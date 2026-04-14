export function normalizeListResponse(payload: any) {
  if (Array.isArray(payload?.data)) {
    return {
      data: payload.data,
      meta: payload?.meta ?? null,
    }
  }

  if (payload?.data && typeof payload.data === 'object' && Array.isArray(payload.data?.data)) {
    return {
      data: payload.data.data,
      meta: payload.data?.meta ?? payload?.meta ?? null,
    }
  }

  if (Array.isArray(payload)) {
    return {
      data: payload,
      meta: null,
    }
  }

  return {
    data: [],
    meta: null,
  }
}
