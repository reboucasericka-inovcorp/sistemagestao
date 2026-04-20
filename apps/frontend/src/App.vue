<script setup lang="ts">
import { RouterView } from 'vue-router'
import { Toaster } from 'vue-sonner'
import ConfirmDialog from '@/components/ui/confirm-dialog/ConfirmDialog.vue'
import { useConfirmDialog } from '@/composables/useConfirmDialog'

const confirmDialog = useConfirmDialog()
</script>

<template>
  <RouterView />
  <ConfirmDialog
    :open="confirmDialog.isOpen.value"
    :title="confirmDialog.title.value"
    :description="confirmDialog.description.value"
    :confirm-label="confirmDialog.confirmLabel.value"
    :cancel-label="confirmDialog.cancelLabel.value"
    :loading="confirmDialog.isLoading.value"
    @confirm="confirmDialog.confirm"
    @cancel="confirmDialog.cancel"
  />
  <Toaster rich-colors />
</template>
