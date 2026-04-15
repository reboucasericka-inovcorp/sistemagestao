import { ref } from 'vue'
import { getCompany } from '@/modules/settings/company/services/companyService'
import type { Company } from '@/modules/settings/company/types/company'

const company = ref<Company | null>(null)
const loading = ref(false)
const loaded = ref(false)
let pendingLoad: Promise<void> | null = null

export function useCompany() {
  async function loadCompany(): Promise<void> {
    if (loaded.value) {
      return
    }

    if (pendingLoad) {
      await pendingLoad
      return
    }

    pendingLoad = (async () => {
      loading.value = true
      try {
        const data = await getCompany()
        company.value = data
        loaded.value = true
      } finally {
        loading.value = false
        pendingLoad = null
      }
    })()

    await pendingLoad
  }

  return {
    company,
    loading,
    loaded,
    loadCompany,
  }
}
