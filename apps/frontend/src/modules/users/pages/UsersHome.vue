<script setup lang="ts">
import UserAccessFieldsNote from '@/modules/users/components/UserAccessFieldsNote.vue'
import DocSection from '@/shared/components/DocSection.vue'
</script>

<template>
  <div>
    <h1>Utilizadores</h1>
    <DocSection title="Âmbito">
      <p>Módulo de Gestão de Acessos; alinhado a <code>docs/guide.md</code> e fluxos ACL.</p>
    </DocSection>
    <UserAccessFieldsNote />
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
