<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { listEntities } from '@/modules/entities/services/entityService'
import type { Entity } from '@/modules/entities/types/entity'
import {
  createContact,
  getContactById,
  updateContact,
  type UpsertContactPayload,
} from '@/modules/contacts/services/contactService'
import { listContactRoles, type ContactRole } from '@/modules/contacts/services/contactRoleService'
import type { Contact } from '@/modules/contacts/types/contact'

const route = useRoute()
const router = useRouter()
const props = withDefaults(
  defineProps<{
    mode?: 'create' | 'edit'
  }>(),
  { mode: undefined },
)
const emit = defineEmits<{
  (e: 'success'): void
  (e: 'cancel'): void
}>()
const isNew = computed(() => (props.mode ? props.mode === 'create' : route.name === 'contacts.new'))
const contactId = computed(() => Number(route.params.id))

const pageLoading = ref(false)
const submitLoading = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')

const entities = ref<Entity[]>([])
const roles = ref<ContactRole[]>([])
const entitySearch = ref('')

const contactSchema = z.object({
  number: z.string().trim().optional(),
  entity_id: z.coerce.number().int().positive('Entidade é obrigatória'),
  first_name: z.string().trim().min(1, 'Nome é obrigatório'),
  last_name: z.string().trim().min(1, 'Apelido é obrigatório'),
  role_id: z.coerce.number().int().positive('Função é obrigatória'),
  phone: z.string().trim().optional(),
  mobile: z.string().trim().optional(),
  email: z.union([z.string().trim().email('Email inválido'), z.literal('')]),
  consent_rgpd: z.boolean(),
  notes: z.string().trim().optional(),
  is_active: z.boolean(),
})

type ContactFormData = z.infer<typeof contactSchema>

const defaultValues: ContactFormData = {
  number: '',
  entity_id: 0,
  first_name: '',
  last_name: '',
  role_id: 0,
  phone: '',
  mobile: '',
  email: '',
  consent_rgpd: false,
  notes: '',
  is_active: true,
}

const { handleSubmit, resetForm, setFieldValue, values } = useForm<ContactFormData>({
  validationSchema: toTypedSchema(contactSchema),
  initialValues: defaultValues,
})

const filteredEntities = computed(() => {
  const term = entitySearch.value.trim().toLowerCase()
  if (!term) {
    return entities.value
  }
  return entities.value.filter((entity) => entity.name.toLowerCase().includes(term))
})

function splitName(fullName: string): { firstName: string; lastName: string } {
  const trimmed = fullName.trim()
  if (!trimmed) {
    return { firstName: '', lastName: '' }
  }
  const parts = trimmed.split(/\s+/)
  if (parts.length === 1) {
    return { firstName: parts[0], lastName: '' }
  }
  return {
    firstName: parts[0],
    lastName: parts.slice(1).join(' '),
  }
}

function applyBackendContact(payload: Contact): void {
  const { firstName, lastName } = splitName(payload.name)
  resetForm({
    values: {
      ...defaultValues,
      number: payload.number ?? String(payload.id),
      entity_id: payload.entity_id,
      first_name: firstName,
      last_name: lastName,
      role_id: payload.contact_function_id ?? 0,
      phone: payload.phone ?? '',
      mobile: payload.mobile ?? '',
      email: payload.email ?? '',
      notes: payload.notes ?? '',
      is_active: payload.is_active,
    },
  })
}

function toPayload(data: ContactFormData): UpsertContactPayload {
  return {
    entity_id: data.entity_id,
    contact_function_id: data.role_id,
    name: `${data.first_name} ${data.last_name}`.trim(),
    email: data.email || null,
    phone: data.phone || null,
    mobile: data.mobile || null,
    notes: data.notes || null,
    is_active: data.is_active,
  }
}

async function loadDependencies(): Promise<void> {
  const [entitiesList, rolesList] = await Promise.all([
    listEntities({ active_only: true }),
    listContactRoles(),
  ])
  entities.value = entitiesList
  roles.value = rolesList
}

async function loadContactIfEditing(): Promise<void> {
  if (isNew.value || Number.isNaN(contactId.value)) {
    return
  }
  const contact = await getContactById(contactId.value)
  applyBackendContact(contact)
}

const onSubmit = handleSubmit(async (submittedValues) => {
  feedbackKind.value = ''
  feedbackMessage.value = ''
  submitLoading.value = true
  try {
    const payload = toPayload(submittedValues)
    if (isNew.value) {
      await createContact(payload)
      feedbackKind.value = 'success'
      feedbackMessage.value = 'Contacto criado com sucesso.'
      if (props.mode === 'create') {
        emit('success')
      } else {
        await router.push('/contacts')
      }
      return
    }
    await updateContact(contactId.value, payload)
    feedbackKind.value = 'success'
    feedbackMessage.value = 'Contacto atualizado com sucesso.'
  } catch (error: unknown) {
    const maybeMessage =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined
    feedbackKind.value = 'error'
    feedbackMessage.value = maybeMessage ?? 'Não foi possível guardar o contacto.'
  } finally {
    submitLoading.value = false
  }
})

