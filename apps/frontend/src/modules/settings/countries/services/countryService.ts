import api from '@/shared/services/api'
import { API_ROUTES } from '@/core/api/apiRoutes'
import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import type { Country } from '../types/country'

export type ListCountriesQuery = {
  search?: string
  page?: number
  per_page?: number
}

export type CountriesListMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export type ListCountriesResult = {
  data: Country[]
  meta: CountriesListMeta
}

export type UpsertCountryPayload = {
  name: string
  code?: string | null
  is_active: boolean
}

export async function listCountriesResult(query?: ListCountriesQuery): Promise<ListCountriesResult> {
  const response = await api.get(API_ROUTES.countries, {
    params: {
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })
  const normalized = normalizeListResponse(response.data) as {
    data: Country[]
    meta?: Partial<CountriesListMeta> | null
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

export async function getCountryById(id: number): Promise<Country> {
  const response = await api.get(`${API_ROUTES.countries}/${id}`)
  return response.data.data as Country
}

export async function createCountry(payload: UpsertCountryPayload): Promise<Country> {
  const response = await api.post(API_ROUTES.countries, payload)
  return response.data.data as Country
}

export async function updateCountry(id: number, payload: Partial<UpsertCountryPayload>): Promise<Country> {
  const response = await api.put(`${API_ROUTES.countries}/${id}`, payload)
  return response.data.data as Country
}

export async function inactivateCountry(id: number): Promise<Country> {
  const response = await api.delete(`${API_ROUTES.countries}/${id}`)
  return response.data.data as Country
}

export async function toggleCountryStatus(id: number, isCurrentlyActive: boolean): Promise<Country> {
  if (isCurrentlyActive) {
    return inactivateCountry(id)
  }
  return updateCountry(id, { is_active: true })
}
