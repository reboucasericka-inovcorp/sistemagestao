<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import FormModal from '@/components/shared/FormModal.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { FormField, FormControl, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import {
  confirmTwoFactorAuthentication,
  confirmPassword,
  disableTwoFactorAuthentication,
  enableTwoFactorAuthentication,
  getRecoveryCodes,
  getTwoFactorQrCode,
  regenerateRecoveryCodes,
} from '@/modules/auth/services/twoFactorService'

const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')
const pageLoading = ref(false)
const actionLoading = ref(false)
const isEnabled = ref(false)
const isTwoFactorConfirmed = ref(false)
const qrCodeSvg = ref('')
const recoveryCodes = ref<string[]>([])
const confirmPasswordModalOpen = ref(false)
const confirmPasswordValue = ref('')
const confirmPasswordFieldError = ref('')

const codeSchema = z.object({
  code: z.string().trim().min(1, 'Código de confirmação é obrigatório'),
})

type ConfirmCodeForm = z.infer<typeof codeSchema>

const {
  handleSubmit: handleConfirmCodeSubmit,
  setErrors: setCodeErrors,
  resetForm: resetCodeForm,
} = useForm<ConfirmCodeForm>({
  validationSchema: toTypedSchema(codeSchema),
  initialValues: {
    code: '',
  },
})

function setFeedback(kind: 'success' | 'error', message: string): void {
  feedbackKind.value = kind
  feedbackMessage.value = message
}

function clearFeedback(): void {
  feedbackKind.value = ''
  feedbackMessage.value = ''
}

function getErrorStatus(error: unknown): number | undefined {
  return typeof error === 'object' && error && 'response' in error
    ? (error as { response?: { status?: number } }).response?.status
    : undefined
}

function applyValidationError(error: unknown): void {
  const status = getErrorStatus(error)

  if (status !== 422) {
    return
  }

  const validationErrors =
    typeof error === 'object' && error && 'response' in error
      ? (error as { response?: { data?: { errors?: Record<string, string[] | undefined> } } })
          .response?.data?.errors
      : undefined

  setCodeErrors({
    code: validationErrors?.code?.[0] ?? 'Código do autenticador inválido.',
  })
}

async function loadTwoFactorState(): Promise<void> {
  pageLoading.value = true

  try {
    const [svg, codes] = await Promise.all([getTwoFactorQrCode(), getRecoveryCodes()])
    qrCodeSvg.value = svg
    recoveryCodes.value = codes
    isEnabled.value = true
    isTwoFactorConfirmed.value = false
  } catch {
    qrCodeSvg.value = ''
    recoveryCodes.value = []
    isEnabled.value = false
    isTwoFactorConfirmed.value = false
  } finally {
    pageLoading.value = false
  }
}

function onEnableTwoFactor(): void {
  clearFeedback()
  confirmPasswordFieldError.value = ''
  confirmPasswordValue.value = ''
  confirmPasswordModalOpen.value = true
}

function closeConfirmPasswordModal(): void {
  confirmPasswordModalOpen.value = false
  confirmPasswordFieldError.value = ''
  confirmPasswordValue.value = ''
}

async function onConfirmPasswordAndEnable(): Promise<void> {
  actionLoading.value = true
  clearFeedback()
  confirmPasswordFieldError.value = ''

  const password = confirmPasswordValue.value.trim()
  if (!password) {
    confirmPasswordFieldError.value = 'Palavra-passe é obrigatória.'
    actionLoading.value = false
    return
  }

  try {
    // Ordem segura exigida pelo Fortify:
    // 1) confirmar palavra-passe, 2) ativar 2FA, 3) carregar QR/códigos.
    await confirmPassword(password)
    await enableTwoFactorAuthentication()
    isTwoFactorConfirmed.value = false
    closeConfirmPasswordModal()
    await loadTwoFactorState()
    setFeedback(
      'success',
      'Autenticação de dois fatores ativada. Confirme com o código do autenticador.',
    )
  } catch (error: unknown) {
    const status = getErrorStatus(error)
    if (status === 422) {
      confirmPasswordFieldError.value = 'Palavra-passe inválida.'
      return
    }
    if (status === 423) {
      setFeedback('error', 'Sessão expirada. Inicie sessão novamente para continuar.')
      return
    }
    setFeedback('error', 'Não foi possível ativar a autenticação de dois fatores.')
  } finally {
    actionLoading.value = false
  }
}

