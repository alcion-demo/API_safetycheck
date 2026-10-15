import { useState, type SubmitEvent } from 'react'
import axios from 'axios'
import { login } from './auth'

type User = {
  id: number
  name: string
  email: string
  roles: string[]
}

type LoginPageProps = {
  onLoginSuccess: (user: User) => void
  onShowRegister: () => void
}

const LoginPage = ({ onLoginSuccess, onShowRegister }: LoginPageProps) => {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const handleLogin = async (event: SubmitEvent<HTMLFormElement>) => {
    event.preventDefault()
    setError('')
    setSubmitting(true)

    try {
      const user = await login({ email, password })
      onLoginSuccess(user as User)
      setPassword('')
    } catch (err: unknown) {
      if (axios.isAxiosError(err)) {
        const data = err.response?.data
        const message =
          data?.errors?.email?.[0] ??
          data?.message ??
          'ログインに失敗しました。入力内容を確認してください。'

        setError(message)
      } else {
        setError('通信エラーが発生しました。')
      }
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="auth-container">
      <section className="auth-card">
        <h1>安否確認システム</h1>

        <form onSubmit={handleLogin}>
          <label htmlFor="email">メールアドレス</label>
          <input
            id="email"
            type="email"
            autoComplete="username"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            required
          />

          <label htmlFor="password">パスワード</label>
          <input
            id="password"
            type="password"
            autoComplete="current-password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
          />

          {error && (
            <p className="error-message" role="alert">
              {error}
            </p>
          )}

          <button
            type="submit"
            className="primary-button"
            disabled={submitting}
          >
            {submitting ? 'ログイン中...' : 'ログイン'}
          </button>

          <p className="register-link">
            アカウントをお持ちでない方は{' '}
            <button
              type="button"
              className="text-link"
              onClick={onShowRegister}
            >
              新規登録
            </button>
          </p>
        </form>
      </section>
    </main>
  )
}

export default LoginPage