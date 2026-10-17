import api, { getCsrfCookie } from '../../api/client'

type LoginCredentials = {
  email: string
  password: string
}

type RegisterCredentials = {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export const login = async (credentials: LoginCredentials) => {

  console.log('1. CSRF Cookie取得開始')
  await getCsrfCookie()

  console.log('2. ログインPOST開始')
  await api.post('/login', credentials)

  console.log('3. ユーザー情報取得開始')
  const loginResponse = await api.get('/api/user')

  console.log('4. ログイン成功')
  return loginResponse.data

}

export const register = async (credentials: RegisterCredentials) => {
  await getCsrfCookie()

  await api.post('/register', credentials)
}

export const logout = async () => {
  await api.post('/logout')
}

export const getCurrentUser = async () => {
  const response = await api.get('/api/user')

  return response.data
}

export const forgotPassword = async (email: string) => {
  await getCsrfCookie()
  const response = await api.post('/forgot-password', { email })
  return response.data
}

export const resetPassword = async (data: {
  token: string
  email: string
  password: string
  password_confirmation: string
}) => {
  await getCsrfCookie()
  const response = await api.post('/reset-password', data)
  return response.data
}