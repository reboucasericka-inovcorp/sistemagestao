<script setup lang="ts">
import { reactive, watch } from 'vue'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import type { CalendarEntityOption, CalendarFilters, CalendarUserOption } from '@/modules/calendar/types/calendar'

const props = defineProps<{
  users: CalendarUserOption[]
  entities: CalendarEntityOption[]
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'change', value: CalendarFilters): void
}>()

const state = reactive({
  userId: 'all',
  entityId: 'all',
})

watch(
  () => [state.userId, state.entityId],
  () => {
    emit('change', {
      user_id: state.userId !== 'all' ? Number(state.userId) : undefined,
      entity_id: state.entityId !== 'all' ? Number(state.entityId) : undefined,
    })
  },
  { immediate: true },
)
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <Select
      :model-value="state.userId"
      :disabled="loading"
      @update:model-value="(value) => (state.userId = String(value ?? 'all'))"
    >
      <SelectTrigger class="w-[240px]"><SelectValue placeholder="Filtrar por utilizador" /></SelectTrigger>
      <SelectContent>
        <SelectItem value="all">Todos os utilizadores</SelectItem>
        <SelectItem v-for="user in props.users" :key="user.id" :value="String(user.id)">{{ user.name }}</SelectItem>
      </SelectContent>
    </Select>

    <Select
      :model-value="state.entityId"
      :disabled="loading"
      @update:model-value="(value) => (state.entityId = String(value ?? 'all'))"
    >
      <SelectTrigger class="w-[240px]"><SelectValue placeholder="Filtrar por entidade" /></SelectTrigger>
      <SelectContent>
        <SelectItem value="all">Todas as entidades</SelectItem>
        <SelectItem v-for="entity in props.entities" :key="entity.id" :value="String(entity.id)">{{ entity.name }}</SelectItem>
      </SelectContent>
    </Select>
  </div>
</template>
