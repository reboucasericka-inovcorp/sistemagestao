import type { RouteRecordRaw } from 'vue-router'

import UsersHome from '@/modules/users/pages/UsersHome.vue'

export const usersRoutes: RouteRecordRaw[] = [{ path: 'users', name: 'users.home', component: UsersHome }]
