import api from '@/shared/services/api'
import type { Proposal, UpsertProposalPayload } from '../types/proposal'

export async function getProposals(): Promise<Proposal[]> {
  const response = await api.get('/proposals')
  return (response.data?.data ?? response.data) as Proposal[]
}

export async function getProposalById(id: number): Promise<Proposal> {
  const response = await api.get(`/proposals/${id}`)
  return (response.data?.data ?? response.data) as Proposal
}

export async function createProposal(data: UpsertProposalPayload): Promise<Proposal> {
  const response = await api.post('/proposals', data)
  return (response.data?.data ?? response.data) as Proposal
}

export async function updateProposal(id: number, data: UpsertProposalPayload): Promise<Proposal> {
  const response = await api.put(`/proposals/${id}`, data)
  return (response.data?.data ?? response.data) as Proposal
}

export async function downloadProposalPdf(id: number): Promise<void> {
  try {
    const response = await api.get(`/proposals/${id}/pdf`, {
      responseType: 'blob',
    })

    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)

    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `proposal_${id}.pdf`)
    document.body.appendChild(link)
    link.click()

    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error: unknown) {
    console.error('[PDF DOWNLOAD ERROR]', error)

    const message =
      (error as { response?: { data?: { message?: string } } })?.response?.data?.message ??
      'Erro ao gerar o PDF da proposta.'

    throw new Error(message)
  }
}

export async function convertProposal(id: number): Promise<void> {
  try {
    await api.post(`/proposals/${id}/convert`)
  } catch (error: unknown) {
    console.error('[CONVERT ERROR]', error)

    const message =
      (error as { response?: { data?: { message?: string } } })?.response?.data?.message ??
      'Erro ao converter proposta.'

    throw new Error(message)
  }
}

export type ClientOption = {
  id: number
  name: string
}

export type SupplierOption = {
  id: number
  name: string
}

export type ArticleSearchOption = {
  id: number
  name: string
  reference: string
  price?: string
}

export async function getClientOptions(): Promise<ClientOption[]> {
  const response = await api.get('/entities', {
    params: {
      type: 'client',
      per_page: 100,
    },
  })
  const payload = (response.data?.data ?? response.data) as Array<{ id: number; name: string }>
  return payload.map((item) => ({ id: item.id, name: item.name }))
}

export async function getSupplierOptions(): Promise<SupplierOption[]> {
  const response = await api.get('/entities', {
    params: {
      type: 'supplier',
      per_page: 100,
    },
  })
  const payload = (response.data?.data ?? response.data) as Array<{ id: number; name: string }>
  return payload.map((item) => ({ id: item.id, name: item.name }))
}

export async function searchArticles(query: string): Promise<ArticleSearchOption[]> {
  const response = await api.get('/articles', {
    params: {
      search: query,
      per_page: 10,
    },
  })
  const payload = (response.data?.data ?? response.data) as Array<{
    id: number
    name: string
    reference: string
    price?: string
  }>
  return payload.map((article) => ({
    id: article.id,
    name: article.name,
    reference: article.reference,
    price: article.price,
  }))
}
