<script setup lang="ts">
defineProps<{
  id?: string
  label: string
  modelValue: string | number
  type?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const onInput = (e: Event) => {
  const el = e.target as HTMLInputElement
  emit('update:modelValue', el.type === 'number' ? Number(el.value) : el.value)
}
</script>

<template>
  <div class="field">
    <label v-if="label" :for="id">{{ label }}</label>
    <input
      :id="id"
      :type="type ?? 'text'"
      class="input"
      :disabled="disabled"
      :value="modelValue"
      @input="onInput"
    />
  </div>
</template>

<style scoped>
.field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-bottom: 0.75rem;
  text-align: left;
}

label {
  font-size: 0.875rem;
  color: var(--text-h);
}

.input {
  font: inherit;
  padding: 0.5rem 0.65rem;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: var(--bg);
  color: var(--text-h);
}

.input:disabled {
  opacity: 0.65;
}
</style>
