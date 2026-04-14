import api from '@/shared/services/api'
import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import type { Article } from '../types/article'

export type ListArticlesQuery = {
  search?: string
  page?: number
  per_page?: number
}

export type ArticlesListMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export type ListArticlesResult = {
  data: Article[]
  meta: ArticlesListMeta
}

export async function listArticlesResult(query?: ListArticlesQuery): Promise<ListArticlesResult> {
  const response = await api.get('/articles', {
    params: {
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })

  const normalized = normalizeListResponse(response.data) as {
    data: Article[]
    meta?: Partial<ArticlesListMeta> | null
  }
  const data = normalized.data
  return {
    data,
    meta: {
      current_page: normalized.meta?.current_page ?? 1,
      per_page: normalized.meta?.per_page ?? (data.length || 15),
      total: normalized.meta?.total ?? data.length,
      last_page: normalized.meta?.last_page ?? 1,
    },
  }
}

export async function listArticles(query?: ListArticlesQuery): Promise<Article[]> {
  const result = await listArticlesResult(query)
  return result.data
}

export type VatOption = {
  id: number
  name: string
  rate: number
}

export type UpsertArticlePayload = {
  reference: string
  name: string
  description?: string | null
  price: string
  vat_id: number
  notes?: string | null
  is_active: boolean
  photo?: File | null
}

export async function getArticleById(id: number): Promise<Article> {
  const response = await api.get(`/articles/${id}`)
  return response.data.data as Article
}

function buildArticleFormData(payload: Partial<UpsertArticlePayload>): FormData {
  const formData = new FormData()
  if (payload.reference != null) {
    formData.append('reference', payload.reference)
  }
  if (payload.name != null) {
    formData.append('name', payload.name)
  }
  if (payload.price != null) {
    formData.append('price', String(payload.price))
  }
  if (payload.vat_id != null) {
    formData.append('vat_id', String(payload.vat_id))
  }
  if (payload.is_active != null) {
    formData.append('is_active', payload.is_active ? '1' : '0')
  }

  if (payload.description) {
    formData.append('description', payload.description)
  }
  if (payload.notes) {
    formData.append('notes', payload.notes)
  }
  if (payload.photo) {
    formData.append('photo', payload.photo)
  }

  return formData
}

export async function createArticle(payload: UpsertArticlePayload): Promise<Article> {
  const response = await api.post('/articles', buildArticleFormData(payload), {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
  return response.data.data as Article
}

export async function updateArticle(id: number, payload: Partial<UpsertArticlePayload>): Promise<Article> {
  const formData = buildArticleFormData(payload)
  formData.append('_method', 'PUT')
  const response = await api.post(`/articles/${id}`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
  return response.data.data as Article
}

export async function inactivateArticle(id: number): Promise<Article> {
  const response = await api.delete(`/articles/${id}`)
  return response.data.data as Article
}

export async function toggleArticleStatus(id: number, isCurrentlyActive: boolean): Promise<Article> {
  if (isCurrentlyActive) {
    return inactivateArticle(id)
  }
  const response = await api.put(`/articles/${id}`, { is_active: true })
  return response.data.data as Article
}

export async function listVatOptions(): Promise<VatOption[]> {
  const endpoints = ['/vat', '/vats', '/settings/vat']

  for (const endpoint of endpoints) {
    try {
      const response = await api.get(endpoint, { params: { active_only: 1, per_page: 100 } })
      const payload = response.data.data
      if (Array.isArray(payload)) {
        return (payload as Array<{ id: number; name: string; rate: number | string }>).map((vat) => ({
          id: vat.id,
          name: vat.name,
          rate: Number(vat.rate),
        }))
      }
      if (payload && Array.isArray(payload.data)) {
        return (payload.data as Array<{ id: number; name: string; rate: number | string }>).map((vat) => ({
          id: vat.id,
          name: vat.name,
          rate: Number(vat.rate),
        }))
      }
    } catch {
      // Try next endpoint.
    }
  }

  throw new Error('Endpoint de IVA não disponível.')
}
