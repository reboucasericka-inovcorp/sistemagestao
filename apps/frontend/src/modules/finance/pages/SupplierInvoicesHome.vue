<script setup lang="ts">
import SupplierInvoiceRulesNote from '@/modules/finance/components/SupplierInvoiceRulesNote.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Faturas — Fornecedor</h1>
    <DocSection title="Estados">
      <p><code>pending</code> → <code>paid</code>; estado pago tratado como terminal (com auditoria se override).</p>
    </DocSection>
    <SupplierInvoiceRulesNote />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
