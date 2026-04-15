<script setup lang="ts">
import FormModal from '@/components/shared/FormModal.vue'
import { useCrudModal } from '@/composables/useCrudModal'
import EntitiesTable from '@/modules/entities/components/EntitiesTable.vue'
import EntityForm from '@/modules/entities/pages/EntityForm.vue'

const {
  openCreate,
  openEdit,
  editingId,
  tableKey,
  openCreateModal,
  closeCreateModal,
  openEditModal,
  closeEditModal,
  onSuccess,
} = useCrudModal()
</script>

<template>
  <div>
    <h1>Clientes</h1>
    <h2>“Lista de entidades marcadas como clientes.”</h2>
    <EntitiesTable
      :key="tableKey"
      fixed-filter="is_client"
      edit-route-name="clients.edit"
      :use-edit-modal="true"
      @create="openCreateModal"
      @edit="openEditModal"
    />
    <FormModal v-model:open="openCreate" title="Novo cliente">
      <EntityForm
        mode="create"
        :default-is-client="true"
        :default-is-supplier="false"
        :open="openCreate"
        @cancel="closeCreateModal"
        @success="onSuccess"
      />
    </FormModal>
    <FormModal v-model:open="openEdit" title="Editar cliente">
      <EntityForm
        v-if="editingId"
        mode="edit"
        :default-is-client="true"
        :default-is-supplier="false"
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
  margin-bottom: 1rem;
}
</style>
