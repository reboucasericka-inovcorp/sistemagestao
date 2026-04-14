import { ref } from 'vue'

export function useCrudModal() {
  const openCreate = ref(false)
  const openEdit = ref(false)
  const editingId = ref<number | null>(null)
  const tableKey = ref(0)

  function openCreateModal(): void {
    openCreate.value = true
  }

  function closeCreateModal(): void {
    openCreate.value = false
  }

  function openEditModal(id: number): void {
    editingId.value = id
    openEdit.value = true
  }

  function closeEditModal(): void {
    openEdit.value = false
    editingId.value = null
  }

  function refreshTable(): void {
    tableKey.value += 1
  }

  function onSuccess(): void {
    closeCreateModal()
    closeEditModal()
    refreshTable()
  }

  return {
    openCreate,
    openEdit,
    editingId,
    tableKey,
    openCreateModal,
    closeCreateModal,
    openEditModal,
    closeEditModal,
    refreshTable,
    onSuccess,
  }
}
