<script setup lang="ts">
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'

withDefaults(
  defineProps<{
    open: boolean
    title: string
    description: string
    confirmLabel?: string
    cancelLabel?: string
    loading?: boolean
  }>(),
  {
    confirmLabel: 'Apagar',
    cancelLabel: 'Cancelar',
    loading: false,
  },
)

const emit = defineEmits<{
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()
</script>

<template>
  <Dialog :open="open" @update:open="emit('cancel')">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
      </DialogHeader>

      <DialogDescription>{{ description }}</DialogDescription>

      <DialogFooter class="mt-4">
        <Button variant="outline" :disabled="loading" @click="emit('cancel')">
          {{ cancelLabel }}
        </Button>
        <Button variant="destructive" :disabled="loading" @click="emit('confirm')">
          {{ loading ? 'A processar...' : confirmLabel }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
