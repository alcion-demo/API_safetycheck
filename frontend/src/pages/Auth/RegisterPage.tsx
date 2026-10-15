import { useState, type SubmitEvent } from 'react'
import axios from 'axios'
import { register, getCurrentUser } from './auth'

type User = {
  id: number
  name: string
  email: string
  roles: string[]
}

type RegisterPageProps = {
  onRegisterSuccess: (user: User) => void
  onBackToLogin: () => void
}

const RegisterPage = ({
  onRegisterSuccess,
  onBackToLogin,
}: RegisterPageProps) => {
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const handleRegister = async (event: SubmitEvent<HTMLFormElement>) => {
    event.preventDefault()
    setError('')
    setSubmitting(true)

    try {
      await register({
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })
    } catch (err: unknown) {
      if (axios.isAxiosError(err)) {
        setError(
          err.response?.data?.message ??
          '登録に失敗しました。入力内容を確認してください。'
        )
      } else {
        setError('登録時に通信エラーが発生しました。')
      }
      return
    }

    try {
      const user = await getCurrentUser()
      onRegisterSuccess(user)
      } catch (err: unknown) {
        if (axios.isAxiosError(err)) {
          console.error('自動ログイン失敗:', {
            status: err.response?.status,
            data: err.response?.data,
            url: err.config?.url,
          })

          setError(
            `登録は成功しましたが、ログインに失敗しました（HTTP ${
              err.response?.status ?? '通信エラー'
            }）。`
          )
        } else {
          setError('登録は成功しましたが、ログイン時にエラーが発生しました。')
        }
      } finally {
        setSubmitting(false)
      }
  }

  return (
    <main className="auth-container">
      <section className="auth-card">
        <h1>安否確認システム</h1>

        <form onSubmit={handleRegister}>
          <label htmlFor="name">名前</label>
          <input
            id="name"
            type="text"
            autoComplete="name"
            value={name}
            onChange={(event) => setName(event.target.value)}
            required
          />

          <label htmlFor="email">メールアドレス</label>
          <input
            id="email"
            type="email"
            autoComplete="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            required
          />

          <label htmlFor="password">パスワード</label>
          <input
            id="password"
            type="password"
            autoComplete="new-password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
          />

          <label htmlFor="password_confirmation">パスワード（確認）</label>
          <input
            id="password_confirmation"
            type="password"
            autoComplete="new-password"
            value={passwordConfirmation}
            onChange={(event) =>
              setPasswordConfirmation(event.target.value)
            }
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
            {submitting ? '登録中...' : '登録する'}
          </button>
        </form>

        <p className="login-link">
          登録済みの方は{' '}
          <button
            type="button"
            className="text-link"
            onClick={onBackToLogin}
          >
            ログイン画面へ戻る
          </button>
        </p>

      </section>
    </main>
  )
}

export default RegisterPage