import { useState, type FormEvent } from 'react'
import axios from 'axios'
import { forgotPassword } from './auth'

type ForgotPasswordPageProps = {
  onBackToLogin: () => void
}

const ForgotPasswordPage = ({
  onBackToLogin,
}: ForgotPasswordPageProps) => {
  const [email, setEmail] = useState('')
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setMessage('')
    setError('')
    setSubmitting(true)

    try {
      const response = await forgotPassword(email)
      setMessage(
        response.message ??
          'パスワード再設定の案内を送信しました。メールをご確認ください。',
      )
    } catch (err: unknown) {
      if (axios.isAxiosError(err)) {
        console.error('パスワード再設定エラー:', err.response?.status, err.response?.data)
        const data = err.response?.data
        setError(
          data?.errors?.email?.[0] ??
            data?.message ??
            'メールの送信に失敗しました。入力内容をご確認ください。',
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
        <h1>パスワード再設定</h1>
        <p>登録済みのメールアドレスを入力してください。</p>

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

          {message && (
            <p role="status">{message}</p>
          )}

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
            {submitting ? '送信中...' : '再設定メールを送信'}
          </button>

          <p>
            <button
              type="button"
              className="text-link"
              onClick={onBackToLogin}
            >
              ログイン画面に戻る
            </button>
          </p>
        </form>
      </section>
    </main>
  )
}

export default ForgotPasswordPage