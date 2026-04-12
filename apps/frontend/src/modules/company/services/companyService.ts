import api from '@/shared/services/api'
import type { CompanyProfile } from '../types/companyProfile'

export async function getCompanyProfile(): Promise<CompanyProfile> {
  const response = await api.get('/company')
  return response.data.data as CompanyProfile
}
