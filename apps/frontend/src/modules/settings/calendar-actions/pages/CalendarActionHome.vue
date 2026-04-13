<script setup lang="ts">
import CalendarActionTable from '@/modules/settings/calendar-actions/components/CalendarActionTable.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Ações de calendário</h1>
    <DocSection title="Âmbito do módulo">
      <p>Gestão de ações usadas nos eventos do calendário.</p>
    </DocSection>
    <CalendarActionTable />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
