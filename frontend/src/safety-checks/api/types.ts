export type SafetyCheck = {
  id: number
  type: 'training' | 'production'
  title: string
  message: string
  scheduled_at: string | null
  status: 'scheduled' | 'active' | 'closed'
  started_at: string | null
  ended_at: string | null
  created_at: string
  updated_at: string
}

export type SafetyCheckResponseItem = {
  id: number | null
  safety_check_id: number
  user_id: number
  status: 'safe' | 'serious' | 'traffic' | 'unknown' | 'not_applicable'| null
  memo: string | null
  created_at: string | null
  updated_at?: string | null
  user: {
    id: number
    name: string
    email: string
  } | null
}