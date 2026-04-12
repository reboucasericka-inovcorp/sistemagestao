<script setup lang="ts">
import ContactStatusBadge from '@/modules/contacts/components/ContactStatusBadge.vue'
import type { Contact } from '@/modules/contacts/types/contact'

defineProps<{
  contact: Contact
}>()
</script>

<template>
  <li class="row">
    <div>
      <span class="name">{{ contact.name }}</span>
      <span class="sub">Entidade #{{ contact.entity_id }}</span>
    </div>
    <ContactStatusBadge :is-active="contact.is_active" />
  </li>
</template>

<style scoped>
.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding: 0.4rem 0;
  border-bottom: 1px solid var(--border);
}

.name {
  display: block;
  color: var(--text-h);
}

.sub {
  font-size: 0.8rem;
  color: var(--text);
}
</style>
