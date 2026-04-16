<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useForm } from 'vee-validate'

import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'

// COMPONENTES UI (Shadcn)
import { Button } from '@/components/ui/button'
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

// SERVICES
import {
  createContact,
  getContactById,
  updateContact,
  type UpsertContactPayload,
} from '@/modules/contacts/services/contactService'

import type { Contact } from '@/modules/contacts/types/contact'

import { listEntities } from '@/modules/entities/services/entityService'
import type { Entity } from '@/modules/entities/types/entity'

import { listContactFunctionsResult } from '@/modules/settings/contact-functions/services/contactFunctionService'

type ContactFunctionOption = { id: number; name: string }

const route = useRoute()
const router = useRouter()
const props = withDefaults(defineProps<{ mode?: 'create' | 'edit' }>(), { mode: undefined })
const emit = defineEmits<{ (e: 'success'): void; (e: 'cancel'): void }>()

const isNew = computed(() => (props.mode ? props.mode === 'create' : route.name === 'contacts.new'))
const contactId = computed(() => Number(route.params.id))
const pageLoading = ref(false)
const submitLoading = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')

const entities = ref<Entity[]>([])
const functions = ref<ContactFunctionOption[]>([])

const contactSchema = z.object({
  number: z.string().trim().optional(),
  entity_id: z.coerce.number().int().positive('Entidade é obrigatória'),
  first_name: z.string().trim().min(1, 'Nome é obrigatório'),
  last_name: z.string().trim().min(1, 'Apelido é obrigatório'),
  contact_function_id: z.union([z.coerce.number().int().positive(), z.literal(0)]),
  phone: z.string().trim().optional(),
  mobile: z.string().trim().optional(),
  email: z.union([z.string().trim().email('Email inválido'), z.literal('')]),
  rgpd_consent: z.boolean(),
  notes: z.string().trim().optional(),
  is_active: z.boolean(),
})

type ContactFormData = z.infer<typeof contactSchema>

const defaultValues: ContactFormData = {
  number: '',
  entity_id: 0,
  first_name: '',
  last_name: '',
  contact_function_id: 0,
  phone: '',
  mobile: '',
  email: '',
  rgpd_consent: false,
  notes: '',
  is_active: true,
}

const { setValues, values } = useForm<ContactFormData>({
  validationSchema: toTypedSchema(contactSchema),
  initialValues: defaultValues,
})

async function applyBackendContact(payload: Contact): Promise<void> {
  await nextTick()
  setValues({
    number: payload.number ?? '',
    entity_id: payload.entity?.id ?? 0,
    first_name: payload.first_name ?? '',
    last_name: payload.last_name ?? '',
    contact_function_id: payload.function?.id ?? 0,
    phone: payload.phone ?? '',
    mobile: payload.mobile ?? '',
    email: payload.email ?? '',
    rgpd_consent: payload.rgpd_consent ?? false,
    notes: payload.notes ?? '',
    is_active: payload.is_active ?? true,
  })
  console.log('FORM VALUES:', values)
}

function toPayload(data: ContactFormData): UpsertContactPayload {
  return {
    entity_id: data.entity_id,
    contact_function_id: data.contact_function_id > 0 ? data.contact_function_id : null,
    first_name: data.first_name,
    last_name: data.last_name,
    email: data.email || null,
    phone: data.phone || null,
    mobile: data.mobile || null,
    rgpd_consent: data.rgpd_consent,
    notes: data.notes || null,
    is_active: data.is_active,
  }
}

async function loadDependencies(): Promise<void> {
  const [entitiesList, functionsResponse] = await Promise.all([
    listEntities({ active_only: true, per_page: 100 }),
    listContactFunctionsResult({ per_page: 100 }),
  ])
  entities.value = entitiesList
  functions.value = functionsResponse.data
}

async function loadContactIfEditing(): Promise<void> {
  console.log('ID:', contactId.value)
  if (isNew.value || Number.isNaN(contactId.value)) return
  const contact = await getContactById(contactId.value)
  console.log('RAW API RESULT:', contact)
  const normalized = (contact as Contact & { data?: Contact })?.data ?? contact
  console.log('NORMALIZED:', normalized)
  await applyBackendContact(normalized as Contact)
}

async function onSubmit(submittedValues: ContactFormData): Promise<void> {
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
    if (props.mode === 'create') {
      emit('success')
      return
    }
    await router.push('/contacts')
    return
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
}

const submitWithValidation = async (formValues: ContactFormData) => {
  await onSubmit(formValues)
}

onMounted(async () => {
  await nextTick()
  pageLoading.value = true
  try {
    const [dependenciesLoad, contactLoad] = await Promise.allSettled([
      loadDependencies(),
      loadContactIfEditing(),
    ])

    if (dependenciesLoad.status === 'rejected' && contactLoad.status === 'fulfilled') {
      feedbackKind.value = 'error'
      feedbackMessage.value = 'Contacto carregado, mas não foi possível carregar listas auxiliares.'
    }

    if (contactLoad.status === 'rejected') {
      throw contactLoad.reason
    }
  } catch {
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível carregar os dados do formulário.'
  } finally {
    pageLoading.value = false
  }
})
</script>

