import api from '@/shared/services/api'
import type { Article } from '../types/article'

export async function listArticles(): Promise<Article[]> {
  const response = await api.get('/articles')
  return response.data.data as Article[]
}
