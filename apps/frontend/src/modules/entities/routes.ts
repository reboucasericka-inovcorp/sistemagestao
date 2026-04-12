import type { RouteRecordRaw } from 'vue-router'

import EntitiesList from '@/modules/entities/pages/EntitiesList.vue'
import EntityForm from '@/modules/entities/pages/EntityForm.vue'

export const entityRoutes: RouteRecordRaw[] = [
  { path: 'entities/new', name: 'entities.new', component: EntityForm },
  { path: 'entities/:id(\\d+)/edit', name: 'entities.edit', component: EntityForm },
  { path: 'entities', name: 'entities.list', component: EntitiesList },
]