async function onDisableTwoFactor(): Promise<void> {
  actionLoading.value = true
  clearFeedback()

  try {
    await disableTwoFactorAuthentication()
    isEnabled.value = false
    isTwoFactorConfirmed.value = false
    qrCodeSvg.value = ''
    recoveryCodes.value = []
    resetCodeForm()
    setFeedback('success', 'Autenticação de dois fatores desativada.')
  } catch (error: unknown) {
    if (getErrorStatus(error) === 423) {
      setFeedback('error', 'Sessão expirada. Inicie sessão novamente para continuar.')
      return
    }
    setFeedback('error', 'Não foi possível desativar a autenticação de dois fatores.')
  } finally {
    actionLoading.value = false
  }
}

const onConfirmCode = handleConfirmCodeSubmit(async (values: ConfirmCodeForm) => {
  actionLoading.value = true
  clearFeedback()
  setCodeErrors({})

  try {
    await confirmTwoFactorAuthentication(values.code)
    isTwoFactorConfirmed.value = true
    confirmPasswordModalOpen.value = false
    recoveryCodes.value = await getRecoveryCodes()
    resetCodeForm()
    setFeedback('success', 'Código confirmado com sucesso.')
  } catch (error: unknown) {
    applyValidationError(error)
    const message =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined

    setFeedback('error', message ?? 'Não foi possível confirmar o código informado.')
  } finally {
    actionLoading.value = false
  }
})

async function onRegenerateRecoveryCodes(): Promise<void> {
  actionLoading.value = true
  clearFeedback()

  try {
    recoveryCodes.value = await regenerateRecoveryCodes()
    setFeedback('success', 'Códigos de recuperação regenerados com sucesso.')
  } catch (error: unknown) {
    if (getErrorStatus(error) === 423) {
      setFeedback('error', 'Sessão expirada. Inicie sessão novamente para continuar.')
      return
    }
    setFeedback('error', 'Não foi possível regenerar os códigos de recuperação.')
  } finally {
    actionLoading.value = false
  }
}
</script>

<template>
  <div class="page">
    <header class="page-header">
      <h1>Segurança do perfil</h1>
      <p class="page-subtitle">
        Gerencie autenticação de dois fatores e códigos de recuperação da sua conta.
      </p>
    </header>

    <Card class="security-card">
      <CardHeader>
        <CardTitle>Autenticação de dois fatores</CardTitle>
        <CardDescription
          >Adicione uma camada extra de segurança para o acesso à sua conta.</CardDescription
        >
      </CardHeader>
      <CardContent class="content">
        <p
          v-if="feedbackMessage"
          :class="feedbackKind === 'error' ? 'error' : 'success'"
          class="feedback"
        >
          {{ feedbackMessage }}
        </p>

        <div class="actions">
          <Button
            v-if="!isEnabled"
            :disabled="pageLoading || actionLoading"
            type="button"
            @click="onEnableTwoFactor"
          >
            {{ actionLoading ? 'A ativar...' : 'Ativar 2FA' }}
          </Button>
          <Button
            v-else
            variant="outline"
            :disabled="pageLoading || actionLoading"
            type="button"
            @click="onDisableTwoFactor"
          >
            {{ actionLoading ? 'A desativar...' : 'Desativar 2FA' }}
          </Button>
        </div>

        <div v-if="isEnabled" class="two-factor-area">
          <div class="qr-section">
            <h2>QR Code</h2>
            <p>Escaneie este QR code com a aplicação autenticadora.</p>
            <div class="qr-wrapper" v-html="qrCodeSvg" />
          </div>

          <form v-if="!isTwoFactorConfirmed" class="confirm-form" @submit="onConfirmCode">
            <FormField v-slot="{ componentField }" name="code">
              <FormItem>
                <FormLabel>Código de confirmação</FormLabel>
                <FormControl>
                  <Input
                    v-bind="componentField"
                    autocomplete="one-time-code"
                    inputmode="numeric"
                    :disabled="pageLoading || actionLoading"
                    placeholder="123456"
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>
            <Button type="submit" :disabled="pageLoading || actionLoading">
              {{ actionLoading ? 'A confirmar...' : 'Confirmar' }}
            </Button>
          </form>

          <div class="recovery-section">
            <div class="recovery-header">
              <h2>Códigos de recuperação</h2>
              <Button
                type="button"
                variant="outline"
                :disabled="pageLoading || actionLoading || recoveryCodes.length === 0"
                @click="onRegenerateRecoveryCodes"
              >
                {{ actionLoading ? 'A regenerar...' : 'Regenerar códigos' }}
              </Button>
            </div>

            <ul v-if="recoveryCodes.length" class="recovery-list">
              <li v-for="code in recoveryCodes" :key="code">{{ code }}</li>
            </ul>
            <p v-else class="recovery-empty">Nenhum código de recuperação disponível.</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <FormModal
      v-model:open="confirmPasswordModalOpen"
      title="Confirmar palavra-passe"
      size="compact"
    >
      <p class="confirm-password-hint">
        Por segurança, confirme a sua palavra-passe para ativar a autenticação de dois fatores.
      </p>
      <form class="confirm-password-form" @submit.prevent="onConfirmPasswordAndEnable">
        <label class="password-label" for="confirm-password-input">Palavra-passe</label>
        <Input
          id="confirm-password-input"
          v-model="confirmPasswordValue"
          type="password"
          autocomplete="current-password"
          :disabled="actionLoading"
          placeholder="Insira a sua palavra-passe"
        />
        <p v-if="confirmPasswordFieldError" class="field-error">{{ confirmPasswordFieldError }}</p>
        <div class="confirm-password-actions">
          <Button
            type="button"
            variant="outline"
            :disabled="actionLoading"
            @click="closeConfirmPasswordModal"
          >
            Cancelar
          </Button>
          <Button type="submit" :disabled="actionLoading">
            {{ actionLoading ? 'A confirmar...' : 'Confirmar e ativar 2FA' }}
          </Button>
        </div>
      </form>
    </FormModal>
  </div>
