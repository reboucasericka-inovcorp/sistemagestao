import type { RouteRecordRaw } from 'vue-router'

import EntitiesList from '@/modules/entities/pages/EntitiesList.vue'
import ClientsPage from '@/modules/entities/pages/ClientsPage.vue'
import EntityForm from '@/modules/entities/pages/EntityForm.vue'
import SuppliersPage from '@/modules/entities/pages/SuppliersPage.vue'

export const entityRoutes: RouteRecordRaw[] = [
  { path: 'clients', name: 'clients.list', component: ClientsPage, meta: { requiresAuth: true, permission: 'entities.read' } },
  { path: 'clients/new', name: 'clients.new', component: EntityForm, meta: { requiresAuth: true, permission: 'entities.create' } },
  { path: 'clients/:id(\\d+)/edit', name: 'clients.edit', component: EntityForm, meta: { requiresAuth: true, permission: 'entities.update' } },
  { path: 'suppliers', name: 'suppliers.list', component: SuppliersPage, meta: { requiresAuth: true, permission: 'entities.read' } },
  { path: 'suppliers/new', name: 'suppliers.new', component: EntityForm, meta: { requiresAuth: true, permission: 'entities.create' } },
  { path: 'suppliers/:id(\\d+)/edit', name: 'suppliers.edit', component: EntityForm, meta: { requiresAuth: true, permission: 'entities.update' } },
  { path: 'entities', name: 'entities.list', component: EntitiesList, meta: { requiresAuth: true, permission: 'entities.read', backofficeOnly: true } },
  { path: 'entities/new', name: 'entities.new', component: EntityForm, meta: { requiresAuth: true, permission: 'entities.create', backofficeOnly: true } },
  { path: 'entities/:id(\\d+)/edit', name: 'entities.edit', component: EntityForm, meta: { requiresAuth: true, permission: 'entities.update' } },
]
