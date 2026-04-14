<script setup lang="ts">
import { ref } from 'vue'
import FormModal from '@/components/shared/FormModal.vue'
import PermissionGroupForm from '@/modules/access-management/permissions/components/PermissionGroupForm.vue'
import PermissionsTable from '@/modules/access-management/permissions/components/PermissionsTable.vue'

const openCreate = ref(false)
const tableKey = ref(0)

function onCreateSuccess(): void {
  openCreate.value = false
  tableKey.value += 1
}
</script>

<template>
  <div>
    <h1>Permissões</h1>
    <PermissionsTable :key="tableKey" @create="openCreate = true" />
    <FormModal v-model:open="openCreate" title="Novo grupo de permissões">
      <PermissionGroupForm mode="create" @cancel="openCreate = false" @success="onCreateSuccess" />
    </FormModal>
  </div>
</template>

<style scoped>
h1 {
  margin-top: 0;
  text-align: left;
}
</style>
