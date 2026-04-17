<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import type { RouteLocationRaw } from 'vue-router'
import { Button } from '@/components/ui/button'
import { useCompany } from '@/core/company/useCompany'
import {
  fetchAuthenticatedUser,
  logout,
  peekAuthenticatedUser,
} from '@/modules/auth/services/authService'
import type { AuthenticatedUser } from '@/modules/auth/services/authService'
import AppNavLink from '@/shared/components/AppNavLink.vue'

type NavItem = {
  label: string
  to?: RouteLocationRaw
  /** Permissão Spatie; omitir = qualquer utilizador autenticado (UX; backend continua a validar). */
  permission?: string
  children?: NavItem[]
}

const navItems: NavItem[] = [
  { label: 'Clientes', to: '/clients', permission: 'entities.read' },
  { label: 'Fornecedores', to: '/suppliers', permission: 'entities.read' },
  { label: 'Contactos', to: '/contacts', permission: 'contacts.read' },
  { label: 'Propostas', to: '/proposals' },
  { label: 'Calendário', to: '/calendar', permission: 'calendar-events.read' },
  { label: 'Encomendas - Clientes', to: '/client-orders' },
  { label: 'Encomendas - Fornecedores', to: '/supplier-orders' },
  { label: 'Ordens de Trabalho', to: '/work-orders' },
  {
    label: 'Financeiro',
    children: [
      { label: 'Contas Bancárias', to: '/bank-accounts' },
      { label: 'Conta Corrente Clientes', to: '/customer-accounts' },
      { label: 'Faturas Fornecedores', to: '/supplier-invoices', permission: 'supplier-invoices.read' },
    ],
  },
  { label: 'Arquivo Digital', to: '/digital-archive', permission: 'digital-files.read' },
  {
    label: 'Gestão de Acessos',
    children: [
      { label: 'Utilizadores', to: '/users', permission: 'users.read' },
      { label: 'Permissões', to: '/permissions', permission: 'roles.read' },
    ],
  },
  {
    label: 'Configurações',
    children: [
      { label: 'Entidades - Países', to: '/settings/countries', permission: 'countries.read' },
      {
        label: 'Contactos - Funções',
        to: '/settings/contact-functions',
        permission: 'contact-functions.read',
      },
      { label: 'Calendário - Tipos', to: '/settings/calendar-types', permission: 'calendar-types.read' },
      {
        label: 'Calendário - Acções',
        to: '/settings/calendar-actions',
        permission: 'calendar-actions.read',
      },
      { label: 'Artigos', to: '/settings/articles', permission: 'articles.read' },
      { label: 'Financeiro - IVA', to: '/settings/vat', permission: 'vat.read' },
      { label: 'Logs', to: '/settings/logs', permission: 'logs.read' },
      { label: 'Empresa', to: '/settings/company', permission: 'company.update' },
    ],
  },
]

function snapshotUserFromCache(): AuthenticatedUser | null {
  const cached = peekAuthenticatedUser()
  if (cached === undefined) {
    return null
  }
  return cached
}

const route = useRoute()
const router = useRouter()
const { company, loadCompany } = useCompany()
const profileMenuOpen = ref(false)
const authenticatedUser = ref<AuthenticatedUser | null>(snapshotUserFromCache())
const logoutLoading = ref(false)
const companyName = computed(() => company.value?.name || '')
const companyLogoUrl = computed(() => company.value?.logo_url || null)

const isItemActive = (item: NavItem): boolean => {
  if (item.children?.length) {
    return item.children.some((child) => isItemActive(child))
  }

  if (!item.to) {
    return false
  }

  return router.resolve(item.to).path === route.path
}

/** UX: só esconde entradas — segurança continua no backend e no router guard. */
function canSeeNavEntry(permission?: string): boolean {
  if (!permission) {
    return true
  }
  const perms = authenticatedUser.value?.permissions
  return Array.isArray(perms) && perms.includes(permission)
}

const visibleNavItems = computed(() => {
  return navItems
    .map((item) => {
      if (item.children?.length) {
        const children = item.children.filter((child) => canSeeNavEntry(child.permission))
        if (children.length === 0) {
          return null
        }
        return { ...item, children }
      }
      return canSeeNavEntry(item.permission) ? item : null
    })
    .filter((item): item is NavItem => item !== null)
})

const openDropdowns = ref<Record<string, boolean>>({})

watch(
  visibleNavItems,
  (items) => {
    const next = { ...openDropdowns.value }
    for (const item of items) {
      if (item.children?.length && next[item.label] === undefined) {
        next[item.label] = isItemActive(item)
      }
    }
    openDropdowns.value = next
  },
  { immediate: true },
)

const toggleDropdown = (label: string): void => {
  openDropdowns.value[label] = !openDropdowns.value[label]
}

const isDropdownOpen = (item: NavItem): boolean => {
  if (!item.children?.length) {
    return false
  }

  return openDropdowns.value[item.label] ?? false
}

const toggleProfileMenu = (): void => {
  profileMenuOpen.value = !profileMenuOpen.value
}

const closeProfileMenu = (): void => {
  profileMenuOpen.value = false
}

