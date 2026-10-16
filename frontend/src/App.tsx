import { useEffect, useState } from 'react'
import { Navigate, Route, Routes, useNavigate } from 'react-router-dom'
import { logout, getCurrentUser } from './pages/Auth/auth'
import LoginPage from './pages/Auth/LoginPage'
import RegisterPage from './pages/Auth/RegisterPage'
import UserListPage from './pages/Admin/User/UserListPage'
import ProfilePage from './pages/Profile/ProfilePage'
import SafetyCheckListPage from './pages/Admin/SafetyCheck/ListPage'
import SafetyCheckCreatePage from './pages/Admin/SafetyCheck/CreatePage'
import AuthenticatedLayout from './components/layout/AuthenticatedLayout'
import HomePage from './pages/Home/HomePage'
import SafetyCheckEditPage from './pages/Admin/SafetyCheck/SafetyCheckEditPage'
import './App.css'

type User = {
  id: number
  name: string
  
  email: string
  roles: string[]
}

const App = () => {
  const [user, setUser] = useState<User | null>(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  const navigate = useNavigate()

  useEffect(() => {
    let mounted = true

    getCurrentUser()
      .then((currentUser: User) => {
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
    <Routes>
      <Route
        path="/register"
        element={
          user ? (
            <Navigate to="/" replace />
          ) : (
            <RegisterPage
              onRegisterSuccess={(newUser: User) => {
                setUser(newUser)
                navigate('/')
              }}
              onBackToLogin={() => navigate('/')}
            />
          )
        }
      />

      <Route
        path="/"
        element={
          !user ? (
            <LoginPage
              onLoginSuccess={setUser}
              onShowRegister={() => navigate('/register')}
            />
          ) : (
            <AuthenticatedLayout
              user={user}
              error={error}
              onLogout={handleLogout}
            >
              <HomePage user={user} />
            </AuthenticatedLayout>
          )
        }
      />

      <Route
        path="/admin/users"
        element={
          !user ? (
            <Navigate to="/" replace />
          ) : !user.roles.includes('admin') ? (
            <Navigate to="/" replace />
          ) : (
            <AuthenticatedLayout
              user={user}
              error={error}
              onLogout={handleLogout}
            >
              <UserListPage />
            </AuthenticatedLayout>
          )
        }
      />

      <Route
        path="/admin/safety-checks"
        element={
          user?.roles.includes('admin') ? (
            <AuthenticatedLayout
              user={user}
              error={error}
              onLogout={handleLogout}
            >
              <SafetyCheckListPage />
            </AuthenticatedLayout>
          ) : (
            <Navigate to="/" replace />
          )
        }
      />

      <Route
        path="/admin/safety-checks/create"
        element={
          user?.roles.includes('admin') ? (
            <AuthenticatedLayout
              user={user}
              error={error}
              onLogout={handleLogout}
            >
              <SafetyCheckCreatePage />
            </AuthenticatedLayout>
          ) : (
            <Navigate to="/" replace />
          )
        }
      />

      <Route
        path="/admin/safety-checks/:id/edit"
        element={<SafetyCheckEditPage  />}
      />

      <Route
        path="/profile"
        element={
          !user ? (
            <Navigate to="/" replace />
          ) : (
            <AuthenticatedLayout
              user={user}
              error={error}
              onLogout={handleLogout}
            >
              <ProfilePage
                user={user}
                onProfileUpdated={setUser}
              />
            </AuthenticatedLayout>
          )
        }
      />

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}

export default App