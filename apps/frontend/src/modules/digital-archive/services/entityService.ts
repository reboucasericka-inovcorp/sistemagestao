import api from '@/shared/services/api'

export const getEntities = (params?: any) => api.get('/entities', { params })
