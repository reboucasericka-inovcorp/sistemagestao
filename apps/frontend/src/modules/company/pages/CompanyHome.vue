<script setup lang="ts">
import CompanyBrandingNote from '@/modules/company/components/CompanyBrandingNote.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Empresa</h1>
    <DocSection title="Campos">
      <ul>
        <li>Logotipo, nome, morada, código postal, localidade, número de contribuinte</li>
      </ul>
    </DocSection>
    <CompanyBrandingNote />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
