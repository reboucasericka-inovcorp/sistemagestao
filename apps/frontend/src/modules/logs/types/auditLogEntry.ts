/** Registo de auditoria (`docs/guide.md` §6 Logs). */
export type AuditLogEntry = {
  id: number
  recorded_at: string
  user_id: number | null
  menu: string
  action: string
  ip: string | null
  device: string | null
}
