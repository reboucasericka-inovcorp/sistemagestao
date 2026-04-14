<script setup lang="ts">
import { ref } from 'vue'
import FormModal from '@/components/shared/FormModal.vue'
import ContactFunctionForm from '@/modules/settings/contact-functions/components/ContactFunctionForm.vue'
import ContactFunctionTable from '@/modules/settings/contact-functions/components/ContactFunctionTable.vue'
import DocSection from '@/shared/components/DocSection.vue'

const openCreate = ref(false)
const tableKey = ref(0)

function onCreateSuccess(): void {
  openCreate.value = false
  tableKey.value += 1
}
</script>

<template>
  <div>
    <h1>Funções de contacto</h1>

    <ContactFunctionTable :key="tableKey" @create="openCreate = true" />
    <FormModal v-model:open="openCreate" title="Nova função de contacto">
      <ContactFunctionForm mode="create" @cancel="openCreate = false" @success="onCreateSuccess" />
    </FormModal>
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
