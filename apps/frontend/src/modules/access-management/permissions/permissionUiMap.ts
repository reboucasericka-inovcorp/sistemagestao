import type { CrudAction } from './types/permissionGroup'

const MODULE_LABELS: Record<string, string> = {
  entities: 'Entidades',
  contacts: 'Contactos',
  countries: 'Países',
  vat: 'IVA',
  'contact-functions': 'Funções de Contacto',
  'calendar-types': 'Tipos de Calendário',
  'calendar-actions': 'Ações de Calendário',
  articles: 'Artigos',
  company: 'Empresa',
  logs: 'Logs',
  users: 'Utilizadores',
  roles: 'Permissões',
}

const ACTION_LABELS: Record<CrudAction, string> = {
  create: 'Criar',
  read: 'Ler',
  update: 'Atualizar',
  delete: 'Eliminar',
}

export function getModuleLabel(moduleName: string): string {
  return MODULE_LABELS[moduleName] ?? moduleName
}

export function getActionLabel(action: CrudAction): string {
  return ACTION_LABELS[action]
}
