<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { RouteLocationRaw } from 'vue-router'

defineProps<{
  to: RouteLocationRaw
}>()
</script>

<template>
  <RouterLink class="nav-link" :to="to">
    <slot />
  </RouterLink>
</template>

<style scoped>
.nav-link {
  display: block;
  padding: 0.35rem 0.5rem;
  border-radius: 4px;
  color: var(--text-h);
  text-decoration: none;
  font-size: 0.9rem;
}

.nav-link:hover {
  background: var(--accent-bg);
}

:global(.router-link-active.nav-link) {
  font-weight: 600;
  background: var(--accent-bg);
}
</style>
