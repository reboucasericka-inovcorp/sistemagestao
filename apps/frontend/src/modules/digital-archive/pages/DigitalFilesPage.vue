<script setup lang="ts">
import { ref } from 'vue'
import DigitalFileForm from '../components/DigitalFileForm.vue'
import DigitalFilesTable from '../components/DigitalFilesTable.vue'

const tableRef = ref<{ reload: () => Promise<void>; reloadAndReset: () => Promise<void> } | null>(null)

async function onUploaded(): Promise<void> {
  await tableRef.value?.reloadAndReset()
}
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-xl font-semibold">Arquivo Digital</h1>
    <DigitalFileForm @uploaded="onUploaded" />
    <DigitalFilesTable ref="tableRef" />
  </div>
</template>
