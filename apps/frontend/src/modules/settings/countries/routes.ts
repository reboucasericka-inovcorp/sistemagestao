import type { RouteRecordRaw } from 'vue-router'

export const countriesRoutes: RouteRecordRaw[] = [
  {
    path: 'settings/countries',
    name: 'countries.index',
    component: () => import('./pages/CountriesHome.vue'),
  },
  {
    path: 'settings/countries/new',
    name: 'countries.new',
    component: () => import('./pages/CountryForm.vue'),
  },
  {
    path: 'settings/countries/:id/edit',
    name: 'countries.edit',
    component: () => import('./pages/CountryForm.vue'),
  },
]
