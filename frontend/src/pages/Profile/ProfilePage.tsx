
import { useState } from 'react'
import api from '../../api/client'

type ProfileUser = {
  id: number
  name: string
  email: string
  roles: string[]
}

type ProfilePageProps = {
  user: ProfileUser
  onProfileUpdated: (user: ProfileUser) => void
}

type ValidationErrors = Record<string, string[]>

const ProfilePage = ({ user, onProfileUpdated }: ProfilePageProps) => {
  const [name, setName] = useState(user.name)
  const [email, setEmail] = useState(user.email)
  const [currentPassword, setCurrentPassword] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [message, setMessage] = useState('')
  const [errors, setErrors] = useState<ValidationErrors>({})
  const [loading, setLoading] = useState(false)//処理中か判定

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    setMessage('')
    setErrors({})
    setLoading(true)

    try {
      const response = await api.patch('/api/profile', {
        name,
        email,
        current_password: currentPassword || undefined,
        password: password || undefined,
        password_confirmation: passwordConfirmation || undefined,
      })

      const updatedUser: ProfileUser = response.data.user

      onProfileUpdated(updatedUser)

      setName(updatedUser.name)
      setEmail(updatedUser.email)
      setCurrentPassword('')
      setPassword('')
      setPasswordConfirmation('')
      setMessage(response.data.message)
    } catch (error: unknown) {
      if (
        typeof error === 'object' &&
        error !== null &&
        'response' in error
      ) {
        const response = (
          error as {
            response?: {
              data?: {
                message?: string
                errors?: ValidationErrors
              }
            }
          }
        ).response

        setErrors(response?.data?.errors ?? {})
        setMessage(
          response?.data?.message ??
            'プロフィールの更新に失敗しました。',
        )
      } else {
        setMessage('通信に失敗しました。もう一度お試しください。')
      }
    } finally {
      setLoading(false)
    }
  }

  const getError = (field: string) => errors[field]?.[0]

  return (
    <section className="auth-card profile-card">
      <h1>プロフィール変更</h1>

      {message && Object.keys(errors).length === 0 && (
        <p className="success-message">
          {message}
        </p>
      )}

      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label htmlFor="profile-name">名前</label>
          <input
            id="profile-name"
            name="name"
            type="text"
            value={name}
            onChange={(event) => setName(event.target.value)}
            maxLength={255}
          />
          {getError('name') && (
            <p className="error-message" role="alert">{getError('name')}</p>
          )}
        </div>

        <div className="form-group">
          <label htmlFor="profile-email">メールアドレス</label>
          <input
            id="profile-email"
            name="email"
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            maxLength={255}
          />
          {getError('email') && (
            <p className="error-message" role="alert">{getError('email')}</p>
          )}
        </div>

        <fieldset className="password-fieldset">
          <legend>パスワード変更（変更する場合のみ入力）</legend>

          <div>
            <label htmlFor="current-password">現在のパスワード</label>
            <input
              id="current-password"
              name="current_password"
              type="password"
              value={currentPassword}
              onChange={(event) => setCurrentPassword(event.target.value)}
              autoComplete="current-password"
            />
            {getError('current_password') && (
              <p className="error-message" role="alert">{getError('current_password')}</p>
            )}
          </div>

          <div>
            <label htmlFor="new-password">新しいパスワード</label>
            <input
              id="new-password"
              name="password"
              type="password"
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              autoComplete="new-password"
            />
            {getError('password') && (
              <p className="error-message" role="alert">{getError('password')}</p>
            )}
          </div>

          <div>
            <label htmlFor="password-confirmation">
              新しいパスワード（確認）
            </label>
            <input
              id="password-confirmation"
              name="password_confirmation"
              type="password"
              value={passwordConfirmation}
              onChange={(event) =>
                setPasswordConfirmation(event.target.value)
              }
              autoComplete="new-password"
            />
            {getError('password_confirmation') && (
              <p className="error-message" role="alert">{getError('password_confirmation')}</p>
            )}
          </div>
        </fieldset>

        <button type="submit" className="primary-button" disabled={loading}>
          {loading ? '更新中...' : 'プロフィールを更新'}
        </button>
      </form>
    </section>
  )
}

export default ProfilePage