import type { RouteRecordRaw } from 'vue-router'

export const settingsRoutes: RouteRecordRaw[] = [
  /** Hub removido: primeiro item útil das configurações */
  { path: 'settings', redirect: { name: 'countries.index' } },
]
