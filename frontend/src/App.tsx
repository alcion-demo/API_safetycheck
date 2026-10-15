import { useEffect, useState } from 'react'
import {
  Navigate,
  NavLink,
  Route,
  Routes,
  useNavigate,
} from 'react-router-dom'
import { logout, getCurrentUser } from './pages/Auth/auth'
import LoginPage from './pages/Auth/LoginPage'
import RegisterPage from './pages/Auth/RegisterPage'
import UserListPage from './pages/Admin/User/UserListPage'
import './App.css'

type User = {
  id: number
  name: string
  email: string
  roles: string[]
}

type LayoutProps = {
  user: User
  error: string
  onLogout: () => void
  children: React.ReactNode
}

const AuthenticatedLayout = ({
  user,
  error,
  onLogout,
  children,
}: LayoutProps) => {
  const [menuOpen, setMenuOpen] = useState(false)

  return (
    <div className="app-layout">
      <header className="app-header">
        <NavLink to="/" className="app-brand">
          安否確認システム
        </NavLink>

        <div className="app-header-user">
          <span>{user.name}</span>
          <button
            type="button"
            className="logout-button"
            onClick={onLogout}
          >
            ログアウト
          </button>
        </div>

        <button
          type="button"
          className="menu-toggle"
          aria-label="メニューを開閉"
          aria-expanded={menuOpen}
          onClick={() => setMenuOpen((open) => !open)}
        >
          ☰
        </button>
      </header>

      <div className="app-body">
        <nav
          className={`app-sidebar ${menuOpen ? 'is-open' : ''}`}
          aria-label="メインメニュー"
        >
          <NavLink
            to="/"
            end
            className={({ isActive }) =>
              `app-nav-link ${isActive ? 'active' : ''}`
            }
            onClick={() => setMenuOpen(false)}
          >
            ホーム
          </NavLink>

          {user.roles.includes('admin') && (
            <NavLink
              to="/admin/users"
              className={({ isActive }) =>
                `app-nav-link ${isActive ? 'active' : ''}`
              }
              onClick={() => setMenuOpen(false)}
            >
              ユーザー管理
            </NavLink>
          )}
        </nav>

        <main className="app-content">
          {error && (
            <p className="error-message" role="alert">
              {error}
            </p>
          )}
          {children}
        </main>
      </div>
    </div>
  )
}

const HomePage = ({ user }: { user: User }) => (
  <section className="home-page">
    <h1>ホーム</h1>
    <p>ログインしました。</p>

    <dl className="user-info">
      <dt>ユーザー名</dt>
      <dd>{user.name}</dd>

      <dt>メールアドレス</dt>
      <dd>{user.email}</dd>
    </dl>
  </section>
)

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

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}

export default App