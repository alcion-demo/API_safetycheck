import { useState, type FormEvent } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import axios from 'axios'
import { resetPassword } from './auth'

const ResetPasswordPage = () => {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()

  const token = searchParams.get('token') ?? ''
  const initialEmail = searchParams.get('email') ?? ''

  const [email, setEmail] = useState(initialEmail)
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setMessage('')
    setError('')

    if (!token) {
      setError('再設定リンクが無効です。メールからリンクを開き直してください。')
      return
    }

    if (password !== passwordConfirmation) {
      setError('パスワードが一致しません。')
      return
    }

    setSubmitting(true)

    try {
      const response = await resetPassword({
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })

      setMessage(
        response.message ??
          'パスワードを再設定しました。ログインしてください。',
      )
      setPassword('')
      setPasswordConfirmation('')
    } catch (err: unknown) {
      if (axios.isAxiosError(err)) {
        const data = err.response?.data
        setError(
          data?.errors?.email?.[0] ??
            data?.errors?.password?.[0] ??
            data?.message ??
            'パスワードを再設定できませんでした。',
        )
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
        <h1>新しいパスワードの設定</h1>

        <form onSubmit={handleSubmit}>
          <label htmlFor="email">メールアドレス</label>
          <input
            id="email"
            type="email"
            autoComplete="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            required
          />

          <label htmlFor="password">新しいパスワード</label>
          <input
            id="password"
            type="password"
            autoComplete="new-password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
          />

          <label htmlFor="password-confirmation">新しいパスワード（確認）</label>
          <input
            id="password-confirmation"
            type="password"
            autoComplete="new-password"
            value={passwordConfirmation}
            onChange={(event) => setPasswordConfirmation(event.target.value)}
            required
          />

          {message && <p role="status">{message}</p>}

          {error && (
            <p className="error-message" role="alert">
              {error}
            </p>
          )}

          {message ? (
            <button
              type="button"
              className="primary-button"
              onClick={() => navigate('/')}
            >
              ログイン画面へ
            </button>
          ) : (
            <button
              type="submit"
              className="primary-button"
              disabled={submitting}
            >
              {submitting ? '変更中...' : 'パスワードを再設定'}
            </button>
          )}

          <p>
            <Link to="/">ログイン画面に戻る</Link>
          </p>
        </form>
      </section>
    </main>
  )
}

export default ResetPasswordPage