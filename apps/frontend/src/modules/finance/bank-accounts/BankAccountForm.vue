<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { handleApiError } from '@/shared/utils/handleApiError'
import {
  createBankAccount,
  getBankAccountById,
  updateBankAccount,
  type UpsertBankAccountPayload,
} from '@/modules/finance/bank-accounts/bankAccountService'

const route = useRoute()
const router = useRouter()

const isNew = computed(() => route.params.id === undefined)
const accountId = computed(() => Number(route.params.id))
const loading = ref(false)
const submitting = ref(false)
const feedback = ref('')

const form = ref<UpsertBankAccountPayload>({
  bank_name: '',
  iban: '',
  account_holder: '',
  is_active: true,
})

async function hydrate(): Promise<void> {
  if (isNew.value) return

  const account = await getBankAccountById(accountId.value)
  form.value = {
    bank_name: account.bank_name ?? '',
    iban: account.iban ?? '',
    account_holder: account.account_holder ?? '',
    is_active: !!account.is_active,
  }
}

async function onSubmit(): Promise<void> {
  submitting.value = true
  feedback.value = ''
  try {
    if (!form.value.bank_name.trim() || !form.value.iban.trim() || !form.value.account_holder.trim()) {
      feedback.value = 'Preencha banco, IBAN e titular.'
      return
    }

    if (isNew.value) {
      await createBankAccount({
        bank_name: form.value.bank_name.trim(),
        iban: form.value.iban.trim(),
        account_holder: form.value.account_holder.trim(),
        is_active: !!form.value.is_active,
      })
      await router.push('/bank-accounts')
      return
    }

    await updateBankAccount(accountId.value, {
      bank_name: form.value.bank_name.trim(),
      iban: form.value.iban.trim(),
      account_holder: form.value.account_holder.trim(),
      is_active: !!form.value.is_active,
    })
    await router.push('/bank-accounts')
  } catch (e: unknown) {
    handleApiError(e)
    if (axios.isAxiosError(e)) {
      const status = e.response?.status
      const apiMsg = String((e.response?.data as { message?: unknown })?.message ?? '').trim()
      if (status === 403) {
        feedback.value = 'Sem permissão para guardar contas bancárias.'
      } else if (status === 422 && apiMsg) {
        feedback.value = apiMsg
      } else if (apiMsg) {
        feedback.value = apiMsg
      } else {
        feedback.value = 'Não foi possível guardar a conta bancária.'
      }
      return
    }
    feedback.value = 'Não foi possível guardar a conta bancária.'
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await hydrate()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Nova Conta Bancária' : 'Conta Bancária' }}</h1>
    <p v-if="feedback" class="feedback">{{ feedback }}</p>

    <form class="form" @submit.prevent="onSubmit">
      <div class="grid-field">
        <label for="bank_name">Banco</label>
        <Input id="bank_name" v-model="form.bank_name" :disabled="loading || submitting" />
      </div>

      <div class="grid-field">
        <label for="iban">IBAN</label>
        <Input id="iban" v-model="form.iban" :disabled="loading || submitting" />
      </div>

      <div class="grid-field">
        <label for="account_holder">Titular</label>
        <Input id="account_holder" v-model="form.account_holder" :disabled="loading || submitting" />
      </div>

      <label class="check-field">
        <input v-model="form.is_active" :disabled="loading || submitting" type="checkbox" />
        <span>Conta ativa</span>
      </label>

      <div class="footer">
        <Button type="button" variant="outline" @click="router.push('/bank-accounts')">Voltar</Button>
        <Button type="submit" :disabled="loading || submitting">
          {{ submitting ? 'A guardar...' : isNew ? 'Criar' : 'Guardar' }}
        </Button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.page {
  width: 100%;
  max-width: 50rem;
}

.form {
  display: grid;
  gap: 0.9rem;
}

.feedback {
  margin-bottom: 1rem;
}

.grid-field {
  display: grid;
  gap: 0.35rem;
}

.check-field {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.footer {
  display: flex;
  gap: 0.8rem;
}
</style>
