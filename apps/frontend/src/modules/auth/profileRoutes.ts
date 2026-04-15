import type { RouteRecordRaw } from 'vue-router'

import ProfileSecurity from '@/modules/auth/pages/ProfileSecurity.vue'

export const authProfileRoutes: RouteRecordRaw[] = [
  { path: 'profile/security', name: 'profile.security', component: ProfileSecurity },
]
