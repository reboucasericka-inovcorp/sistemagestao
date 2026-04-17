import { ref } from 'vue'
import { toast } from 'vue-sonner'

type ApiActionOptions<T> = {
  successMessage?: string
  errorMessage?: string
  onSuccess?: (data: T) => void
}

export function useApiAction<T = any>() {
  const loading = ref(false)

  const execute = async (action: () => Promise<any>, options: ApiActionOptions<T> = {}): Promise<T | null> => {
    loading.value = true

    try {
      const response = await action()
      const payload = response?.data

      if (!payload || typeof payload !== 'object') {
        throw new Error('Resposta inválida do servidor')
      }

      const message = options.successMessage || payload.message || 'Operação realizada com sucesso'
      toast.success(message)

      const data = 'data' in payload ? (payload.data as T | null | undefined) : undefined
      const result = (data === undefined ? (payload as T) : (data as T | null)) as T | null

      if (options.onSuccess) {
        options.onSuccess(result as T)
      }

      return result
    } catch (error: any) {
      const message = error?.response?.data?.message || options.errorMessage || 'Ocorreu um erro'
      toast.error(message)

      return null
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    execute,
  }
}
