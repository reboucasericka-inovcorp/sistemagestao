import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import api from '@/shared/services/api'
import type { Log } from '../types/log'

export type ListLogsQuery = {
  search?: string
  user?: string
  menu?: string
  action?: string
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
}

export type LogsListMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export type ListLogsResult = {
  data: Log[]
  meta: LogsListMeta
}

type RawLog = {
  id: number
  date?: string
  time?: string
  user_name?: string | null
  menu?: string | null
  action?: string | null
  device?: string | null
  ip_address?: string | null
}

function mapRawLog(item: RawLog): Log {
  return {
    id: item.id,
    date: item.date ?? '-',
    time: item.time ?? '-',
    user_name: item.user_name ?? null,
    menu: item.menu ?? '-',
    action: item.action ?? '-',
    device: item.device ?? null,
    ip_address: item.ip_address ?? null,
  }
}

export async function listLogsResult(query?: ListLogsQuery): Promise<ListLogsResult> {
  const response = await api.get('/activity-logs', {
    params: {
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.user ? { user: query.user } : {}),
      ...(query?.menu ? { menu: query.menu } : {}),
      ...(query?.action ? { action: query.action } : {}),
      ...(query?.date_from ? { date_from: query.date_from } : {}),
      ...(query?.date_to ? { date_to: query.date_to } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })

  const normalized = normalizeListResponse(response.data) as {
    data: RawLog[]
    meta?: Partial<LogsListMeta> | null
  }
  const data = normalized.data.map(mapRawLog)
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
