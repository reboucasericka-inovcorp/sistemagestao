<script setup lang="ts">
import WorkOrdersTable from '@/modules/work-orders/components/WorkOrdersTable.vue'
</script>

<template>
  <div>
    <h1>Ordens de Trabalho</h1>
    <WorkOrdersTable />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
  margin-bottom: 1rem;
}
</style>
