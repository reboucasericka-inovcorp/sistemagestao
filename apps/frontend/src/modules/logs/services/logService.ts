import api from '@/shared/services/api'
import type { AuditLogEntry } from '../types/auditLogEntry'

export async function listAuditLogs(): Promise<AuditLogEntry[]> {
  const response = await api.get('/activity-logs')
  return response.data.data as AuditLogEntry[]
}
