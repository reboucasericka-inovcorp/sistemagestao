<script setup lang="ts">
import { onMounted, ref } from 'vue'
import ContactListRow from '@/modules/contacts/components/ContactListRow.vue'
import { listContacts } from '@/modules/contacts/services/contactService'
import type { Contact } from '@/modules/contacts/types/contact'

const contacts = ref<Contact[]>([])

const load = async () => {
  contacts.value = await listContacts()
}

onMounted(() => {
  void load()
})
</script>

<template>
  <div>
    <h1>Contactos</h1>
    <p class="actions">
      <RouterLink to="/contacts/new">Novo contacto</RouterLink>
      ·
      <RouterLink to="/entities">Entidades</RouterLink>
    </p>
    <p class="hint">Relacionados com entidades; função configurável (ver `docs/guide.md`).</p>
    <ul class="list">
      <ContactListRow v-for="c in contacts" :key="c.id" :contact="c" />
    </ul>
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

.hint {
  text-align: left;
  font-size: 0.85rem;
  color: var(--text);
  margin-bottom: 1rem;
}

.list {
  list-style: none;
  padding: 0;
  margin: 0;
  max-width: 40rem;
}
</style>
