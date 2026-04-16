<script setup lang="ts">
import axios from 'axios'
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { sendPasswordResetLink } from '@/modules/auth/services/authService'

const email = ref('')
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const submit = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await sendPasswordResetLink(email.value.trim())
    successMessage.value = 'Se o email existir, enviamos um link para definir a senha.'
  } catch (e: unknown) {
    errorMessage.value = axios.isAxiosError(e)
      ? String((e.response?.data as { message?: unknown })?.message ?? e.message)
      : 'Não foi possível enviar o email de recuperação.'
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
        <h1>Recuperar senha</h1>
        <p class="help">Informe seu email para receber o link de definição de senha.</p>

        <form class="form" @submit.prevent="submit">
          <input v-model="email" type="email" placeholder="Email" class="input" autocomplete="email" required />
          <button type="submit" class="submit" :disabled="loading">
            {{ loading ? 'Enviando...' : 'Enviar link' }}
          </button>
        </form>

        <p v-if="successMessage" class="success">{{ successMessage }}</p>
        <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
        <RouterLink to="/login" class="back-link">Voltar ao login</RouterLink>
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

.back-link {
  display: inline-block;
  margin-top: 1rem;
  color: #93c5fd;
}
</style>
