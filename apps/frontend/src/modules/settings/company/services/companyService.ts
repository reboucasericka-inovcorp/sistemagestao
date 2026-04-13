import { API_ROUTES } from '@/core/api/apiRoutes'
import api from '@/shared/services/api'
import type { Company, UpsertCompanyPayload } from '../types/company'

function buildCompanyFormData(payload: UpsertCompanyPayload): FormData {
  const formData = new FormData()
  formData.append('name', payload.name)
  formData.append('tax_number', payload.tax_number)

  if (payload.address) {
    formData.append('address', payload.address)
  }
  if (payload.postal_code) {
    formData.append('postal_code', payload.postal_code)
  }
  if (payload.city) {
    formData.append('city', payload.city)
  }
  if (payload.logo) {
    formData.append('logo', payload.logo)
  }

  return formData
}

export async function getCompany(): Promise<Company | null> {
  try {
    const response = await api.get(API_ROUTES.company)
    return (response.data?.data ?? response.data) as Company
  } catch (error: unknown) {
    const status =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { status?: number } }).response?.status
        : undefined
    if (status === 404) {
      return null
    }
    throw error
  }
}

export async function createCompany(payload: UpsertCompanyPayload): Promise<Company> {
  const response = await api.post(API_ROUTES.company, buildCompanyFormData(payload), {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
  return (response.data?.data ?? response.data) as Company
}

export async function updateCompany(payload: UpsertCompanyPayload): Promise<Company> {
  const formData = buildCompanyFormData(payload)
  formData.append('_method', 'PUT')
  const response = await api.post(API_ROUTES.company, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
  return (response.data?.data ?? response.data) as Company
}
