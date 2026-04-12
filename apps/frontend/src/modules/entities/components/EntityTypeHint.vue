<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const hint = computed(() => {
  if (route.query.clients === '1') {
    return 'Filtro: clientes (mesma tabela-base de entidades; ver docs/guide.md §6).'
  }
  if (route.query.suppliers === '1') {
    return 'Filtro: fornecedores (mesma tabela-base de entidades).'
  }
  return 'Todas as entidades.'
})
</script>

<template>
  <p class="hint">{{ hint }}</p>
</template>

<style scoped>
.hint {
  margin: 0 0 1rem;
  font-size: 0.9rem;
  color: var(--text);
  text-align: left;
}
</style>
