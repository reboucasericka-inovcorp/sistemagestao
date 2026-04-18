<script setup lang="ts">
import axios from 'axios'
import { computed, reactive, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { handleApiError } from '@/shared/utils/handleApiError'
import { createCustomerAccountMovement } from '@/modules/finance/customer-accounts/customerAccountService'

const props = defineProps<{
  entityId: number | null
}>()

/** Equivalente a `selectedEntityId` no pai: sem cliente, formulário desativado. */
const isFormEnabled = computed(() => props.entityId != null)

const emit = defineEmits<{
  created: []
}>()

const submitting = ref(false)
const feedback = ref('')
const form = reactive({
  type: 'credit' as 'credit' | 'debit',
  amount: '',
  description: '',
  date: new Date().toISOString().slice(0, 10),
})

function todayIsoDate(): string {
  return new Date().toISOString().slice(0, 10)
}

function resetForm(): void {
  form.type = 'credit'
  form.amount = ''
  form.description = ''
  form.date = todayIsoDate()
}

watch(
  () => props.entityId,
  () => {
    feedback.value = ''
    resetForm()
  },
)

async function submit(): Promise<void> {
  if (!props.entityId) return


  const amount = Number(form.amount)
  if (!Number.isFinite(amount) || amount <= 0) {
    feedback.value = 'Valor inválido. Informe um valor maior que zero.'
    return
  }

  if (!form.date) {
    feedback.value = 'Data do movimento é obrigatória.'
    return
  }

  submitting.value = true
  feedback.value = ''

  try {
    await createCustomerAccountMovement(props.entityId, {
      type: form.type,
      amount,
      description: form.description.trim() || undefined,
      date: form.date,
    })
    resetForm()
    emit('created')
  } catch (e: unknown) {
    handleApiError(e)
    if (axios.isAxiosError(e)) {
      const status = e.response?.status
      const apiMsg = String((e.response?.data as { message?: unknown })?.message ?? '').trim()
      if (status === 403) {
        feedback.value = 'Sem permissão para adicionar movimentos da conta corrente.'
      } else if (status === 422 && apiMsg) {
        feedback.value = apiMsg
      } else if (apiMsg) {
        feedback.value = apiMsg
      } else {
        feedback.value = 'Não foi possível adicionar o movimento.'
      }
      return
    }
    feedback.value = 'Não foi possível adicionar o movimento.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="rounded-md border p-4">
    <h2 class="mb-3 text-base font-semibold">Adicionar movimento</h2>
    <p v-if="!isFormEnabled" class="mb-3 text-sm text-muted-foreground">
      Selecione um cliente para adicionar movimentos.
    </p>
    <p v-if="feedback" class="mb-3 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
      {{ feedback }}
    </p>

    <form class="grid gap-3" @submit.prevent="submit">
      <div class="grid gap-1">
        <label>Tipo</label>
        <select v-model="form.type" class="native-select" :disabled="submitting || !isFormEnabled">
          <option value="credit">Crédito</option>
          <option value="debit">Débito</option>
        </select>
      </div>

      <div class="grid gap-1">
        <label>Valor</label>
        <Input v-model="form.amount" :disabled="submitting || !isFormEnabled" type="number" min="0.01" step="0.01" />
      </div>

      <div class="grid gap-1">
        <label>Descrição</label>
        <Input v-model="form.description" :disabled="submitting || !isFormEnabled" />
      </div>

      <div class="grid gap-1">
        <label>Data</label>
        <Input v-model="form.date" :disabled="submitting || !isFormEnabled" type="date" />
      </div>

      <div class="flex justify-end">
        <Button :disabled="submitting || !isFormEnabled" type="submit">
          {{ submitting ? 'A guardar...' : 'Adicionar movimento' }}
        </Button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.native-select {
  width: 100%;
  border: 1px solid hsl(var(--border));
  border-radius: 0.5rem;
  min-height: 2.5rem;
  padding: 0 0.75rem;
  background: hsl(var(--background));
}
</style>
