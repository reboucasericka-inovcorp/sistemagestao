<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCompany } from '@/core/company/useCompany'
import {
  completeTwoFactorLogin,
  fetchSanctumCsrfCookie,
  loginWithCredentials,
} from '@/modules/auth/services/authService'

const router = useRouter()
const { company, loadCompany } = useCompany()

const email = ref('')
const password = ref('')
const twoFactorCode = ref('')
const recoveryCode = ref('')
const useRecoveryCode = ref(false)
const requiresTwoFactor = ref(false)
const errorMessage = ref('')
const loading = ref(false)
const companyName = computed(() => company.value?.name || '')
const companyLogoUrl = computed(() => company.value?.logo_url || null)

const login = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    await fetchSanctumCsrfCookie()
    const loginResult = await loginWithCredentials({ email: email.value, password: password.value })
    if (loginResult.two_factor) {
      requiresTwoFactor.value = true
      return
    }
    router.push('/clients')
  } catch (e: unknown) {
    errorMessage.value = axios.isAxiosError(e)
      ? String((e.response?.data as { message?: unknown })?.message ?? e.message)
      : 'Erro no login'
  } finally {
    loading.value = false
  }
}

const submitTwoFactor = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    await fetchSanctumCsrfCookie()
    await completeTwoFactorLogin(
      useRecoveryCode.value
        ? { recovery_code: recoveryCode.value.trim() }
        : { code: twoFactorCode.value.trim() },
    )
    router.push('/clients')
  } catch (e: unknown) {
    const status = axios.isAxiosError(e) ? e.response?.status : undefined
    if (status === 422) {
      errorMessage.value = useRecoveryCode.value
        ? 'Código de recuperação inválido.'
        : 'Código de autenticação inválido.'
      return
    }
    errorMessage.value = axios.isAxiosError(e)
      ? String((e.response?.data as { message?: unknown })?.message ?? e.message)
      : 'Erro na validação de dois fatores'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadCompany()
})
</script>

<template>
  <div class="login-page">
    <div class="login-bg" aria-hidden="true" />
    <div class="login-overlay" aria-hidden="true" />

    <div class="login-card-wrap">
      <div class="login-card">
        <div class="login-brand">
          <img v-if="companyLogoUrl" :src="companyLogoUrl" alt="Logótipo da empresa" class="login-brand-logo" />
          <h1 v-if="companyName" class="login-brand-title">{{ companyName }}</h1>
          <p class="login-brand-tagline">Acesso ao sistema</p>
        </div>

        <h2 class="login-form-title">{{ requiresTwoFactor ? 'Autenticação de dois fatores' : 'Login' }}</h2>

        <form v-if="!requiresTwoFactor" class="login-form" @submit.prevent="login">
          <label class="sr-only" for="login-email">Email</label>
          <input
            id="login-email"
            v-model="email"
            type="email"
            autocomplete="email"
            placeholder="Email"
            class="login-input"
          />

          <label class="sr-only" for="login-password">Palavra-passe</label>
          <input
            id="login-password"
            v-model="password"
            type="password"
            autocomplete="current-password"
            placeholder="Palavra-passe"
            class="login-input"
          />

          <RouterLink to="/forgot-password" class="forgot-password-link">Esqueci a senha</RouterLink>

          <button type="submit" class="login-submit" :disabled="loading">
            {{ loading ? 'Entrando...' : 'Entrar' }}
          </button>
        </form>

        <form v-else class="login-form" @submit.prevent="submitTwoFactor">
          <p class="login-help">
            Introduza o código da aplicação autenticadora ou utilize um código de recuperação.
          </p>

          <label class="recovery-toggle">
            <input v-model="useRecoveryCode" type="checkbox" />
            Usar código de recuperação
          </label>

          <label class="sr-only" for="two-factor-code">Código 2FA</label>
          <input
            v-if="!useRecoveryCode"
            id="two-factor-code"
            v-model="twoFactorCode"
            type="text"
            autocomplete="one-time-code"
            placeholder="Código do autenticador"
            class="login-input"
          />

          <label class="sr-only" for="recovery-code">Código de recuperação</label>
          <input
            v-if="useRecoveryCode"
            id="recovery-code"
            v-model="recoveryCode"
            type="text"
            placeholder="Código de recuperação"
            class="login-input"
          />

          <button type="submit" class="login-submit" :disabled="loading">
            {{ loading ? 'Validando...' : 'Validar e entrar' }}
          </button>
        </form>

        <p v-if="errorMessage" class="login-error">{{ errorMessage }}</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Este projeto não usa Tailwind: o layout depende deste CSS. */

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.login-page {
  position: relative;
  flex: 1;
  width: 100%;
  min-height: 100dvh;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  box-sizing: border-box;
}

.login-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  background-color: #0f1115;
  background-image: url('/sistema_fundo.png');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.login-overlay {
  position: fixed;
  inset: 0;
  z-index: 1;
  background: rgba(0, 0, 0, 0.55);
}

.login-card-wrap {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 24rem;
}

.login-card {
  border-radius: 1rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(3, 7, 18, 0.78);
  padding: 2rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  text-align: center;
}

.login-brand {
  margin-bottom: 1.5rem;
}

.login-brand-logo {
  width: 4rem;
  height: 4rem;
  margin: 0 auto 0.75rem;
  border-radius: 0.75rem;
  object-fit: cover;
}

.login-brand-title {
  margin: 0;
  font-size: 1.875rem;
  line-height: 1.2;
  font-weight: 700;
  color: #fff;
  letter-spacing: -0.02em;
}

.login-brand-tagline {
  margin: 0.25rem 0 0;
  font-size: 0.875rem;
  line-height: 1.4;
  color: #9ca3af;
}

.login-form-title {
  margin: 0 0 1.5rem;
  font-size: 1.25rem;
  line-height: 1.3;
  font-weight: 600;
  color: #fff;
  letter-spacing: -0.01em;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  text-align: left;
}

.login-input {
  width: 100%;
  box-sizing: border-box;
  border-radius: 0.5rem;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(0, 0, 0, 0.35);
  padding: 0.625rem 1rem;
  font: inherit;
  font-size: 1rem;
  line-height: 1.4;
  color: #fff;
}

.login-input::placeholder {
  color: #6b7280;
}

.login-input:focus {
  outline: none;
  border-color: rgba(59, 130, 246, 0.55);
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
}

.login-submit {
  width: 100%;
  margin-top: 0.25rem;
  border: none;
  border-radius: 0.5rem;
  padding: 0.625rem 1rem;
  font: inherit;
  font-size: 1rem;
  font-weight: 500;
  color: #fff;
  background: #2563eb;
  cursor: pointer;
  transition: background 0.15s ease;
}

.login-submit:hover:not(:disabled) {
  background: #1d4ed8;
}

.login-submit:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.forgot-password-link {
  color: #93c5fd;
  font-size: 0.85rem;
  text-decoration: none;
}

.forgot-password-link:hover {
  text-decoration: underline;
}

.login-help {
  margin: 0;
  color: #9ca3af;
  font-size: 0.85rem;
}

.recovery-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #d1d5db;
  font-size: 0.85rem;
}

.login-error {
  margin-top: 0.9rem;
  color: #fca5a5;
  font-size: 0.88rem;
}
</style>
