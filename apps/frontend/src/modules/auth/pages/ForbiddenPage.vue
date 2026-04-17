<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

const route = useRoute()

/** Apenas caminhos internos relativos (evita open redirect) */
const safeReturnTo = computed((): string | null => {
  const raw = route.query.from
  if (typeof raw !== 'string' || raw === '') {
    return null
  }
  if (!raw.startsWith('/') || raw.startsWith('//')) {
    return null
  }
  return raw
})
</script>

<template>
  <div class="forbidden">
    <h1>403 — Acesso negado</h1>
    <p>Não tem permissão para aceder a esta página.</p>
    <div class="actions">
      <RouterLink v-if="safeReturnTo" class="link" :to="safeReturnTo">Voltar à página anterior</RouterLink>
      <RouterLink class="link" to="/clients">Ir para o início</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.forbidden {
  min-height: 100svh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1.5rem;
  text-align: center;
  background: hsl(var(--background, 210 40% 98%));
  color: hsl(var(--foreground, 222 47% 11%));
}

h1 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
}

p {
  margin: 0;
  color: hsl(var(--muted-foreground, 215 16% 47%));
}

.actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.link {
  color: hsl(var(--primary, 221 83% 53%));
  text-decoration: underline;
}
</style>
