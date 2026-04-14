<script setup lang="ts">
import FormModal from '@/components/shared/FormModal.vue'
import { useCrudModal } from '@/composables/useCrudModal'
import ArticleForm from '@/modules/settings/articles/components/ArticleForm.vue'
import ArticlesTable from '@/modules/settings/articles/components/ArticlesTable.vue'
import DocSection from '@/shared/components/DocSection.vue'

const { openCreate, openEdit, editingId, tableKey, openCreateModal, closeCreateModal, openEditModal, closeEditModal, onSuccess } =
  useCrudModal()
</script>

<template>
  <div>
    <h1>Artigos</h1>

    <ArticlesTable :key="tableKey" :use-edit-modal="true" @create="openCreateModal" @edit="openEditModal" />
    <FormModal v-model:open="openCreate" title="Novo artigo">
      <ArticleForm mode="create" :open="openCreate" @cancel="closeCreateModal" @success="onSuccess" />
    </FormModal>
    <FormModal v-model:open="openEdit" title="Editar artigo">
      <ArticleForm
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
