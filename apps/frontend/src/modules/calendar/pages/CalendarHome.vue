<script setup lang="ts">
import CalendarStackNote from '@/modules/calendar/components/CalendarStackNote.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Calendário</h1>
    <DocSection title="Campos">
      <ul>
        <li>Data, hora, duração, entidade, tipo, ação, descrição, estado</li>
        <li>Partilha / conhecimento conforme especificação funcional</li>
      </ul>
    </DocSection>
    <CalendarStackNote />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
