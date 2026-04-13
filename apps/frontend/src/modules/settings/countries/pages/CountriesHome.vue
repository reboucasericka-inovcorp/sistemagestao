<script setup lang="ts">
import CountryTable from '@/modules/settings/countries/components/CountryTable.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Países</h1>
    <DocSection title="Âmbito do módulo">
      <p>Gestão de países para utilização em registos e configurações auxiliares do sistema.</p>
    </DocSection>
    <CountryTable />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
