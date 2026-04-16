import type { RouteRecordRaw } from 'vue-router'

import ProposalForm from '@/modules/proposals/components/ProposalForm.vue'
import ProposalsPage from '@/modules/proposals/pages/ProposalsPage.vue'

export const proposalsRoutes: RouteRecordRaw[] = [
  { path: 'proposals', name: 'proposals.index', component: ProposalsPage },
  { path: 'proposals/create', name: 'proposals.create', component: ProposalForm },
  { path: 'proposals/:id(\\d+)/edit', name: 'proposals.edit', component: ProposalForm },
]
