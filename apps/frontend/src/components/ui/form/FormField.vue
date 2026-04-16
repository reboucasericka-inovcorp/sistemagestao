<script setup lang="ts">
import { Field } from 'vee-validate'

const props = defineProps<{ name: string }>()
</script>

<template>
  <Field :name="props.name" v-slot="{ field, meta, errors }">
    <slot
      :value="field.value"
      :handleChange="field.onChange"
      :errorMessage="errors?.[0]"
      :meta="meta"
      :componentField="{
        modelValue: field.value,
        'onUpdate:modelValue': field.onChange,
      }"
    />
  </Field>
</template>
