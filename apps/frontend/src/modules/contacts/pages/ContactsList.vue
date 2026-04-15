<script setup lang="ts">
import { ref } from 'vue'
import FormModal from '@/components/shared/FormModal.vue'
import ContactFormSection from '@/modules/contacts/components/ContactForm.vue'
import ContactsTable from '@/modules/contacts/components/ContactsTable.vue'

const openCreate = ref(false)
const tableKey = ref(0)

function onCreateSuccess(): void {
  openCreate.value = false
  tableKey.value += 1
}
</script>

<template>
  <div>
    <h1>Contactos</h1>
    <h2>“Cada contato pertence a uma entidade.”</h2>
    <p class="actions"></p>

    <ContactsTable :key="tableKey" @create="openCreate = true" />
    <FormModal v-model:open="openCreate" title="Novo contacto">
      <ContactFormSection mode="create" @cancel="openCreate = false" @success="onCreateSuccess" />
    </FormModal>
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}

.actions {
  text-align: left;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.link-button {
  background: transparent;
  border: 0;
  padding: 0;
  color: hsl(var(--primary));
  cursor: pointer;
}

.hint {
  text-align: left;
  font-size: 0.85rem;
  color: var(--text);
  margin-bottom: 1rem;
}
</style>
