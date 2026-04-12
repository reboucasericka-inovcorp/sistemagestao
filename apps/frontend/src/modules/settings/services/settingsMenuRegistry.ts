/**
 * Registo de áreas de configuração descritas em `docs/README.md` (Menus — Configurações).
 * Quando a API expuser recursos por secção, os serviços (`listCountries`, etc.) vivem neste módulo.
 */
export const settingsMenuSections = [
  { id: 'entities_countries', label: 'Entidades — Países' },
  { id: 'contact_functions', label: 'Contactos — Funções' },
  { id: 'calendar_types', label: 'Calendário — Tipos' },
  { id: 'calendar_actions', label: 'Calendário — Acções' },
  { id: 'articles', label: 'Artigos' },
  { id: 'finance_vat', label: 'Financeiro — IVA' },
  { id: 'company', label: 'Empresa' },
] as const
