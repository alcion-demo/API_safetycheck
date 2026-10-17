import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { logout, getCurrentUser } from './pages/Auth/auth'
import AppRoutes from './routes/AppRoutes'
import type { User } from './routes/AppRoutes'
import './App.css'

const App = () => {
  const [user, setUser] = useState<User | null>(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  const navigate = useNavigate()

  useEffect(() => {
    let mounted = true

    getCurrentUser()
      .then((currentUser: User) => {
        console.log('現在のユーザー取得成功:', currentUser)
        if (mounted) setUser(currentUser)
      })
      .catch(() => {})
      .finally(() => {
        if (mounted) setLoading(false)
      })

    return () => {
      mounted = false
    }
  }, [])

  const handleLogout = async () => {
    setError('')

    try {
      await logout()
      setUser(null)
      navigate('/')
    } catch {
      setError('ログアウトに失敗しました。もう一度お試しください。')
    }
  }

  if (loading) {
    return (
      <main className="auth-container">
        <p>ログイン状態を確認しています...</p>
      </main>
    )
  }

  return (
    <AppRoutes
      user={user}
      setUser={setUser}
      error={error}
      handleLogout={handleLogout}
    />
  )
}

export default App