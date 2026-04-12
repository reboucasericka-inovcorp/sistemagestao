<script setup lang="ts">
defineProps<{
  isActive: boolean
}>()
</script>

<template>
  <span class="badge" :data-active="isActive">{{ isActive ? 'Ativo' : 'Inativo' }}</span>
</template>

<style scoped>
.badge {
  font-size: 0.75rem;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  border: 1px solid var(--border);
  color: var(--text);
}

.badge[data-active='true'] {
  border-color: var(--accent-border);
  color: var(--text-h);
  background: var(--accent-bg);
}
</style>
