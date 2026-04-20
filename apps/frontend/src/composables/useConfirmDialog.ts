import { ref } from 'vue'

type ConfirmOptions = {
  title: string
  description: string
  confirmLabel?: string
  cancelLabel?: string
  onConfirm: () => Promise<void> | void
  onCancel?: () => Promise<void> | void
}

const isOpen = ref(false)
const title = ref('')
const description = ref('')
const confirmLabel = ref('Confirmar')
const cancelLabel = ref('Cancelar')
const isLoading = ref(false)
const isExecuting = ref(false)

let confirmAction: null | (() => Promise<void> | void) = null
let cancelAction: null | (() => Promise<void> | void) = null

export function useConfirmDialog() {
  const open = (options: ConfirmOptions) => {
    if (isExecuting.value) return

    title.value = options.title
    description.value = options.description
    confirmLabel.value = options.confirmLabel ?? 'Confirmar'
    cancelLabel.value = options.cancelLabel ?? 'Cancelar'
    confirmAction = options.onConfirm
    cancelAction = options.onCancel ?? null
    isOpen.value = true
  }

  const confirm = async () => {
    if (!confirmAction || isExecuting.value) return

    isExecuting.value = true
    isLoading.value = true

    try {
      await confirmAction()
      isOpen.value = false
    } finally {
      isLoading.value = false
      isExecuting.value = false
      confirmAction = null
      cancelAction = null
    }
  }

  const cancel = async () => {
    if (isLoading.value) return

    isExecuting.value = true
    try {
      if (cancelAction) {
        await cancelAction()
      }
      isOpen.value = false
    } finally {
      isExecuting.value = false
      confirmAction = null
      cancelAction = null
    }
  }

  return {
    isOpen,
    title,
    description,
    confirmLabel,
    cancelLabel,
    isLoading,
    open,
    confirm,
    cancel,
  }
}
