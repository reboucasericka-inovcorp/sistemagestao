<script setup lang="ts">
import AuditColumnsNote from '@/modules/logs/components/AuditColumnsNote.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Logs</h1>
    <DocSection title="Âmbito">
      <p>Trilha de auditoria obrigatória para ações sensíveis; ver requisitos em <code>docs/README.md</code>.</p>
    </DocSection>
    <AuditColumnsNote />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