<template>
  <div class="space-y-4">
    <h1>{{ isNew ? 'Novo contacto' : 'Editar contacto' }}</h1>

    <p
      v-if="feedbackMessage"
      :class="feedbackKind === 'error' ? 'text-destructive' : 'text-green-700'"
      class="text-sm"
    >
      {{ feedbackMessage }}
    </p>

    <Form class="grid gap-4" @submit="submitWithValidation">
      <FormField v-slot="{ value, handleChange }" name="number">
        <FormItem>
          <FormLabel>Número</FormLabel>
          <FormControl>
            <Input :model-value="value" readonly :disabled="true" @update:model-value="handleChange" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="entity_id">
        <FormItem>
          <FormLabel>Entidade</FormLabel>
          <Select
            :model-value="String(value || '')"
            :disabled="pageLoading || submitLoading"
            @update:model-value="(selected) => handleChange(Number(selected))"
          >
            <FormControl>
              <SelectTrigger><SelectValue placeholder="Selecione a entidade" /></SelectTrigger>
            </FormControl>
            <SelectContent>
              <SelectItem v-for="entity in entities" :key="entity.id" :value="String(entity.id)">
                {{ entity.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <FormField v-slot="{ value, handleChange }" name="first_name">
          <FormItem>
            <FormLabel>Nome</FormLabel>
            <FormControl
              ><Input
                :model-value="value"
                :disabled="pageLoading || submitLoading"
                @update:model-value="handleChange"
            /></FormControl>
            <FormMessage />
          </FormItem>
        </FormField>
        <FormField v-slot="{ value, handleChange }" name="last_name">
          <FormItem>
            <FormLabel>Apelido</FormLabel>
            <FormControl
              ><Input
                :model-value="value"
                :disabled="pageLoading || submitLoading"
                @update:model-value="handleChange"
            /></FormControl>
            <FormMessage />
          </FormItem>
        </FormField>
      </div>

      <FormField v-slot="{ value, handleChange }" name="contact_function_id">
        <FormItem>
          <FormLabel>Função</FormLabel>
          <Select
            :model-value="String(value || 0)"
            :disabled="pageLoading || submitLoading"
            @update:model-value="(selected) => handleChange(Number(selected))"
          >
            <FormControl>
              <SelectTrigger><SelectValue placeholder="Selecione a função" /></SelectTrigger>
            </FormControl>
            <SelectContent>
              <SelectItem value="0">Sem função</SelectItem>
              <SelectItem v-for="option in functions" :key="option.id" :value="String(option.id)">
                {{ option.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <FormField v-slot="{ value, handleChange }" name="phone">
          <FormItem>
            <FormLabel>Telefone</FormLabel>
            <FormControl
              ><Input
                :model-value="value"
                :disabled="pageLoading || submitLoading"
                @update:model-value="handleChange"
            /></FormControl>
            <FormMessage />
          </FormItem>
        </FormField>
        <FormField v-slot="{ value, handleChange }" name="mobile">
          <FormItem>
            <FormLabel>Telemóvel</FormLabel>
            <FormControl
              ><Input
                :model-value="value"
                :disabled="pageLoading || submitLoading"
                @update:model-value="handleChange"
            /></FormControl>
            <FormMessage />
          </FormItem>
        </FormField>
        <FormField v-slot="{ value, handleChange }" name="email">
          <FormItem>
            <FormLabel>Email</FormLabel>
            <FormControl
              ><Input
                :model-value="value"
                type="email"
                :disabled="pageLoading || submitLoading"
                @update:model-value="handleChange"
            /></FormControl>
            <FormMessage />
          </FormItem>
        </FormField>
      </div>

      <FormField v-slot="{ value, handleChange }" name="rgpd_consent">
        <FormItem>
          <FormLabel>Consentimento RGPD</FormLabel>
          <Select
            :model-value="value ? '1' : '0'"
            :disabled="pageLoading || submitLoading"
            @update:model-value="(selected) => handleChange(selected === '1')"
          >
            <FormControl
              ><SelectTrigger><SelectValue /></SelectTrigger
            ></FormControl>
            <SelectContent>
              <SelectItem value="1">Sim</SelectItem>
              <SelectItem value="0">Não</SelectItem>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="is_active">
        <FormItem>
          <FormLabel>Estado</FormLabel>
          <Select
            :model-value="value ? '1' : '0'"
            :disabled="pageLoading || submitLoading"
            @update:model-value="(selected) => handleChange(selected === '1')"
          >
            <FormControl
              ><SelectTrigger><SelectValue /></SelectTrigger
            ></FormControl>
            <SelectContent>
              <SelectItem value="1">Ativo</SelectItem>
              <SelectItem value="0">Inativo</SelectItem>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="notes">
        <FormItem>
          <FormLabel>Observações</FormLabel>
          <FormControl
            ><Textarea
              :model-value="value"
              :disabled="pageLoading || submitLoading"
              @update:model-value="handleChange"
          /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="flex items-center gap-2 pt-2">
        <Button v-if="props.mode" type="button" variant="outline" @click="emit('cancel')"
          >Cancelar</Button
        >
        <RouterLink v-else to="/contacts" class="text-sm text-primary">Cancelar</RouterLink>
        <Button :disabled="submitLoading || pageLoading" type="submit">
          {{ submitLoading ? 'A guardar...' : 'Guardar' }}
        </Button>
      </div>
    </Form>
  </div>
</template>
