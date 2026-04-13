<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import EntitiesTable from '@/modules/entities/components/EntitiesTable.vue'
import EntityTypeHint from '@/modules/entities/components/EntityTypeHint.vue'

const route = useRoute()

const listQuery = computed(() => ({
  clients: route.query.clients === '1',
  suppliers: route.query.suppliers === '1',
}))

const title = computed(() => {
  if (listQuery.value.clients) {
    return 'Clientes'
  }
  if (listQuery.value.suppliers) {
    return 'Fornecedores'
  }
  return 'Entidades'
})
</script>

<template>
  <div>
    <h1>{{ title }}</h1>
    <EntityTypeHint />
    <p class="actions">
      <RouterLink to="/entities/new">Nova entidade</RouterLink>
      ·
      <RouterLink to="/contacts">Contactos</RouterLink>
    </p>
    <EntitiesTable />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}

.actions {
  text-align: left;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}
</style>
