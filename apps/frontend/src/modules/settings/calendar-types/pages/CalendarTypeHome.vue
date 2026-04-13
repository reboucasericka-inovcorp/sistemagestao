<script setup lang="ts">
import CalendarTypeTable from '@/modules/settings/calendar-types/components/CalendarTypeTable.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Tipos de calendário</h1>
    <DocSection title="Âmbito do módulo">
      <p>Gestão dos tipos de evento usados no calendário.</p>
    </DocSection>
    <CalendarTypeTable />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
