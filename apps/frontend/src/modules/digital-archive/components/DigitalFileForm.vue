<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { Button } from '@/components/ui/button'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { useApiAction } from '@/composables/useApiAction'
import { digitalFileSchema, type DigitalFileFormData } from '../schemas/digitalFileSchema'
import { uploadDigitalFile } from '../services/digitalFileService'
import { getEntities } from '../services/entityService'

const emit = defineEmits<{
  (e: 'uploaded'): void
}>()

const { loading: submitLoading, execute } = useApiAction()
const entities = ref<Array<{ id: number; name: string }>>([])
const loadingEntities = ref(false)

const { setFieldValue, setErrors, resetForm } = useForm<DigitalFileFormData>({
  validationSchema: toTypedSchema(digitalFileSchema),
  initialValues: {
    name: '',
    category: '',
    description: '',
    entity_id: null,
  },
})

async function loadEntities(): Promise<void> {
  loadingEntities.value = true
  try {
    const response = await getEntities({ per_page: 100 })
    const payload = response?.data
    const list = payload?.data?.data ?? payload?.data ?? []
    entities.value = Array.isArray(list) ? list : []
  } finally {
    loadingEntities.value = false
  }
}

function onFileChange(event: Event): void {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) {
    return
  }
  setFieldValue('file', file)
}

async function onSubmit(values: DigitalFileFormData): Promise<void> {
  setErrors({})
  const formData = new FormData()

  Object.entries(values).forEach(([key, value]) => {
    if (value !== null && value !== undefined) {
      formData.append(key, value as string | Blob)
    }
  })

  await execute(() => uploadDigitalFile(formData), {
    onSuccess: () => {
      resetForm()
      emit('uploaded')
    },
  })
}

const submitWithValidation = async (values: DigitalFileFormData) => {
  await onSubmit(values)
}

onMounted(() => {
  void loadEntities()
})
</script>

<template>
  <div class="space-y-3 rounded-md border p-4">
    <h2 class="text-base font-medium">Upload</h2>

    <Form class="grid gap-3" @submit="submitWithValidation">
      <FormField v-slot="{ componentField }" name="name">
        <FormItem>
          <FormLabel>Nome</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="submitLoading" placeholder="Nome do ficheiro" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="category">
        <FormItem>
          <FormLabel>Categoria</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="submitLoading" placeholder="Categoria" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="description">
        <FormItem>
          <FormLabel>Descrição</FormLabel>
          <FormControl>
            <Textarea v-bind="componentField" :disabled="submitLoading" placeholder="Descrição" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value }" name="entity_id">
        <FormItem>
          <FormLabel>Entidade (opcional)</FormLabel>
          <FormControl>
            <select
              class="w-full rounded border px-3 py-2"
              :value="value == null ? '' : String(value)"
              :disabled="submitLoading"
              @change="
                setFieldValue(
                  'entity_id',
                  ($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null,
                )
              "
            >
              <option value="">Sem entidade</option>
              <option v-for="entity in entities" :key="entity.id" :value="entity.id">
                {{ entity.name }}
              </option>
            </select>
          </FormControl>
          <p v-if="loadingEntities" class="text-xs text-muted-foreground">A carregar entidades...</p>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField name="file">
        <FormItem>
          <FormLabel>Ficheiro</FormLabel>
          <FormControl>
            <Input type="file" :disabled="submitLoading" @change="onFileChange" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <div>
        <Button :disabled="submitLoading" type="submit">
          {{ submitLoading ? 'A carregar...' : 'Upload' }}
        </Button>
      </div>
    </Form>
  </div>
</template>
