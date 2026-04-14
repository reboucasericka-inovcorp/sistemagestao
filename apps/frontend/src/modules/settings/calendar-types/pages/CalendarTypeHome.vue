<script setup lang="ts">
import FormModal from '@/components/shared/FormModal.vue'
import { useCrudModal } from '@/composables/useCrudModal'
import CalendarTypeForm from '@/modules/settings/calendar-types/components/CalendarTypeForm.vue'
import CalendarTypeTable from '@/modules/settings/calendar-types/components/CalendarTypeTable.vue'
import DocSection from '@/shared/components/DocSection.vue'

const { openCreate, openEdit, editingId, tableKey, openCreateModal, closeCreateModal, openEditModal, closeEditModal, onSuccess } =
  useCrudModal()
</script>

<template>
  <div>
    <h1>Tipos de calendário</h1>

    <CalendarTypeTable :key="tableKey" :use-edit-modal="true" @create="openCreateModal" @edit="openEditModal" />
    <FormModal v-model:open="openCreate" title="Novo tipo de calendário">
      <CalendarTypeForm mode="create" :open="openCreate" @cancel="closeCreateModal" @success="onSuccess" />
    </FormModal>
    <FormModal v-model:open="openEdit" title="Editar tipo de calendário">
      <CalendarTypeForm
        v-if="editingId"
        mode="edit"
        :open="openEdit"
        :record-id="editingId"
        @cancel="closeEditModal"
        @success="onSuccess"
      />
    </FormModal>
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
