/**
 * Permissões Spatie alinhadas com `routes/api.php` (middleware permission:*).
 * Rotas sem correspondência aqui ficam só com autenticação no guard (ex.: propostas).
 */
export function normalizePath(fullPath: string): string {
  const path = fullPath.split('?')[0] ?? ''
  if (path === '' || path === '/') {
    return '/'
  }
  return path.replace(/\/+$/, '') || '/'
}

export function resolvePermissionForPath(fullPath: string): string | undefined {
  const path = normalizePath(fullPath)

  if (path.startsWith('/supplier-invoices/create')) {
    return 'supplier-invoices.create'
  }
  if (/^\/supplier-invoices\/\d+\/edit$/.test(path)) {
    return 'supplier-invoices.update'
  }
  if (path.startsWith('/supplier-invoices')) {
    return 'supplier-invoices.read'
  }

  if (path.startsWith('/digital-archive')) {
    return 'digital-files.read'
  }

  if (path.startsWith('/calendar')) {
    return 'calendar-events.read'
  }

  if (path === '/clients/new' || path === '/suppliers/new' || path === '/entities/new') {
    return 'entities.create'
  }
  if (/^\/(clients|suppliers|entities)\/\d+\/edit$/.test(path)) {
    return 'entities.update'
  }
  if (path.startsWith('/clients') || path.startsWith('/suppliers') || path.startsWith('/entities')) {
    return 'entities.read'
  }

  if (path === '/contacts/new') {
    return 'contacts.create'
  }
  if (/^\/contacts\/\d+\/edit$/.test(path)) {
    return 'contacts.update'
  }
  if (path.startsWith('/contacts')) {
    return 'contacts.read'
  }

  if (path === '/users/new') {
    return 'users.create'
  }
  if (/^\/users\/\d+\/edit$/.test(path)) {
    return 'users.update'
  }
  if (path.startsWith('/users')) {
    return 'users.read'
  }

  if (path === '/permissions/new') {
    return 'roles.create'
  }
  if (/^\/permissions\/[^/]+\/edit$/.test(path)) {
    return 'roles.update'
  }
  if (path.startsWith('/permissions')) {
    return 'roles.read'
  }

  if (path === '/settings/countries/new') {
    return 'countries.create'
  }
  if (/^\/settings\/countries\/\d+\/edit$/.test(path)) {
    return 'countries.update'
  }
  if (path.startsWith('/settings/countries')) {
    return 'countries.read'
  }

  if (path === '/settings/articles/new') {
    return 'articles.create'
  }
  if (/^\/settings\/articles\/\d+\/edit$/.test(path)) {
    return 'articles.update'
  }
  if (path.startsWith('/settings/articles')) {
    return 'articles.read'
  }

  if (path === '/settings/vat/new') {
    return 'vat.create'
  }
  if (/^\/settings\/vat\/\d+\/edit$/.test(path)) {
    return 'vat.update'
  }
  if (path.startsWith('/settings/vat')) {
    return 'vat.read'
  }

  if (path === '/settings/calendar-types/new') {
    return 'calendar-types.create'
  }
  if (/^\/settings\/calendar-types\/\d+\/edit$/.test(path)) {
    return 'calendar-types.update'
  }
  if (path.startsWith('/settings/calendar-types')) {
    return 'calendar-types.read'
  }

  if (path === '/settings/calendar-actions/new') {
    return 'calendar-actions.create'
  }
  if (/^\/settings\/calendar-actions\/\d+\/edit$/.test(path)) {
    return 'calendar-actions.update'
  }
  if (path.startsWith('/settings/calendar-actions')) {
    return 'calendar-actions.read'
  }

  if (path === '/settings/contact-functions/new') {
    return 'contact-functions.create'
  }
  if (/^\/settings\/contact-functions\/\d+\/edit$/.test(path)) {
    return 'contact-functions.update'
  }
  if (path.startsWith('/settings/contact-functions')) {
    return 'contact-functions.read'
  }

  if (path.startsWith('/settings/logs')) {
    return 'logs.read'
  }

  if (path.startsWith('/settings/company')) {
    return 'company.update'
  }

  return undefined
}
