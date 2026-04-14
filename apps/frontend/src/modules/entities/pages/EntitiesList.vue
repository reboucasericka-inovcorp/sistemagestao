<script setup lang="ts">
import EntitiesTable from '@/modules/entities/components/EntitiesTable.vue'
</script>

<template>
  <div>
    <h1>Entidades</h1>
    <p class="actions">
      <RouterLink to="/entities/new">Nova entidade</RouterLink>
      ·
      <RouterLink to="/contacts">Contactos</RouterLink>
    </p>
    <EntitiesTable fixed-type="all" create-path="/entities/new" />
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
