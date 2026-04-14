<script setup lang="ts">
import FormModal from '@/components/shared/FormModal.vue'
import { useCrudModal } from '@/composables/useCrudModal'
import UserForm from '@/modules/access-management/users/components/UserForm.vue'
import UsersTable from '@/modules/access-management/users/components/UsersTable.vue'

const { openCreate, openEdit, editingId, tableKey, openCreateModal, closeCreateModal, openEditModal, closeEditModal, onSuccess } =
  useCrudModal()
</script>

<template>
  <div>
    <h1>Utilizadores</h1>
    <UsersTable :key="tableKey" :use-edit-modal="true" @create="openCreateModal" @edit="openEditModal" />
    <FormModal v-model:open="openCreate" title="Novo utilizador">
      <UserForm mode="create" :open="openCreate" @cancel="closeCreateModal" @success="onSuccess" />
    </FormModal>
    <FormModal v-model:open="openEdit" title="Editar utilizador">
      <UserForm
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
