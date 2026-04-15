<script setup lang="ts">
import type { Entity } from '../types/entity'

defineProps<{
  entity: Entity
}>()
</script>

<template>
  <li class="row">
    <span class="name">{{ entity.name }}</span>
    <span class="meta">
      {{ entity.is_client ? 'Cliente' : '' }}{{ entity.is_client && entity.is_supplier ? ' · ' : '' }}{{ entity.is_supplier ? 'Fornecedor' : '' }}
    </span>
  </li>
</template>

<style scoped>
.row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.35rem 0;
  border-bottom: 1px solid var(--border);
}

.name {
  color: var(--text-h);
}

.meta {
  font-size: 0.85rem;
  color: var(--text);
}
</style>
