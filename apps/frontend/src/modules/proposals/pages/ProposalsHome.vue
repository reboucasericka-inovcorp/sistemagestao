<script setup lang="ts">
import ProposalRulesNote from '@/modules/proposals/components/ProposalRulesNote.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Propostas</h1>
    <DocSection title="Ciclo comercial">
      <p>
        Integra com artigos e encomendas de cliente; estados e PDFs descritos em
        <code>docs/README.md</code> (secção Propostas).
      </p>
    </DocSection>
    <ProposalRulesNote />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
