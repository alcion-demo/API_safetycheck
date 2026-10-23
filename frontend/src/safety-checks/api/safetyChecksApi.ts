import api from '../../api/client'
import type { SafetyCheck, SafetyCheckResponseItem } from './types'

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

export const closeProductionSafetyCheck = async (
  id: number,
): Promise<SafetyCheck> => {
  const response = await api.post<SafetyCheck>(
    `/api/safety-checks/${id}/close-production`,
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

export type SafetyCheckResponseStatus =
  | 'safe'
  | 'serious'
  | 'traffic'
  | 'unknown'
  | 'not_applicable'

export const submitSafetyCheckResponse = async (
  id: number,
  data: {
    status: SafetyCheckResponseStatus
    memo?: string
  },
): Promise<void> => {
  await api.post(
    `/api/safety-checks/${id}/responses`,
    data,
  )
}

export const getSafetyCheck = async (
  id: number,
): Promise<SafetyCheck> => {
  const response = await api.get<SafetyCheck>(
    `/api/safety-checks/${id}`,
  )

  return response.data
}

export const getSafetyCheckResponses = async (
  id: number,
): Promise<SafetyCheckResponseItem[]> => {
  const response = await api.get<{
    responses: SafetyCheckResponseItem[]
  }>(`/api/safety-checks/${id}/responses`)

  return response.data.responses
}

export const getMyUnansweredSafetyChecks = async (): Promise<SafetyCheck[]> => {
  const response = await api.get<SafetyCheck[]>(
    '/api/my/unanswered-safety-checks'
  )
  return response.data
}