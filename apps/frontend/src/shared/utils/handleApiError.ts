import axios from 'axios'
import { toast } from 'vue-sonner'
import { mapLaravelErrors } from '@/shared/utils/laravelErrorMapper'

export const handleApiError = (error: unknown): void => {
  if (axios.isAxiosError(error)) {
    const status = error.response?.status

    if (status === 422) {
      const messages = mapLaravelErrors((error.response?.data ?? {}) as {
        message?: string
        errors?: Record<string, string[]>
      })

      if (messages.length > 0) {
        messages.forEach((msg) => toast.error(msg))
      } else {
        toast.error('Erro de validação.')
      }
      return
    }

    if (status === 403) {
      toast.error('Sem permissão para esta ação.')
      return
    }

    if (status === 401) {
      toast.error('Sessão expirada. Faça login novamente.')
      return
    }
  }

  toast.error('Erro inesperado.')
}
