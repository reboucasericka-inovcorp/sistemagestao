<script setup lang="ts">
import CatalogFieldList from '@/modules/articles/components/CatalogFieldList.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Artigos</h1>
    <DocSection title="Âmbito do módulo">
      <p>
        Catálogo de artigos com referência, preços e IVA, conforme fluxo comercial descrito em
        <code>docs/README.md</code> (Propostas e Encomendas usam linhas pesquisáveis por referência/nome).
      </p>
    </DocSection>
    <CatalogFieldList />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
