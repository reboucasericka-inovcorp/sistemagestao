<script setup lang="ts">
import EntitiesTable from '@/modules/entities/components/EntitiesTable.vue'
</script>

<template>
  <div>
    <h1>Entidades</h1>
    <p class="hint">Visão administrativa da base única de entidades.</p>
    <p class="actions">
      <RouterLink to="/contacts">Contactos</RouterLink>
    </p>
    <EntitiesTable fixed-filter="all" :show-create-action="false" />
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

.hint {
  margin: 0 0 0.35rem;
  font-size: 0.85rem;
  color: hsl(var(--muted-foreground));
}
</style>