</template>

<style scoped>
.page {
  margin: 0 auto;
  max-width: 52rem;
  padding: 0.25rem 0;
}

.page-header {
  margin-bottom: 1rem;
}

h1 {
  margin: 0;
  text-align: left;
}

.page-subtitle {
  margin: 0.35rem 0 0;
  font-size: 0.92rem;
  color: hsl(var(--muted-foreground));
}

.security-card {
  border-radius: 0.85rem;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.content {
  display: grid;
  gap: 1.1rem;
}

.feedback {
  margin: 0;
  font-size: 0.9rem;
}

.success {
  color: #166534;
}

.error {
  color: #b91c1c;
}

.actions {
  display: flex;
  gap: 0.75rem;
  padding-top: 0.1rem;
}

.two-factor-area {
  display: grid;
  gap: 1.1rem;
  padding-top: 0.9rem;
  border-top: 1px solid hsl(var(--border));
}

.qr-section h2,
.recovery-section h2 {
  margin: 0 0 0.25rem;
  font-size: 1rem;
}

.qr-section p {
  margin: 0 0 0.75rem;
  color: hsl(var(--muted-foreground));
}

.qr-wrapper {
  width: fit-content;
  border: 1px solid hsl(var(--border));
  border-radius: 0.5rem;
  padding: 0.75rem;
  background: #fff;
}

.confirm-form {
  display: grid;
  gap: 0.75rem;
  max-width: 20rem;
  padding: 0.9rem;
  border: 1px solid hsl(var(--border));
  border-radius: 0.6rem;
  background: hsl(var(--muted) / 0.2);
}

.recovery-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.65rem;
}

.recovery-list {
  margin: 0;
  padding: 0.75rem;
  list-style: none;
  border: 1px solid hsl(var(--border));
  border-radius: 0.5rem;
  background-color: hsl(var(--muted) / 0.35);
  display: grid;
  gap: 0.35rem;
}

.recovery-list li {
  font-family:
    ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New',
    monospace;
  font-size: 0.85rem;
}

.recovery-empty {
  margin: 0;
  color: hsl(var(--muted-foreground));
}

.confirm-password-form {
  display: grid;
  gap: 0.9rem;
  border-top: 1px solid hsl(var(--border));
  padding-top: 0.9rem;
}

.confirm-password-hint {
  margin: 0 0 0.15rem;
  font-size: 0.88rem;
  color: hsl(var(--muted-foreground));
}

.confirm-password-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 0.2rem;
}

.field-error {
  margin: 0.35rem 0 0;
  font-size: 0.8rem;
  color: #b91c1c;
}

.password-label {
  font-size: 0.88rem;
  font-weight: 500;
  color: hsl(var(--foreground));
}
</style>
