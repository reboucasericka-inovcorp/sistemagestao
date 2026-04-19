import { ref } from 'vue'
import { peekAuthenticatedUser } from '@/modules/auth/services/authService'
import { getCompany } from '@/modules/settings/company/services/companyService'
import type { Company } from '@/modules/settings/company/types/company'

const company = ref<Company | null>(null)
const loading = ref(false)
const loaded = ref(false)
let pendingLoad: Promise<void> | null = null

export function useCompany() {
  async function loadCompany(): Promise<void> {
    const sessionUser = peekAuthenticatedUser()
    if (!sessionUser) {
      return
    }

    if (loaded.value) {
      return
    }

    if (pendingLoad) {
      await pendingLoad
      return
    }
    if (!peekAuthenticatedUser()) return

    pendingLoad = (async () => {
      loading.value = true
      try {
        const data = await getCompany()
        company.value = data
      } catch {
        /** Ex.: 403 sem `company.read` — shell da app não depende disto */
        company.value = null
      } finally {
        loaded.value = true
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
