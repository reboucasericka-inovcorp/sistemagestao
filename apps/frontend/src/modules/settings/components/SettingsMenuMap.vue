<script setup lang="ts">
import { settingsMenuSections } from '@/modules/settings/services/settingsMenuRegistry'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <DocSection title="Mapa de configurações (docs/README.md)">
    <ul>
      <li v-for="s in settingsMenuSections" :key="s.id">{{ s.label }}</li>
    </ul>
  </DocSection>
</template>
