<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui'

const props = defineProps<{
  open: boolean
  title: string
  description?: string
  size?: 'default' | 'compact' | 'wide'
}>()

defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const contentBodyRef = ref<HTMLElement | null>(null)

const contentClass = computed(() => {
  if (props.size === 'compact') {
    return 'form-modal-content is-compact'
  }

  if (props.size === 'wide') {
    return 'form-modal-content is-wide'
  }

  return 'form-modal-content is-default'
})

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return
    await nextTick()
    const firstField = contentBodyRef.value?.querySelector<HTMLElement>(
      'input:not([disabled]), textarea:not([disabled]), select:not([disabled])',
    )
    firstField?.focus()
  },
)
</script>

<template>
  <DialogRoot :open="open" @update:open="$emit('update:open', $event)">
    <DialogPortal>
      <DialogOverlay class="fixed inset-0 z-50 bg-black/45 backdrop-blur-[1px]" />
      <DialogContent
        :class="contentClass"
      >
        <div ref="contentBodyRef">
          <DialogTitle class="form-modal-title">{{ title }}</DialogTitle>
          <DialogDescription class="sr-only">
            {{ description ?? `Janela modal: ${title}` }}
          </DialogDescription>
          <slot />
        </div>
      </DialogContent>
    </DialogPortal>
  </DialogRoot>
</template>

<style scoped>
.form-modal-content {
  position: fixed;
  left: 50%;
  top: 50%;
  z-index: 50;
  max-height: 90vh;
  transform: translate(-50%, -50%);
  overflow-y: auto;
  border: 1px solid hsl(var(--border));
  border-radius: 0.875rem;
  background: hsl(var(--background));
  padding: 1.1rem 1.1rem 1rem;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.22);
}

.form-modal-content.is-default {
  width: min(75vw, 72rem);
}

.form-modal-content.is-wide {
  width: min(90vw, 88rem);
}

.form-modal-content.is-compact {
  width: min(92vw, 28rem);
}

.form-modal-title {
  margin: 0 0 0.85rem;
  text-align: left;
  font-size: 1rem;
  font-weight: 600;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
