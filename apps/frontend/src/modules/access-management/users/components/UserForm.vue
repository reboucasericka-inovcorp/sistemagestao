<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { listRoles } from '@/modules/access-management/roles/services/roleService'
import type { AccessRole } from '@/modules/access-management/roles/types/role'
import {
  createUser,
  getUserById,
  updateUser,
} from '@/modules/access-management/users/services/userService'
import type {
  AccessUser,
  UpsertAccessUserPayload,
} from '@/modules/access-management/users/types/accessUser'

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
const isNew = computed(() => (props.mode ? props.mode === 'create' : route.name === 'users.new'))
const userId = computed(() => Number(props.recordId ?? route.params.id))
const roleOptions = ref<AccessRole[]>([])
const pageLoading = ref(false)
const submitLoading = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')

const userSchema = z.object({
  name: z.string().trim().min(1, 'Nome é obrigatório'),
  email: z.string().trim().email('Email inválido'),
  phone: z.string().trim().optional(),
  role_id: z.string().min(1, 'Grupo de Permissões é obrigatório'),
  is_active: z.boolean(),
})
type UserFormData = z.infer<typeof userSchema>

const { resetForm, setFieldValue, setErrors } = useForm<UserFormData>({
  validationSchema: toTypedSchema(userSchema),
  initialValues: { name: '', email: '', phone: '', role_id: '', is_active: true },
})

function applyBackendUser(payload: AccessUser): void {
  resetForm({
    values: {
      name: payload.name,
      email: payload.email,
      phone: payload.phone ?? '',
      role_id: payload.role?.id ? String(payload.role.id) : '',
      is_active: payload.is_active,
    },
  })
}

function toPayload(values: UserFormData): UpsertAccessUserPayload {
  return {
    name: values.name,
    email: values.email,
    phone: values.phone || null,
    role_id: Number(values.role_id),
    is_active: values.is_active,
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
  setErrors({
    name: errors.name?.[0],
    email: errors.email?.[0],
    phone: errors.phone?.[0],
    role_id: errors.role_id?.[0],
    is_active: errors.is_active?.[0],
  })
}

async function loadUserIfEditing(): Promise<void> {
  if (isNew.value || Number.isNaN(userId.value)) {
    return
  }
  const user = await getUserById(userId.value)
  applyBackendUser(user)

  if (user.role?.id && !roleOptions.value.some((role) => role.id === user.role?.id)) {
    setFieldValue('role_id', '')
    feedbackKind.value = 'error'
    feedbackMessage.value =
      'O utilizador está associado a um Grupo de Permissões inativo. Selecione um grupo ativo.'
  }
}

async function onSubmit(values: UserFormData): Promise<void> {
  feedbackKind.value = ''
  feedbackMessage.value = ''
  submitLoading.value = true
  try {
    setErrors({})
    const payload = toPayload(values)
    if (isNew.value) {
      await createUser(payload)
      if (props.mode === 'create') {
        emit('success')
      } else {
        await router.push('/users')
      }
      return
    }
    await updateUser(userId.value, payload)
    if (props.mode === 'edit') {
      emit('success')
      return
    }
    feedbackKind.value = 'success'
    feedbackMessage.value = 'Utilizador atualizado com sucesso.'
  } catch (error: unknown) {
    applyLaravelValidationErrors(error)
    const maybeMessage =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined
    feedbackKind.value = 'error'
    feedbackMessage.value = maybeMessage ?? 'Não foi possível guardar o utilizador.'
  } finally {
    submitLoading.value = false
  }
}

onMounted(async () => {
  pageLoading.value = true
  try {
    const allRoles = await listRoles()
    roleOptions.value = allRoles.filter((role) => role.is_active)
    await loadUserIfEditing()
  } catch {
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível carregar dados do utilizador.'
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
      resetForm({ values: { name: '', email: '', phone: '', role_id: '', is_active: true } })
      return
    }
    await loadUserIfEditing()
  },
)
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Novo utilizador' : 'Editar utilizador' }}</h1>

    <p
      v-if="feedbackMessage"
      :class="feedbackKind === 'error' ? 'error' : 'success'"
      class="feedback"
    >
      {{ feedbackMessage }}
    </p>

    <Form class="form" @submit="(values) => onSubmit(values as UserFormData)">
      <FormField v-slot="{ componentField }" name="name">
        <FormItem>
          <FormLabel>Nome</FormLabel>
          <FormControl
            ><Input v-bind="componentField" :disabled="pageLoading || submitLoading"
          /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="email">
        <FormItem>
          <FormLabel>Email</FormLabel>
          <FormControl
            ><Input v-bind="componentField" :disabled="pageLoading || submitLoading"
          /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="phone">
        <FormItem>
          <FormLabel>Telemóvel</FormLabel>
          <FormControl
            ><Input v-bind="componentField" :disabled="pageLoading || submitLoading"
          /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="role_id">
        <FormItem>
          <FormLabel>Grupo de Permissões</FormLabel>
          <Select
            :model-value="String(value ?? '')"
            :disabled="pageLoading || submitLoading"
            @update:model-value="(selectedValue) => handleChange(String(selectedValue ?? ''))"
          >
            <FormControl>
              <SelectTrigger
                ><SelectValue placeholder="Selecione o Grupo de Permissões"
              /></SelectTrigger>
            </FormControl>
            <SelectContent>
              <SelectItem v-for="role in roleOptions" :key="role.id" :value="String(role.id)">{{
                role.name
              }}</SelectItem>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

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
        <Button v-if="props.mode" type="button" variant="outline" @click="emit('cancel')">Cancelar</Button>
        <RouterLink v-else to="/users">Cancelar</RouterLink>
        <Button :disabled="submitLoading || pageLoading" type="submit">{{
          submitLoading ? 'A guardar...' : 'Guardar'
        }}</Button>
      </div>
    </Form>
  </div>
</template>

<style scoped>
.page {
  max-width: 42rem;
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
