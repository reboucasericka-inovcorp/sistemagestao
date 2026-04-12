<script setup lang="ts">
import SettingsMenuMap from '@/modules/settings/components/SettingsMenuMap.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Configurações</h1>
    <DocSection title="Função do módulo">
      <p>Tabelas auxiliares e parâmetros globais; cada subárea pode evoluir para sub-rota dedicada.</p>
    </DocSection>
    <SettingsMenuMap />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
