<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import EntityRow from '@/modules/entities/components/EntityRow.vue'
import EntityTypeHint from '@/modules/entities/components/EntityTypeHint.vue'
import { listEntities } from '@/modules/entities/services/entityService'
import type { Entity } from '@/modules/entities/types/entity'

const route = useRoute()
const entities = ref<Entity[]>([])

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

const loadEntities = async () => {
  entities.value = await listEntities(listQuery.value)
}

onMounted(loadEntities)
watch(
  () => route.query,
  () => {
    void loadEntities()
  },
  { deep: true },
)
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
    <ul class="list">
      <EntityRow v-for="entity in entities" :key="entity.id" :entity="entity" />
    </ul>
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

.list {
  list-style: none;
  padding: 0;
  margin: 0;
  max-width: 40rem;
}
</style>
