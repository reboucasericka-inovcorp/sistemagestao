export function normalizeListResponse(response: any) {
  if (response.data?.data) {
    return response.data
  }

  return {
    data: response.data,
    meta: null,
  }
}
