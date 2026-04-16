import type { RouteRecordRaw } from 'vue-router'

import ForgotPassword from '@/modules/auth/pages/ForgotPassword.vue'
import Login from '@/modules/auth/pages/Login.vue'
import ResetPassword from '@/modules/auth/pages/ResetPassword.vue'

export const authRoutes: RouteRecordRaw[] = [
  { path: '/login', name: 'login', component: Login },
  { path: '/forgot-password', name: 'forgot-password', component: ForgotPassword },
  { path: '/reset-password', name: 'reset-password', component: ResetPassword },
]
