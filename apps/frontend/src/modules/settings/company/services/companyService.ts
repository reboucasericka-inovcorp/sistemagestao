import { API_ROUTES } from '@/core/api/apiRoutes'
import api from '@/shared/services/api'
import type { Company, UpsertCompanyPayload } from '../types/company'

function buildCompanyFormData(payload: UpsertCompanyPayload): FormData {
  const formData = new FormData()
  formData.append('_method', 'PUT')
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
  if (payload.country_id) {
    formData.append('country_id', String(payload.country_id))
  }
  if (payload.phone) {
    formData.append('phone', payload.phone)
  }
  if (payload.mobile) {
    formData.append('mobile', payload.mobile)
  }
  if (payload.email) {
    formData.append('email', payload.email)
  }
  if (payload.website) {
    formData.append('website', payload.website)
  }
  formData.append('is_active', payload.is_active ? '1' : '0')
  if (payload.logo) {
    formData.append('logo', payload.logo)
  }

  return formData
}

export async function getCompany(): Promise<Company | null> {
  const response = await api.get(API_ROUTES.company)
  return (response.data?.data ?? response.data) as Company | null
}

export async function updateCompany(payload: UpsertCompanyPayload): Promise<Company> {
  const response = await api.post(API_ROUTES.company, buildCompanyFormData(payload), {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
  return (response.data?.data ?? response.data) as Company
}
