export type Log = {
  id: number
  date: string
  time: string
  user_name: string | null
  menu: string
  action: string
  device: string | null
  ip_address: string | null
}

export type LogAction = 'created' | 'updated' | 'deleted'
