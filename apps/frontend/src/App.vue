<script setup lang="ts">
import { RouterView } from 'vue-router'
import { useCompany } from '@/core/company/useCompany'
import { onMounted } from 'vue'
import { Toaster } from 'vue-sonner'

const { loadCompany } = useCompany()

onMounted(() => {
  loadCompany()
})
</script>

<template>
  <RouterView />
  <Toaster rich-colors />
</template>
