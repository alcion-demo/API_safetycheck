import { useEffect, useState } from 'react'
import api from '../../../api/client'
import type { User, PaginatedUsers, UserFormData } from './types'
import UserTable from './UserTable'
import UserCardList from './UserCardList'
import UserForm from './UserForm'
import axios from 'axios'

const UserListPage = () => {
  const [users, setUsers] = useState<User[]>([])
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [error, setError] = useState('')
  const [editingUser, setEditingUser] = useState<User | null>(null)
  const [showForm, setShowForm] = useState(false)
  const [validationErrors, setValidationErrors] = useState<
    Record<string, string[]>
  >({})
  const [formError, setFormError] = useState('')

  const handleEdit = (user: User) => {
    setEditingUser(user)
    setShowForm(true)
  }

  const handleUpdate = async (data: UserFormData) => {
    if (!editingUser) return

    setError('')
    setFormError('')
    setValidationErrors({})

    try {
      await api.put(`/api/users/${editingUser.id}`, data)

      const response = await api.get<PaginatedUsers>('/api/users')
      setUsers(response.data.data)

      setShowForm(false)
      setEditingUser(null)
    } catch (error) {
      if (
        axios.isAxiosError(error) &&
        error.response?.status === 422
      ) {
        setValidationErrors(error.response.data.errors ?? {})
        setFormError(error.response.data.message ?? '')
        return
      }

      setError('ユーザーの更新に失敗しました。')
    }
  }

  const handleCreate = async (data: UserFormData) => {
    setError('')
    setValidationErrors({})

    try {
      await api.post('/api/users', data)

      const response = await api.get<PaginatedUsers>('/api/users')
      setUsers(response.data.data)

      setShowForm(false)
      setEditingUser(null)
    } catch (error) {
      if (
        axios.isAxiosError(error) &&
        error.response?.status === 422
      ) {
        setValidationErrors(
          error.response.data.errors ?? {},
        )
        return
      }

      setError('ユーザーの登録に失敗しました。')
    }
  }

  const handleDelete = async (user: User) => {
    const confirmed = window.confirm(
      `${user.name} さんを削除しますか？`,
    )

    if (!confirmed) return

    try {
      await api.delete(`/api/users/${user.id}`)
      setUsers((currentUsers) =>
        currentUsers.filter((currentUser) => currentUser.id !== user.id),
      )
    } catch {
      setError('ユーザーの削除に失敗しました。')
    }
  }


  useEffect(() => {
    const fetchUsers = async () => {
      setError('')

      try {
        const response = await api.get<PaginatedUsers>('/api/users', {
          params: {
            search: search,
          },
        })

        setUsers(response.data.data)
      } catch (error) {
        if (axios.isAxiosError(error)) {
          console.log('status:', error.response?.status)
          console.log('response:', error.response?.data)
        }

        setError('ユーザー一覧の取得に失敗しました。')
      } finally {
        setLoading(false)
      }
    }

    fetchUsers()
  }, [search])

  if (loading) {
    return <main className="auth-container"><p>ユーザー一覧を読み込んでいます...</p></main>
  }

  if (error) {
    return <main className="auth-container"><p className="error-message" role="alert">{error}</p></main>
  }

  return (
    <main className="auth-container user-management-page">
      <section className="auth-card user-management-card">
        <div className="user-management-heading">
          <div>
            <h1>ユーザー管理</h1>
          </div>

          <button
            type="button"
            onClick={() => {
              setEditingUser(null)
              setShowForm(true)
            }}
          >
            新規登録
          </button>
        </div>

        <div className="user-search">
          <input
            type="search"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="名前・メールアドレスで検索"
            aria-label="名前・メールアドレスで検索"
          />
        </div>

        {showForm && (
          <UserForm
            user={editingUser}
            onSubmit={editingUser ? handleUpdate : handleCreate}
            onCancel={() => {
              setShowForm(false)
              setEditingUser(null)
              setValidationErrors({})
              setFormError('')
            }}
            errors={validationErrors}
            formError={formError}
          />
        )}

        {users.length === 0 ? (
          <p>登録ユーザーはいません。</p>
        ) : (
          <>
            {/* PC・タブレット用テーブル */}
            <div className="user-table-wrapper desktop-user-list">
              <UserTable
                users={users}
                onEdit={handleEdit}
                onDelete={handleDelete}
              />
            </div>

            {/* スマホ用カード */}
            <div className="mobile-user-list">
              <UserCardList
                users={users}
                onEdit={handleEdit}
                onDelete={handleDelete}
              />
            </div>
          </>
        )}
      </section>
    </main>
  )
}

export default UserListPage