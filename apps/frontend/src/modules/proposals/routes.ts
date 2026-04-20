import type { RouteRecordRaw } from 'vue-router'

import ProposalForm from '@/modules/proposals/components/ProposalForm.vue'
import ProposalsPage from '@/modules/proposals/pages/ProposalsPage.vue'

export const proposalsRoutes: RouteRecordRaw[] = [
  { path: 'proposals', name: 'proposals.index', component: ProposalsPage, meta: { requiresAuth: true, permission: 'proposals.read' } },
  { path: 'proposals/create', name: 'proposals.create', component: ProposalForm, meta: { requiresAuth: true, permission: 'proposals.create' } },
  { path: 'proposals/:id(\\d+)/edit', name: 'proposals.edit', component: ProposalForm, meta: { requiresAuth: true, permission: 'proposals.update' } },
]
