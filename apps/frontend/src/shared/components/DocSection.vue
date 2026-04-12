<script setup lang="ts">
defineProps<{
  title: string
}>()
</script>

<template>
  <section class="doc-section">
    <h2>{{ title }}</h2>
    <div class="body">
      <slot />
    </div>
  </section>
</template>

<style scoped>
.doc-section {
  margin-top: 1.5rem;
  text-align: left;
  max-width: 48rem;
}

h2 {
  font-size: 1.05rem;
  margin: 0 0 0.5rem;
  color: var(--text-h);
}

.body {
  font-size: 0.9rem;
  color: var(--text);
  line-height: 1.45;
}

.body :deep(ul) {
  margin: 0.35rem 0 0;
  padding-left: 1.25rem;
}
</style>
