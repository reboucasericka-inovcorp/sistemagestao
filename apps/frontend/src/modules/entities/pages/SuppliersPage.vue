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
    <h1>Fornecedores</h1>
    <h2>“Lista de entidades marcadas como fornecedores.”</h2>
    <EntitiesTable
      :key="tableKey"
      fixed-filter="is_supplier"
      edit-route-name="suppliers.edit"
      :use-edit-modal="true"
      @create="openCreateModal"
      @edit="openEditModal"
    />
    <FormModal v-model:open="openCreate" title="Novo fornecedor">
      <EntityForm
        mode="create"
        :default-is-client="false"
        :default-is-supplier="true"
        :open="openCreate"
        @cancel="closeCreateModal"
        @success="onSuccess"
      />
    </FormModal>
    <FormModal v-model:open="openEdit" title="Editar fornecedor">
      <EntityForm
        mode="edit"
        :default-is-client="false"
        :default-is-supplier="true"
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
