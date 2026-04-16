<script setup lang="ts">
import ClientOrdersTable from '@/modules/client-orders/components/ClientOrdersTable.vue'
</script>

<template>
  <div>
    <h1>Encomendas de Clientes</h1>
    <ClientOrdersTable />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
  margin-bottom: 1rem;
}
</style>
