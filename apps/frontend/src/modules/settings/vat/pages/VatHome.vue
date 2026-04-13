<script setup lang="ts">
import VatTable from '@/modules/settings/vat/components/VatTable.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>IVA</h1>
    <DocSection title="Âmbito do módulo">
      <p>Gestão de taxas de IVA para utilização no catálogo de artigos e fluxos comerciais.</p>
    </DocSection>
    <VatTable />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
