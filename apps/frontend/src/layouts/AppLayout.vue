<script setup lang="ts">
import { RouterView } from 'vue-router'
import type { RouteLocationRaw } from 'vue-router'
import AppNavLink from '@/shared/components/AppNavLink.vue'

type NavItem = { label: string; to: RouteLocationRaw }
type NavGroup = { title: string; items: NavItem[] }

/** Estrutura de menus alinhada a `docs/README.md` (secção Menus e Submenus). */
const navGroups: NavGroup[] = [
  {
    title: 'Cadastro',
    items: [
      { label: 'Clientes', to: { path: '/entities', query: { clients: '1' } } },
      { label: 'Fornecedores', to: { path: '/entities', query: { suppliers: '1' } } },
      { label: 'Contactos', to: '/contacts' },
    ],
  },
  {
    title: 'Comercial',
    items: [
      { label: 'Propostas', to: '/proposals' },
      { label: 'Encomendas — Clientes', to: '/client-orders' },
      { label: 'Encomendas — Fornecedores', to: '/supplier-orders' },
      { label: 'Artigos', to: '/settings/articles' },
      { label: 'IVA', to: '/settings/vat' },
    ],
  },
  {
    title: 'Operação',
    items: [
      { label: 'Calendário', to: '/calendar' },
      { label: 'Financeiro (visão)', to: '/finance' },
      { label: 'Faturas Fornecedor', to: '/supplier-invoices' },
    ],
  },
  {
    title: 'Sistema',
    items: [
      { label: 'Utilizadores', to: '/users' },
      { label: 'Permissões', to: '/permissions' },
      { label: 'Logs', to: '/settings/logs' },
      { label: 'Configurações', to: '/settings' },
      { label: 'Empresa', to: '/company' },
    ],
  },
]
</script>

<template>
  <div class="app-layout">
    <aside class="sidebar" aria-label="Navegação principal">
      <header class="brand">Gestão</header>
      <nav v-for="group in navGroups" :key="group.title" class="nav-group">
        <h2 class="nav-title">{{ group.title }}</h2>
        <ul class="nav-list">
          <li v-for="item in group.items" :key="item.label">
            <AppNavLink :to="item.to">{{ item.label }}</AppNavLink>
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
  border-right: 1px solid var(--border);
  padding: 1rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.brand {
  font-weight: 600;
  font-size: 1.1rem;
  color: var(--text-h);
  padding: 0 0.5rem;
}

.nav-group {
  margin: 0;
}

.nav-title {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text);
  margin: 0 0 0.35rem 0.5rem;
  font-weight: 600;
}

.nav-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.sidebar-footer {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid var(--border);
}

.main {
  flex: 1;
  padding: 1.25rem 1.5rem;
  overflow: auto;
}
</style>
