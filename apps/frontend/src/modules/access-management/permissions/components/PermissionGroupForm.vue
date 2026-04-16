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
  getActionLabel,
  getModuleLabel,
} from '@/modules/access-management/permissions/permissionUiMap'
import {
  buildEmptyMatrix,
  createPermissionGroup,
  getPermissionGroupById,
  getPermissionsCatalog,
  permissionListToMatrix,
  updatePermissionGroup,
} from '@/modules/access-management/permissions/services/permissionService'
import {
  CRUD_ACTIONS,
  type CrudAction,
  type PermissionGroup,
  type PermissionMatrix,
  type UpsertPermissionGroupPayload,
} from '@/modules/access-management/permissions/types/permissionGroup'

const route = useRoute()
const router = useRouter()
const props = withDefaults(
  defineProps<{
    mode?: 'create' | 'edit'
    open?: boolean
  }>(),
  { mode: undefined, open: undefined },
)
const emit = defineEmits<{
  (e: 'success'): void
  (e: 'cancel'): void
}>()
const isNew = computed(() =>
  props.mode ? props.mode === 'create' : route.name === 'permissions.new',
)
const groupId = computed(() => Number(route.params.id))
const matrixModules = ref<string[]>([])
const matrix = ref<PermissionMatrix>({})

const pageLoading = ref(false)
const submitLoading = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')

const formSchema = z.object({
  name: z.string().trim().min(1, 'Nome do Grupo é obrigatório'),
  is_active: z.boolean(),
})
type PermissionGroupFormData = z.infer<typeof formSchema>

const defaultValues: PermissionGroupFormData = { name: '', is_active: true }

const { setValues, setErrors, values } = useForm<PermissionGroupFormData>({
  validationSchema: toTypedSchema(formSchema),
  initialValues: defaultValues,
})

async function applyBackendGroup(payload: PermissionGroup): Promise<void> {
  await nextTick()
  setValues({
    name: payload.name ?? '',
    is_active: payload.is_active ?? true,
  })
  console.log('FORM VALUES:', values)
  matrix.value = permissionListToMatrix(payload.permissions ?? [], matrixModules.value)
}

function togglePermission(moduleName: string, action: CrudAction, checked: boolean): void {
  if (!matrix.value[moduleName]) {
    return
  }
  matrix.value[moduleName][action] = checked
}

function toPayload(values: PermissionGroupFormData): UpsertPermissionGroupPayload {
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
  setErrors({ name: errors.name?.[0], is_active: errors.is_active?.[0] })
}

async function loadGroupIfEditing(): Promise<void> {
  if (isNew.value || Number.isNaN(groupId.value)) {
    return
  }
  const group = await getPermissionGroupById(groupId.value)
  console.log('RAW API RESULT:', group)
  const normalized = (group as PermissionGroup & { data?: PermissionGroup })?.data ?? group
  console.log('NORMALIZED:', normalized)
  await applyBackendGroup(normalized as PermissionGroup)
}

async function onSubmit(values: PermissionGroupFormData): Promise<void> {
  feedbackKind.value = ''
  feedbackMessage.value = ''
  submitLoading.value = true
  try {
    setErrors({})
    const payload = toPayload(values)
    if (isNew.value) {
      await createPermissionGroup(payload)
      if (props.mode === 'create') {
        emit('success')
      } else {
        await router.push('/permissions')
      }
      return
    }
    await updatePermissionGroup(groupId.value, payload)
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

const submitWithValidation = async (submittedValues: PermissionGroupFormData) => {
  await onSubmit(submittedValues)
}

onMounted(async () => {
  await nextTick()
  pageLoading.value = true
  try {
    const catalog = await getPermissionsCatalog()
    matrixModules.value = Object.keys(catalog)
    matrix.value = buildEmptyMatrix(matrixModules.value)
    await loadGroupIfEditing()
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
      matrix.value = buildEmptyMatrix(matrixModules.value)
      console.log('FORM VALUES:', values)
      return
    }
    await loadGroupIfEditing()
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
          <FormLabel>Nome do Grupo</FormLabel>
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
              <th class="px-2 py-2 text-left">Menu / Módulo</th>
              <th v-for="action in CRUD_ACTIONS" :key="action" class="px-2 py-2 text-left">
                {{ getActionLabel(action) }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="moduleName in matrixModules" :key="moduleName">
              <td class="px-2 py-2">{{ getModuleLabel(moduleName) }}</td>
              <td v-for="action in CRUD_ACTIONS" :key="`${moduleName}.${action}`" class="px-2 py-2">
                <Checkbox
                  :checked="matrix[moduleName]?.[action] ?? false"
                  :disabled="pageLoading || submitLoading"
                  @update:checked="
                    (value: boolean | 'indeterminate') =>
                      togglePermission(moduleName, action, value === true)
                  "
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
          <FormLabel>Estado (Ativo)</FormLabel>
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
