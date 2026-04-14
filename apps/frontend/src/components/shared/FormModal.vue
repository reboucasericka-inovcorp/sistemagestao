<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'
import { DialogContent, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui'

const props = defineProps<{
  open: boolean
  title: string
}>()

defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const contentRef = ref<HTMLElement | null>(null)

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return
    await nextTick()
    const firstField = contentRef.value?.querySelector<HTMLElement>(
      'input:not([disabled]), textarea:not([disabled]), select:not([disabled])',
    )
    firstField?.focus()
  },
)
</script>

<template>
  <DialogRoot :open="open" @update:open="$emit('update:open', $event)">
    <DialogPortal>
      <DialogOverlay class="fixed inset-0 z-50 bg-black/40" />
      <DialogContent
        ref="contentRef"
        class="fixed left-1/2 top-1/2 z-50 w-[75vw] max-h-[90vh] -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-md border bg-background p-4 shadow-lg"
      >
        <DialogTitle class="mb-4 text-left text-lg font-semibold">{{ title }}</DialogTitle>
        <slot />
      </DialogContent>
    </DialogPortal>
  </DialogRoot>
</template>
