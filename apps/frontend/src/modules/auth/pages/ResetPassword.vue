<script setup lang="ts">
import axios from 'axios'
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { resetPassword } from '@/modules/auth/services/authService'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const password = ref('')
const passwordConfirmation = ref('')
const errorMessage = ref('')
const successMessage = ref('')

const token = computed(() => String(route.query.token ?? ''))
const email = computed(() => String(route.query.email ?? ''))

const submit = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  if (!token.value || !email.value) {
    errorMessage.value = 'Link inválido. Solicite uma nova recuperação de senha.'
    loading.value = false
    return
  }

  try {
    await resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    successMessage.value = 'Senha definida com sucesso. Você já pode entrar.'
    setTimeout(() => void router.push('/login'), 900)
  } catch (e: unknown) {
    errorMessage.value = axios.isAxiosError(e)
      ? String((e.response?.data as { message?: unknown })?.message ?? e.message)
      : 'Não foi possível redefinir a senha.'
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
        <h1>Definir senha</h1>
        <p class="help">Defina sua nova senha para acessar o sistema.</p>

        <form class="form" @submit.prevent="submit">
          <input :value="email" type="email" class="input" disabled />
          <input
            v-model="password"
            type="password"
            class="input"
            placeholder="Nova senha"
            autocomplete="new-password"
            required
          />
          <input
            v-model="passwordConfirmation"
            type="password"
            class="input"
            placeholder="Confirmar nova senha"
            autocomplete="new-password"
            required
          />
          <button type="submit" class="submit" :disabled="loading">
            {{ loading ? 'A guardar...' : 'Definir senha' }}
          </button>
        </form>

        <p v-if="successMessage" class="success">{{ successMessage }}</p>
        <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
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
  color: #fff;
}

h1 {
  margin: 0 0 0.5rem;
  font-size: 1.3rem;
}

.help {
  margin: 0 0 1rem;
  color: #9ca3af;
  font-size: 0.9rem;
}

.form {
  display: grid;
  gap: 0.75rem;
}

.input {
  width: 100%;
  box-sizing: border-box;
  border-radius: 0.5rem;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(0, 0, 0, 0.35);
  color: #fff;
  padding: 0.625rem 1rem;
}

.submit {
  border: none;
  border-radius: 0.5rem;
  padding: 0.625rem 1rem;
  background: #2563eb;
  color: #fff;
  cursor: pointer;
}

.submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.success {
  color: #86efac;
  margin-top: 0.75rem;
}

.error {
  color: #fca5a5;
  margin-top: 0.75rem;
}
</style>