const handleLogout = async (): Promise<void> => {
  logoutLoading.value = true
  try {
    await logout()
    authenticatedUser.value = null
  } finally {
    logoutLoading.value = false
    closeProfileMenu()
    await router.push('/login')
  }
}

onMounted(async () => {
  await Promise.allSettled([fetchAuthenticatedUser().then((user) => { authenticatedUser.value = user }), loadCompany()])
})
</script>

<template>
  <div class="app-layout">
    <aside class="sidebar" aria-label="Navegação principal">
      <header class="brand">
        <img v-if="companyLogoUrl" :src="companyLogoUrl" alt="Logótipo da empresa" class="brand-logo" />
        <span v-if="companyName">{{ companyName }}</span>
      </header>
      <nav class="nav-wrapper">
        <ul class="nav-list">
          <li v-for="item in visibleNavItems" :key="item.label">
            <button
              v-if="item.children?.length"
              type="button"
              class="dropdown-toggle"
              :class="{ 'is-active': isItemActive(item) }"
              :aria-expanded="isDropdownOpen(item)"
              @click="toggleDropdown(item.label)"
            >
              <span>{{ item.label }}</span>
              <span class="chevron" :class="{ open: isDropdownOpen(item) }">▾</span>
            </button>

            <AppNavLink v-else-if="item.to" :to="item.to">{{ item.label }}</AppNavLink>

            <ul v-if="item.children?.length && isDropdownOpen(item)" class="nav-sublist">
              <li v-for="child in item.children" :key="`${item.label}-${child.label}`">
                <AppNavLink v-if="child.to" :to="child.to">{{ child.label }}</AppNavLink>
              </li>
            </ul>
          </li>
        </ul>
      </nav>
      <footer class="sidebar-footer">
        <p class="footer-title">Perfil</p>
        <div class="profile-menu-wrapper">
          <Button
            type="button"
            variant="outline"
            class="profile-trigger"
            :aria-expanded="profileMenuOpen"
            :disabled="!authenticatedUser"
            @click="toggleProfileMenu"
          >
            <span class="profile-name">{{ authenticatedUser?.name }}</span>
            <span class="chevron" :class="{ open: profileMenuOpen }">▾</span>
          </Button>

          <div v-if="profileMenuOpen" class="profile-menu">
            <AppNavLink to="/profile/security" @click="closeProfileMenu">Perfil e Segurança</AppNavLink>
            <button
              type="button"
              class="logout-button"
              :disabled="logoutLoading"
              @click="handleLogout"
            >
              {{ logoutLoading ? 'A sair...' : 'Logout' }}
            </button>
          </div>
        </div>
      </footer>
    </aside>
    <main class="main">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.app-layout {
  display: flex;
  min-height: 100svh;
  width: 100%;
  max-width: none;
  text-align: left;
}

.sidebar {
  width: 15rem;
  flex-shrink: 0;
  border-right: 1px solid #cbd5e1;
  padding: 1rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  background-color: #e2e8f0;
  box-shadow: inset -1px 0 0 rgba(15, 23, 42, 0.06);
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  font-size: 1.1rem;
  color: #0f172a;
  padding: 0 0.5rem;
}

.brand-logo {
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 0.35rem;
  object-fit: cover;
}

.nav-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.nav-wrapper {
  display: block;
}

.dropdown-toggle {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: transparent;
  border: none;
  color: #1e293b;
  text-align: left;
  cursor: pointer;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  font-size: 0.9rem;
}

.dropdown-toggle:hover {
  background-color: #cbd5e1;
  color: #0f172a;
}

.dropdown-toggle.is-active {
  background-color: #bfdbfe;
  color: #1e40af;
  font-weight: 600;
}

.chevron {
  transition: transform 0.2s ease;
}

.chevron.open {
  transform: rotate(180deg);
}

.nav-sublist {
  list-style: none;
  margin: 0.2rem 0 0.25rem;
  padding: 0 0 0 0.9rem;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

:deep(.nav-list li a) {
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  color: #1e293b;
  font-weight: 500;
  transition:
    background-color 0.2s ease,
    color 0.2s ease;
}

:deep(.nav-list li a:hover) {
  background-color: #cbd5e1;
  color: #0f172a;
}

:deep(.nav-list li a.router-link-active) {
  background-color: #bfdbfe;
  color: #1e40af;
  font-weight: 600;
}

.sidebar-footer {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid #dbe3ee;
}

.footer-title {
  margin: 0 0 0.5rem;
  padding: 0 0.75rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
}

.profile-menu-wrapper {
  position: relative;
}

.profile-trigger {
  width: 100%;
  justify-content: space-between;
}

.profile-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.profile-menu {
  margin-top: 0.35rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #f8fafc;
  overflow: hidden;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
}

.logout-button {
  width: 100%;
  text-align: left;
  border: none;
  background: transparent;
  padding: 0.5rem 0.75rem;
  color: #991b1b;
  cursor: pointer;
  font-size: 0.9rem;
}

.logout-button:hover:not(:disabled) {
  background: #fee2e2;
}

.logout-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.profile-menu :deep(a) {
  border-radius: 0;
  display: block;
}

.main {
  flex: 1;
  padding: 1.25rem 1.5rem;
  overflow: auto;
  background-color: #f1f5f9;
}
</style>
