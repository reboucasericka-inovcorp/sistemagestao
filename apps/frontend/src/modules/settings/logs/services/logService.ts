import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import api from '@/shared/services/api'
import type { Log } from '../types/log'

export type ListLogsQuery = {
  search?: string
  user?: string
  menu?: string
  action?: string
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
  recorded_at?: string
  created_at?: string
  user_name?: string | null
  user?: { name?: string | null } | null
  menu?: string | null
  action?: string | null
  device?: string | null
  ip?: string | null
  ip_address?: string | null
}

function mapRawLog(item: RawLog): Log {
  const recordedAt = item.recorded_at ?? item.created_at ?? ''
  const dateValue = recordedAt ? new Date(recordedAt) : null
  const isValidDate = dateValue instanceof Date && !Number.isNaN(dateValue.getTime())

  return {
    id: item.id,
    date: isValidDate ? dateValue.toLocaleDateString('pt-PT') : '-',
    time: isValidDate ? dateValue.toLocaleTimeString('pt-PT') : '-',
    user_name: item.user_name ?? item.user?.name ?? null,
    menu: item.menu ?? '-',
    action: item.action ?? '-',
    device: item.device ?? null,
    ip_address: item.ip_address ?? item.ip ?? null,
  }
}

export async function listLogsResult(query?: ListLogsQuery): Promise<ListLogsResult> {
  const response = await api.get('/activity-logs', {
    params: {
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.user ? { user: query.user } : {}),
      ...(query?.menu ? { menu: query.menu } : {}),
      ...(query?.action ? { action: query.action } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })

  const normalized = normalizeListResponse(response) as {
    data: RawLog[]
    meta?: Partial<LogsListMeta> | null
  }
  const data = Array.isArray(normalized.data) ? normalized.data.map(mapRawLog) : []
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
