<script setup lang="ts">
import OrderCommercialFlowNote from '@/modules/orders/components/OrderCommercialFlowNote.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Encomendas — Fornecedores</h1>
    <DocSection title="Geração">
      <p>Agrupamento automático por fornecedor a partir de encomendas de cliente fechadas.</p>
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
