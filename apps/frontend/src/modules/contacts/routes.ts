import type { RouteRecordRaw } from 'vue-router'

import ContactsList from '@/modules/contacts/pages/ContactsList.vue'
import ContactForm from '@/modules/contacts/pages/ContactForm.vue'

export const contactRoutes: RouteRecordRaw[] = [
  { path: 'contacts/new', name: 'contacts.new', component: ContactForm, meta: { requiresAuth: true, permission: 'contacts.create' } },
  { path: 'contacts/:id(\\d+)/edit', name: 'contacts.edit', component: ContactForm, meta: { requiresAuth: true, permission: 'contacts.update' } },
  { path: 'contacts', name: 'contacts.list', component: ContactsList, meta: { requiresAuth: true, permission: 'contacts.read' } },
]
