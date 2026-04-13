<script setup lang="ts">
import ContactsTable from '@/modules/contacts/components/ContactsTable.vue'
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
    <ContactsTable />
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
</style>
