<script setup lang="ts">
import { RouterView } from 'vue-router'
import { useCompany } from '@/core/company/useCompany'
import { onMounted } from 'vue'

const { loadCompany } = useCompany()

onMounted(() => {
  loadCompany()
})
</script>

<template>
  <RouterView />
</template>
