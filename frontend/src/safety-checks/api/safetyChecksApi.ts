import api from '../../api/client'
import type { SafetyCheck } from './types'

export const getSafetyChecks = async (): Promise<SafetyCheck[]> => {
  const response = await api.get<SafetyCheck[]>('/api/safety-checks')

  return response.data
}

export const activateSafetyCheck = async (
  id: number,
): Promise<SafetyCheck> => {
  const response = await api.post<SafetyCheck>(
    `/api/safety-checks/${id}/activate`,
  )
  return response.data
}

export const closeSafetyCheck = async (
  id: number,
): Promise<SafetyCheck> => {
  const response = await api.post<SafetyCheck>(
    `/api/safety-checks/${id}/close`,
  )
  return response.data
}

export const createSafetyCheck = async (
  data: {
    title: string
    message: string
    type: 'training'
    scheduled_at: string
  },
): Promise<SafetyCheck> => {
  const response = await api.post<SafetyCheck>(
    '/api/safety-checks',
    data,
  )
  return response.data
}

export const updateSafetyCheck = async (
  id: number,
  data: {
    title: string
    message: string
    scheduled_at: string
  },
): Promise<SafetyCheck> => {
  const response = await api.put<SafetyCheck>(
    `/api/safety-checks/${id}`,
    data,
  )
  return response.data
}

export const deleteSafetyCheck = async (
  id: number,
): Promise<void> => {
  await api.delete(`/api/safety-checks/${id}`)
}
