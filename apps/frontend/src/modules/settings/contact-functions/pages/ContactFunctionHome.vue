<script setup lang="ts">
import ContactFunctionTable from '@/modules/settings/contact-functions/components/ContactFunctionTable.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Funções de contacto</h1>
    <DocSection title="Âmbito do módulo">
      <p>Gestão de funções utilizadas no cadastro de contactos.</p>
    </DocSection>
    <ContactFunctionTable />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
