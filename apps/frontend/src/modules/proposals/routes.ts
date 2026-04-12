import type { RouteRecordRaw } from 'vue-router'

import ProposalsHome from '@/modules/proposals/pages/ProposalsHome.vue'

export const proposalsRoutes: RouteRecordRaw[] = [
  { path: 'proposals', name: 'proposals.home', component: ProposalsHome },
]
