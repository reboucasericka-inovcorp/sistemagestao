<script setup lang="ts">
import PermissionMatrixNote from '@/modules/permissions/components/PermissionMatrixNote.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Permissões</h1>
    <DocSection title="Âmbito">
      <p>Configuração de grupos e matriz de permissões alinhada ao modelo ACL do projeto.</p>
    </DocSection>
    <PermissionMatrixNote />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
