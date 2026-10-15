// ユーザー情報
export type User = {
  id: number
  name: string
  email: string
  roles: {
    id: number
    name: string
  }[]
}

// ユーザー一覧APIのレスポンス
export type PaginatedUsers = {
  data: User[]
  current_page: number
  last_page: number
}

// 登録・編集フォームの入力値
export type UserFormData = {
  name: string
  email: string
  password: string
  password_confirmation: string
  admin: boolean
}