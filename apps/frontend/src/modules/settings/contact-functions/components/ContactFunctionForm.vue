<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import {
  createContactFunction,
  getContactFunctionById,
  updateContactFunction,
  type UpsertContactFunctionPayload,
} from '@/modules/settings/contact-functions/services/contactFunctionService'
import type { ContactFunction } from '@/modules/settings/contact-functions/types/contactFunction'

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
const isNew = computed(() =>
  props.mode ? props.mode === 'create' : route.name === 'contact-functions.new',
)
const contactFunctionId = computed(() => Number(route.params.id))

const pageLoading = ref(false)
const submitLoading = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')

const contactFunctionSchema = z.object({
  name: z.string().trim().min(1, 'Nome é obrigatório'),
  is_active: z.boolean(),
})

type ContactFunctionFormData = z.infer<typeof contactFunctionSchema>

const defaultValues: ContactFunctionFormData = {
  name: '',
  is_active: true,
}

const { setValues, values } = useForm<ContactFunctionFormData>({
  validationSchema: toTypedSchema(contactFunctionSchema),
  initialValues: defaultValues,
})

async function applyBackendContactFunction(payload: ContactFunction): Promise<void> {
  await nextTick()
  setValues({
    name: payload.name ?? '',
    is_active: payload.is_active ?? true,
  })
  console.log('FORM VALUES:', values)
}

async function loadContactFunctionIfEditing(): Promise<void> {
  if (isNew.value || Number.isNaN(contactFunctionId.value)) {
    return
  }
  const contactFunction = await getContactFunctionById(contactFunctionId.value)
  console.log('RAW API RESULT:', contactFunction)
  const normalized =
    (contactFunction as ContactFunction & { data?: ContactFunction })?.data ?? contactFunction
  console.log('NORMALIZED:', normalized)
  await applyBackendContactFunction(normalized as ContactFunction)
}

function toPayload(values: ContactFunctionFormData): UpsertContactFunctionPayload {
  return {
    name: values.name,
    is_active: values.is_active,
  }
}

async function onSubmit(submittedValues: ContactFunctionFormData): Promise<void> {
  feedbackKind.value = ''
  feedbackMessage.value = ''
  submitLoading.value = true
  try {
    const payload = toPayload(submittedValues)
    if (isNew.value) {
      await createContactFunction(payload)
      if (props.mode === 'create') {
        // Modal (home page)
        emit('success')
      } else {
        // Página de rota: fechar/navegar de volta
        await router.push('/settings/contact-functions')
      }
      return
    }
    await updateContactFunction(contactFunctionId.value, payload)
    feedbackKind.value = 'success'
    feedbackMessage.value = 'Função de contacto atualizada com sucesso.'
    if (props.mode === 'edit') {
      emit('success')
    } else {
      // Página de rota: fechar/navegar de volta
      await router.push('/settings/contact-functions')
    }
  } catch (error: unknown) {
    const maybeMessage =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined
    feedbackKind.value = 'error'
    feedbackMessage.value = maybeMessage ?? 'Não foi possível guardar a função de contacto.'
  } finally {
    submitLoading.value = false
  }
}

const submitWithValidation = async (submittedValues: ContactFunctionFormData) => {
  await onSubmit(submittedValues)
}

onMounted(async () => {
  await nextTick()
  pageLoading.value = true
  try {
    await loadContactFunctionIfEditing()
  } catch {
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível carregar dados da função de contacto.'
  } finally {
    pageLoading.value = false
  }
})
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Nova função de contacto' : 'Editar função de contacto' }}</h1>

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
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
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

      <div class="footer">
        <Button v-if="props.mode" type="button" variant="outline" @click="emit('cancel')"
          >Cancelar</Button
        >
        <RouterLink v-else to="/settings/contact-functions">Cancelar</RouterLink>
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
