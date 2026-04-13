<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

type SettingsModuleCard = {
  title: string
  description: string
  to: string
}

const modules: SettingsModuleCard[] = [
  {
    title: 'Countries',
    description: 'Gestão de países e dados auxiliares de localização.',
    to: '/settings/countries',
  },
  {
    title: 'Contact Functions',
    description: 'Definição de funções usadas nos contactos.',
    to: '/settings/contact-functions',
  },
  {
    title: 'Articles',
    description: 'Catálogo de artigos para fluxos comerciais.',
    to: '/settings/articles',
  },
  {
    title: 'VAT',
    description: 'Gestão de taxas de IVA aplicadas aos artigos.',
    to: '/settings/vat',
  },
  {
    title: 'Company',
    description: 'Configurações de dados da empresa.',
    to: '/settings/company',
  },
  {
    title: 'Logs',
    description: 'Consulta de registos e auditoria do sistema.',
    to: '/settings/logs',
  },
]
</script>

<template>
  <div class="settings-page">
    <div class="settings-header">
      <h1>Configurações</h1>
      <p class="subtitle">Acesso rápido aos módulos de configuração do sistema.</p>
    </div>

    <div class="cards-grid">
      <Card
        v-for="module in modules"
        :key="module.to"
        class="transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
      >
        <CardHeader>
          <CardTitle>{{ module.title }}</CardTitle>
          <CardDescription>{{ module.description }}</CardDescription>
        </CardHeader>
        <CardContent>
          <Button as-child variant="outline" class="w-full">
            <RouterLink :to="module.to">Abrir módulo</RouterLink>
          </Button>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<style scoped>
.settings-page {
  margin: 0 auto;
  max-width: 72rem;
  padding: 0.25rem 0;
}

.settings-header {
  margin-bottom: 1.25rem;
}

h1 {
  margin: 0;
  text-align: center;
}

.subtitle {
  margin: 0.5rem 0 0;
  text-align: center;
  color: hsl(var(--muted-foreground));
}

.cards-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 768px) {
  .cards-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1200px) {
  .cards-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
</style>
