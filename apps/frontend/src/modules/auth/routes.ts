import type { RouteRecordRaw } from 'vue-router'

import Login from '@/modules/auth/pages/Login.vue'

export const authRoutes: RouteRecordRaw[] = [{ path: '/login', name: 'login', component: Login }]
