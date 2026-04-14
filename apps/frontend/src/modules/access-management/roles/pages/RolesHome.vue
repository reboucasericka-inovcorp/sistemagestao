<script setup lang="ts">
import FormModal from '@/components/shared/FormModal.vue'
import { useCrudModal } from '@/composables/useCrudModal'
import RoleForm from '@/modules/access-management/roles/components/RoleForm.vue'
import RolesTable from '@/modules/access-management/roles/components/RolesTable.vue'

const { openCreate, openEdit, editingId, tableKey, openCreateModal, closeCreateModal, openEditModal, closeEditModal, onSuccess } =
  useCrudModal()
</script>

<template>
  <div>
    <h1>Grupos de permissões</h1>
    <RolesTable :key="tableKey" :use-edit-modal="true" @create="openCreateModal" @edit="openEditModal" />
    <FormModal v-model:open="openCreate" title="Novo grupo de permissões">
      <RoleForm mode="create" :open="openCreate" @cancel="closeCreateModal" @success="onSuccess" />
    </FormModal>
    <FormModal v-model:open="openEdit" title="Editar grupo de permissões">
      <RoleForm
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
