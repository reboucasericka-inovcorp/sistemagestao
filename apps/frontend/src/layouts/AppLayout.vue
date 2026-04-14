<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import type { RouteLocationRaw } from 'vue-router'
import AppNavLink from '@/shared/components/AppNavLink.vue'

type NavItem = {
  label: string
  to?: RouteLocationRaw
  children?: NavItem[]
}

const navItems: NavItem[] = [
  { label: 'Clientes', to: '/clients' },
  { label: 'Fornecedores', to: '/suppliers' },
  { label: 'Contactos', to: '/contacts' },
  { label: 'Propostas', to: '/proposals' },
  { label: 'Calendário', to: '/calendar' },
  { label: 'Encomendas - Clientes', to: '/client-orders' },
  { label: 'Encomendas - Fornecedores', to: '/supplier-orders' },
  { label: 'Ordens de Trabalho', to: '/settings' },
  {
    label: 'Financeiro',
    children: [
      { label: 'Contas Bancárias', to: '/finance' },
      { label: 'Conta Corrente Clientes', to: '/finance' },
      { label: 'Faturas Fornecedores', to: '/supplier-invoices' },
    ],
  },
  { label: 'Arquivo Digital', to: '/settings' },
  {
    label: 'Gestão de Acessos',
    children: [
      { label: 'Utilizadores', to: '/users' },
      { label: 'Permissões', to: '/permissions' },
    ],
  },
  {
    label: 'Configurações',
    children: [
      { label: 'Entidades - Países', to: '/settings/countries' },
      { label: 'Contactos - Funções', to: '/settings/contact-functions' },
      { label: 'Calendário - Tipos', to: '/settings/calendar-types' },
      { label: 'Calendário - Acções', to: '/settings/calendar-actions' },
      { label: 'Artigos', to: '/settings/articles' },
      { label: 'Financeiro - IVA', to: '/settings/vat' },
      { label: 'Logs', to: '/settings/logs' },
      { label: 'Empresa', to: '/settings/company' },
    ],
  },
]

const route = useRoute()
const router = useRouter()

const isItemActive = (item: NavItem): boolean => {
  if (item.children?.length) {
    return item.children.some((child) => isItemActive(child))
  }

  if (!item.to) {
    return false
  }

  return router.resolve(item.to).path === route.path
}

const initialDropdownState = navItems.reduce<Record<string, boolean>>((acc, item) => {
  if (item.children?.length) {
    acc[item.label] = isItemActive(item)
  }

  return acc
}, {})

const openDropdowns = ref(initialDropdownState)

const toggleDropdown = (label: string): void => {
  openDropdowns.value[label] = !openDropdowns.value[label]
}

const isDropdownOpen = (item: NavItem): boolean => {
  if (!item.children?.length) {
    return false
  }

  return openDropdowns.value[item.label] ?? false
}

const navList = computed(() => navItems)
</script>

<template>
  <div class="app-layout">
    <aside class="sidebar" aria-label="Navegação principal">
      <header class="brand">Gestão</header>
      <nav class="nav-wrapper">
        <ul class="nav-list">
          <li v-for="item in navList" :key="item.label">
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
        <AppNavLink to="/login">Sessão / Login</AppNavLink>
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
  font-weight: 600;
  font-size: 1.1rem;
  color: #0f172a;
  padding: 0 0.5rem;
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
  transition: background-color 0.2s ease, color 0.2s ease;
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

.main {
  flex: 1;
  padding: 1.25rem 1.5rem;
  overflow: auto;
  background-color: #f1f5f9;
}
</style>
