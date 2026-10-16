import { useState } from 'react'
import { NavLink } from 'react-router-dom'

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

          {user.roles.includes('admin') && (
            <NavLink
              to="/admin/safety-checks"
              className={({ isActive }) =>
                `app-nav-link ${isActive ? 'active' : ''}`
              }
              onClick={() => setMenuOpen(false)}
            >
              安否確認管理
            </NavLink>
          )}

          <NavLink
            to="/profile"
            className={({ isActive }) =>
              `app-nav-link ${isActive ? 'active' : ''}`
            }
            onClick={() => setMenuOpen(false)}
          >
            プロフィール変更
          </NavLink>
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

export default AuthenticatedLayout