import type { RouteRecordRaw } from 'vue-router'

import SettingsHome from '@/modules/settings/pages/SettingsHome.vue'

export const settingsRoutes: RouteRecordRaw[] = [
  { path: 'settings', name: 'settings.home', component: SettingsHome },
]
