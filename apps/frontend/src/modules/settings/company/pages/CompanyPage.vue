<script setup lang="ts">
import { onMounted } from 'vue'
import { useCompany } from '@/core/company/useCompany'
import CompanyForm from '@/modules/settings/company/components/CompanyForm.vue'

const { loadCompany, loading } = useCompany()

onMounted(async () => {
  await loadCompany()
})
</script>

<template>
  <div v-if="loading" class="text-sm text-muted-foreground">A carregar...</div>
  <CompanyForm v-else />
</template>
