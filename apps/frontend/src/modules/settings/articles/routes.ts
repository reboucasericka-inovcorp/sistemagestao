import type { RouteRecordRaw } from 'vue-router'

export const articlesRoutes: RouteRecordRaw[] = [
  {
    path: 'settings/articles',
    name: 'articles.index',
    component: () => import('./pages/ArticlesHome.vue'),
  },
  {
    path: 'settings/articles/new',
    name: 'articles.new',
    component: () => import('./pages/ArticleForm.vue'),
  },
  {
    path: 'settings/articles/:id/edit',
    name: 'articles.edit',
    component: () => import('./pages/ArticleForm.vue'),
  },
]
