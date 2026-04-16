<script setup lang="ts">
import { useFormContext } from 'vee-validate'
import { computed, useAttrs } from 'vue'

const emit = defineEmits<{
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  (e: 'submit', values: any): void
}>()

const attrs = useAttrs()
const form = useFormContext()

const submitHandler = computed(() => {
  // vee-validate's handleSubmit will validate and pass *values* to the callback.
  return form.handleSubmit((values: any) => {
    emit('submit', values)
  })
})
</script>

<template>
  <form v-bind="attrs" @submit.prevent="submitHandler">
    <slot />
  </form>
</template>

