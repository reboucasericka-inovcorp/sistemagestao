<script setup lang="ts">
import OrderCommercialFlowNote from '@/modules/orders/components/OrderCommercialFlowNote.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Encomendas — Clientes</h1>
    <DocSection title="Regras principais">
      <p>Criação direta ou via proposta; cliente apenas do tipo cliente; linhas com artigos pesquisáveis.</p>
    </DocSection>
    <OrderCommercialFlowNote />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
