import type { User } from './types'

type Props = {
  users: User[]
  onEdit: (user: User) => void
  onDelete: (user: User) => void
}

const UserCardList = ({ users, onEdit, onDelete }: Props) => {
  return (
    <div className="mobile-user-list">
      {users.map((user) => (
        <article className="mobile-user-card" key={user.id}>
          <div className="mobile-user-heading">
            <strong>{user.name}</strong>
            <span className="mobile-user-id">ID: {user.id}</span>
          </div>

          <dl className="mobile-user-info">
            <dt>メールアドレス</dt>
            <dd>{user.email}</dd>

            <dt>ロール</dt>
            <dd>
              {user.roles.map((role) => role.name).join(', ') || '未設定'}
            </dd>
          </dl>

          <div className="mobile-user-actions">
            <button type="button" onClick={() => onEdit(user)}>
              編集
            </button>
            <button type="button" onClick={() => onDelete(user)}>
              削除
            </button>
          </div>
        </article>
      ))}
    </div>
  )
}

export default UserCardList