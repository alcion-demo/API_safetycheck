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