import type { RouteRecordRaw } from 'vue-router'

import ContactsList from '@/modules/contacts/pages/ContactsList.vue'
import ContactForm from '@/modules/contacts/pages/ContactForm.vue'

export const contactRoutes: RouteRecordRaw[] = [
  { path: 'contacts/new', name: 'contacts.new', component: ContactForm },
  { path: 'contacts/:id(\\d+)/edit', name: 'contacts.edit', component: ContactForm },
  { path: 'contacts', name: 'contacts.list', component: ContactsList },
]
