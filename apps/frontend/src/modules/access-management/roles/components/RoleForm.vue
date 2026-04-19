<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import {
  ACCESS_ACTIONS,
  ACCESS_MODULES,
  buildEmptyPermissionMatrix,
  permissionListToMatrix,
  type AccessAction,
  type AccessModule,
  type PermissionMatrix,
} from '@/modules/access-management/permissionCatalog'
import {
  createRole,
  getPermissionsCatalog,
  getRoleById,
  updateRole,
} from '@/modules/access-management/roles/services/roleService'
import type {
  AccessRole,
  UpsertAccessRolePayload,
} from '@/modules/access-management/roles/types/role'

const route = useRoute()
const router = useRouter()
const props = withDefaults(
  defineProps<{
    mode?: 'create' | 'edit'
    recordId?: number | null
    open?: boolean
  }>(),
  { mode: undefined, recordId: null, open: undefined },
)
const emit = defineEmits<{
  (e: 'success'): void
  (e: 'cancel'): void
}>()
const isNew = computed(() => (props.mode ? props.mode === 'create' : route.name === 'roles.new'))
const roleId = computed(() => Number(props.recordId ?? route.params.id))
const matrix = ref<PermissionMatrix>(buildEmptyPermissionMatrix())

const pageLoading = ref(false)
const submitLoading = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')

const roleSchema = z.object({
  name: z.string().trim().min(1, 'Nome é obrigatório'),
  is_active: z.boolean(),
})
type RoleFormData = z.infer<typeof roleSchema>

const defaultValues: RoleFormData = { name: '', is_active: true }

const { setValues, setErrors } = useForm<RoleFormData>({
  validationSchema: toTypedSchema(roleSchema),
  initialValues: defaultValues,
})

async function applyBackendRole(payload: AccessRole): Promise<void> {
  await nextTick()
  setValues({
    name: payload.name ?? '',
    is_active: payload.is_active ?? true,
  })
  matrix.value = permissionListToMatrix(payload.permissions ?? [])
}

function togglePermission(moduleName: AccessModule, action: AccessAction, checked: boolean): void {
  matrix.value[moduleName][action] = checked
}

function toPayload(values: RoleFormData): UpsertAccessRolePayload {
  return {
    name: values.name,
    is_active: values.is_active,
    permissions: matrix.value,
  }
}

function applyLaravelValidationErrors(error: unknown): void {
  if (typeof error !== 'object' || !error || !('response' in error)) {
    return
  }
  const errors = (
    error as { response?: { data?: { errors?: Record<string, string[] | undefined> } } }
  ).response?.data?.errors
  if (!errors) {
    return
  }
  setErrors({ name: errors.name?.[0] })
}

async function loadRoleIfEditing(): Promise<void> {
  if (isNew.value || Number.isNaN(roleId.value)) {
    return
  }
  const role = await getRoleById(roleId.value)
  await applyBackendRole(role)
}

async function onSubmit(values: RoleFormData): Promise<void> {
  feedbackKind.value = ''
  feedbackMessage.value = ''
  submitLoading.value = true
  try {
    setErrors({})
    const payload = toPayload(values)
    if (isNew.value) {
      await createRole(payload)
      if (props.mode === 'create') {
        emit('success')
      } else {
        await router.push('/permissions')
      }
      return
    }
    await updateRole(roleId.value, payload)
    if (props.mode === 'edit') {
      emit('success')
      return
    }
    feedbackKind.value = 'success'
    feedbackMessage.value = 'Grupo de permissões atualizado com sucesso.'
  } catch (error: unknown) {
    applyLaravelValidationErrors(error)
    const maybeMessage =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined
    feedbackKind.value = 'error'
    feedbackMessage.value = maybeMessage ?? 'Não foi possível guardar o grupo de permissões.'
  } finally {
    submitLoading.value = false
  }
}

const submitWithValidation = async (submittedValues: RoleFormData) => {
  await onSubmit(submittedValues)
}

onMounted(async () => {
  await nextTick()
  pageLoading.value = true
  try {
    const catalog = await getPermissionsCatalog()
    if (Object.keys(catalog).length > 0) {
      matrix.value = buildEmptyPermissionMatrix()
    }
    await loadRoleIfEditing()
  } catch {
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível carregar os dados do grupo.'
  } finally {
    pageLoading.value = false
  }
})

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return
    feedbackKind.value = ''
    feedbackMessage.value = ''
    if (isNew.value) {
      await nextTick()
      setValues(defaultValues)
      matrix.value = buildEmptyPermissionMatrix()
      return
    }
    await loadRoleIfEditing()
  },
  { immediate: true },
)
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Novo grupo de permissões' : 'Editar grupo de permissões' }}</h1>

    <p
      v-if="feedbackMessage"
      :class="feedbackKind === 'error' ? 'error' : 'success'"
      class="feedback"
    >
      {{ feedbackMessage }}
    </p>

    <Form class="form" @submit="submitWithValidation">
      <FormField v-slot="{ componentField }" name="name">
        <FormItem>
          <FormLabel>Nome</FormLabel>
          <FormControl
            ><Input v-bind="componentField" :disabled="pageLoading || submitLoading"
          /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="rounded-md border">
        <table class="w-full">
          <thead>
            <tr>
              <th class="px-2 py-2 text-left">Módulo</th>
              <th v-for="action in ACCESS_ACTIONS" :key="action" class="px-2 py-2 text-left">
                {{ action }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="moduleName in ACCESS_MODULES" :key="moduleName">
              <td class="px-2 py-2">{{ moduleName }}</td>
              <td
                v-for="action in ACCESS_ACTIONS"
                :key="`${moduleName}.${action}`"
                class="px-2 py-2"
              >
                <Checkbox
                  :checked="matrix[moduleName][action]"
                  :disabled="pageLoading || submitLoading"
                  @update:checked="(value: boolean) => togglePermission(moduleName, action, value)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <FormField v-slot="{ value, handleChange }" name="is_active">
        <FormItem class="check-item">
          <FormControl
            ><Checkbox
              :checked="Boolean(value)"
              :disabled="pageLoading || submitLoading"
              @update:checked="handleChange"
          /></FormControl>
          <FormLabel>Ativo</FormLabel>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="footer">
        <Button v-if="props.mode" type="button" variant="outline" @click="emit('cancel')"
          >Cancelar</Button
        >
        <RouterLink v-else to="/permissions">Cancelar</RouterLink>
        <Button :disabled="submitLoading || pageLoading" type="submit">{{
          submitLoading ? 'A guardar...' : 'Guardar'
        }}</Button>
      </div>
    </Form>
  </div>
</template>

<style scoped>
.page {
  max-width: 60rem;
}
h1 {
  margin-top: 0;
  text-align: left;
}
.feedback {
  margin-bottom: 1rem;
  font-size: 0.9rem;
}
.success {
  color: #166534;
}
.error {
  color: #b91c1c;
}
.form {
  display: grid;
  gap: 0.9rem;
}
.check-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.footer {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-top: 0.5rem;
}
</style>
