import type { RouteRecordRaw } from 'vue-router'

import EntitiesList from '@/modules/entities/pages/EntitiesList.vue'
import ClientsPage from '@/modules/entities/pages/ClientsPage.vue'
import EntityForm from '@/modules/entities/pages/EntityForm.vue'
import SuppliersPage from '@/modules/entities/pages/SuppliersPage.vue'

export const entityRoutes: RouteRecordRaw[] = [
  { path: 'clients', name: 'clients.list', component: ClientsPage },
  { path: 'clients/new', name: 'clients.new', component: EntityForm },
  { path: 'clients/:id(\\d+)/edit', name: 'clients.edit', component: EntityForm },
  { path: 'suppliers', name: 'suppliers.list', component: SuppliersPage },
  { path: 'suppliers/new', name: 'suppliers.new', component: EntityForm },
  { path: 'suppliers/:id(\\d+)/edit', name: 'suppliers.edit', component: EntityForm },
  { path: 'entities', name: 'entities.list', component: EntitiesList, meta: { backofficeOnly: true } },
  { path: 'entities/new', name: 'entities.new', component: EntityForm, meta: { backofficeOnly: true } },
  { path: 'entities/:id(\\d+)/edit', name: 'entities.edit', component: EntityForm },
]
