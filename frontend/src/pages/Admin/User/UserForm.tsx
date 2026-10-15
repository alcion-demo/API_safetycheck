import { useEffect, useState, type SubmitEvent } from 'react'
import type { User, UserFormData } from './types'

type Props = {
  user: User | null
  onSubmit: (data: UserFormData) => void
  onCancel: () => void
  submitting?: boolean
  errors?: Record<string, string[]>
  formError?: string
}

const UserForm = ({
  user,
  onSubmit,
  onCancel,
  submitting = false,
  errors = {},
  formError = '',
}: Props) => {
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [admin, setAdmin] = useState(false)

  useEffect(() => {
    setName(user?.name ?? '')
    setEmail(user?.email ?? '')
    setPassword('')
    setPasswordConfirmation('')
    setAdmin(user?.roles.some((role) => role.name === 'admin') ?? false)
  }, [user])

  const handleSubmit = async (event: SubmitEvent<HTMLFormElement>) => {
    event.preventDefault()

    onSubmit({
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
      admin,
    })
  }

  return (
    <form onSubmit={handleSubmit} className="user-form">
      <h2>{user ? 'ユーザー編集' : 'ユーザー新規登録'}</h2>

      {formError && (
        <p className="error-message" role="alert">
          {formError}
        </p>
      )}

      <div>
        <label htmlFor="user-name">名前</label>
        <input
          id="user-name"
          type="text"
          value={name}
          onChange={(event) => setName(event.target.value)}
        />
        {errors.name?.map((message) => (
          <p className="error-message" key={message}>
            {message}
          </p>
        ))}
      </div>

      <div>
        <label htmlFor="user-email">メールアドレス</label>
        <input
          id="user-email"
          type="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
        />
        {errors.email?.map((message) => (
          <p className="error-message" key={message}>
            {message}
          </p>
        ))}
      </div>

      <div>
        <label htmlFor="user-password">
          パスワード{user ? '（変更する場合のみ入力）' : ''}
        </label>
        <input
          id="user-password"
          type="password"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          autoComplete="new-password"
        />
        {errors.password
          ?.filter((message) => !message.includes('一致しません'))
          .map((message) => (
            <p className="error-message" key={message}>
              {message}
            </p>
          ))}
      </div>

      <div>
        <label htmlFor="user-password-confirmation">パスワード確認</label>
        <input
          id="user-password-confirmation"
          type="password"
          value={passwordConfirmation}
          onChange={(event) => setPasswordConfirmation(event.target.value)}
          autoComplete="new-password"
        />
        {errors.password_confirmation?.map((message) => (
          <p className="error-message" key={message}>
            {message}
          </p>
        ))}
      </div>

      <div className="user-form-checkbox">
        <label>
          <input
            type="checkbox"
            checked={admin}
            onChange={(event) => setAdmin(event.target.checked)}
          />
          管理者権限を付与する
        </label>
      </div>

      <div className="user-form-actions">
        <button type="submit" disabled={submitting}>
          {submitting ? '処理中...' : user ? '更新' : '登録'}
        </button>

        <button type="button" onClick={onCancel} disabled={submitting}>
          キャンセル
        </button>
      </div>
    </form>
  )
}

export default UserForm