onMounted(async () => {
  pageLoading.value = true
  try {
    await loadDependencies()
    await loadContactIfEditing()
  } catch {
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível carregar os dados do formulário.'
  } finally {
    pageLoading.value = false
  }
})
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Novo contacto' : 'Editar contacto' }}</h1>

    <p v-if="feedbackMessage" :class="feedbackKind === 'error' ? 'error' : 'success'" class="feedback">
      {{ feedbackMessage }}
    </p>

    <Form class="form" @submit="onSubmit">
      <FormField v-slot="{ componentField }" name="number">
        <FormItem>
          <FormLabel>Número</FormLabel>
          <FormControl>
            <Input v-bind="componentField" readonly :disabled="true" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="entity_id">
        <FormItem>
          <FormLabel>Entidade</FormLabel>
          <div class="space-y-2">
            <Input
              :model-value="entitySearch"
              placeholder="Pesquisar entidade..."
              :disabled="pageLoading || submitLoading"
              @update:model-value="entitySearch = String($event)"
            />
            <Select
              :model-value="String(componentField.modelValue || '')"
              :disabled="pageLoading || submitLoading"
              @update:model-value="(value) => componentField['onUpdate:modelValue'](Number(value))"
            >
              <FormControl>
                <SelectTrigger>
                  <SelectValue placeholder="Selecione a entidade" />
                </SelectTrigger>
              </FormControl>
              <SelectContent>
                <SelectItem v-for="entity in filteredEntities" :key="entity.id" :value="String(entity.id)">
                  {{ entity.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="name-grid">
        <FormField v-slot="{ componentField }" name="first_name">
          <FormItem>
            <FormLabel>Nome</FormLabel>
            <FormControl>
              <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
            </FormControl>
            <FormMessage />
          </FormItem>
        </FormField>

        <FormField v-slot="{ componentField }" name="last_name">
          <FormItem>
            <FormLabel>Apelido</FormLabel>
            <FormControl>
              <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
            </FormControl>
            <FormMessage />
          </FormItem>
        </FormField>
      </div>

      <FormField v-slot="{ componentField }" name="role_id">
        <FormItem>
          <FormLabel>Função</FormLabel>
          <Select
            :model-value="String(componentField.modelValue || '')"
            :disabled="pageLoading || submitLoading"
            @update:model-value="(value) => componentField['onUpdate:modelValue'](Number(value))"
          >
            <FormControl>
              <SelectTrigger>
                <SelectValue placeholder="Selecione a função" />
              </SelectTrigger>
            </FormControl>
            <SelectContent>
              <SelectItem v-for="role in roles" :key="role.id" :value="String(role.id)">
                {{ role.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="phone">
        <FormItem>
          <FormLabel>Telefone</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="mobile">
        <FormItem>
          <FormLabel>Telemóvel</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="email">
        <FormItem>
          <FormLabel>Email</FormLabel>
          <FormControl>
            <Input v-bind="componentField" type="email" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="notes">
        <FormItem>
          <FormLabel>Observações</FormLabel>
          <FormControl>
            <Textarea v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="checks">
        <FormField v-slot="{ value, handleChange }" name="consent_rgpd">
          <FormItem class="check-item">
            <FormControl>
              <Checkbox
                :checked="Boolean(value)"
                :disabled="pageLoading || submitLoading"
                @update:checked="handleChange"
              />
            </FormControl>
            <FormLabel>Consentimento RGPD</FormLabel>
            <FormMessage />
          </FormItem>
        </FormField>

        <FormField v-slot="{ value, handleChange }" name="is_active">
          <FormItem class="check-item">
            <FormControl>
              <Checkbox
                :checked="Boolean(value)"
                :disabled="pageLoading || submitLoading"
                @update:checked="handleChange"
              />
            </FormControl>
            <FormLabel>Ativo</FormLabel>
            <FormMessage />
          </FormItem>
        </FormField>
      </div>

      <div class="footer">
        <Button v-if="props.mode" type="button" variant="outline" @click="emit('cancel')">Cancelar</Button>
        <RouterLink v-else to="/contacts">Cancelar</RouterLink>
        <Button :disabled="submitLoading || pageLoading" type="submit">
          {{ submitLoading ? 'A guardar...' : 'Guardar' }}
        </Button>
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

.name-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.checks {
  border: 1px solid hsl(var(--border));
  border-radius: 0.5rem;
  padding: 0.75rem;
  display: grid;
  gap: 0.7rem;
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
