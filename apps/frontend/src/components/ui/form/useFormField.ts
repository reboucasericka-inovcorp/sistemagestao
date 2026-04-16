import { FieldContextKey } from "vee-validate"
import { computed, inject } from "vue"
import { FORM_ITEM_INJECTION_KEY } from "./injectionKeys"

export function useFormField() {
  const fieldContext = inject(FieldContextKey)
  const fieldItemContext = inject(FORM_ITEM_INJECTION_KEY)

  // Some UI sections (e.g. file upload) may render `FormItem/FormLabel/...`
  // outside of a vee-validate `<FormField>`. In that case we must not crash
  // the whole form; we return safe defaults instead.
  if (!fieldContext) {
    return {
      id: '',
      name: '',
      formItemId: '',
      formDescriptionId: '',
      formMessageId: '',
      valid: computed(() => false),
      isDirty: computed(() => false),
      isTouched: computed(() => false),
      error: undefined,
    }
  }

  const { name, errorMessage: error, meta } = fieldContext
  const id = fieldItemContext

  const fieldState = {
    valid: computed(() => meta.valid),
    isDirty: computed(() => meta.dirty),
    isTouched: computed(() => meta.touched),
    error,
  }

  return {
    id,
    name,
    formItemId: `${id}-form-item`,
    formDescriptionId: `${id}-form-item-description`,
    formMessageId: `${id}-form-item-message`,
    ...fieldState,
  }
}
