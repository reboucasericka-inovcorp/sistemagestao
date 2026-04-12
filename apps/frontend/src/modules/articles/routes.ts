import type { RouteRecordRaw } from 'vue-router'

import ArticlesHome from '@/modules/articles/pages/ArticlesHome.vue'

export const articlesRoutes: RouteRecordRaw[] = [
  { path: 'articles', name: 'articles.home', component: ArticlesHome },
]
