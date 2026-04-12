<script setup lang="ts">
import axios from 'axios'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { fetchSanctumCsrfCookie, loginWithCredentials } from '@/modules/auth/services/authService'

const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)

const login = async () => {
  loading.value = true
  try {
    await fetchSanctumCsrfCookie()
    await loginWithCredentials({ email: email.value, password: password.value })
    router.push('/entities')
  } catch (e: unknown) {
    const msg = axios.isAxiosError(e)
      ? String((e.response?.data as { message?: unknown })?.message ?? e.message)
      : 'Erro no login'
    alert(msg || 'Erro no login')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <div class="login-bg" aria-hidden="true" />
    <div class="login-overlay" aria-hidden="true" />

    <div class="login-card-wrap">
      <div class="login-card">
        <div class="login-brand">
          <h1 class="login-brand-title">Sistema de Gestão</h1>
          <p class="login-brand-tagline">Acesso ao sistema</p>
        </div>

        <h2 class="login-form-title">Login</h2>

        <form class="login-form" @submit.prevent="login">
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

          <button type="submit" class="login-submit" :disabled="loading">
            {{ loading ? 'Entrando...' : 'Entrar' }}
          </button>
        </form>
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
</style>
