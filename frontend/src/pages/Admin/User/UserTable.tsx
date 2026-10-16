import type { User } from './types'

type Props = {
  users: User[]
  onEdit: (user: User) => void
  onDelete: (user: User) => void
}
//PC・タブレット用
const UserTable = ({ users, onEdit, onDelete }: Props) => {
  return (
    <div className="user-table-wrapper desktop-user-list">
      <table className="user-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>名前</th>
            <th>メールアドレス</th>
            <th>ロール</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          {users.map((user) => (
            <tr key={user.id}>
              <td>{user.id}</td>
              <td>{user.name}</td>
              <td>{user.email}</td>
              <td>
                {user.roles.map((role) => role.name).join(', ') || '未設定'}
              </td>
              <td className="user-actions">
                <button type="button" onClick={() => onEdit(user)}>
                  編集
                </button>
                <button type="button" onClick={() => onDelete(user)}>
                  削除
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

export default UserTable