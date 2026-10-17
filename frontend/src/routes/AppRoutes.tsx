import { Navigate, Route, Routes, useLocation, useNavigate } from 'react-router-dom'
import type { Dispatch, SetStateAction } from 'react'
import LoginPage from '../pages/Auth/LoginPage'
import RegisterPage from '../pages/Auth/RegisterPage'
import UserListPage from '../pages/Admin/User/UserListPage'
import ProfilePage from '../pages/Profile/ProfilePage'
import SafetyCheckListPage from '../pages/Admin/SafetyCheck/ListPage'
import SafetyCheckCreatePage from '../pages/Admin/SafetyCheck/CreatePage'
import SafetyCheckEditPage from '../pages/Admin/SafetyCheck/SafetyCheckEditPage'
import ForgotPasswordPage from '../pages/Auth/ForgotPasswordPage'
import ResetPasswordPage from '../pages/Auth/ResetPasswordPage'
import AuthenticatedLayout from '../components/layout/AuthenticatedLayout'
import HomePage from '../pages/Home/HomePage'
import ResponsePage from '../safety-checks/ResponsePage'
import ResponseListPage from '../pages/Admin/SafetyCheck/ResponseListPage'

export type User = {
  id: number
  name: string
  email: string
  roles: string[]
}

type AppRoutesProps = {
  user: User | null
  setUser: Dispatch<SetStateAction<User | null>>
  error: string
  handleLogout: () => Promise<void>
}

const AppRoutes = ({
  user,
  setUser,
  error,
  handleLogout,
}: AppRoutesProps) => {
  const navigate = useNavigate()
  const location = useLocation()

  const handleLoginSuccess = (newUser: User) => {
    setUser(newUser)

    const state = location.state as { from?: string } | null
    navigate(state?.from ?? '/', { replace: true })
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
        path="/forgot-password"
        element={
          user ? (
            <Navigate to="/" replace />
          ) : (
            <ForgotPasswordPage
              onBackToLogin={() => navigate('/')}
            />
          )
        }
      />

      <Route
        path="/reset-password"
        element={
          user ? (
            <Navigate to="/" replace />
          ) : (
            <ResetPasswordPage />
          )
        }
      />

      <Route
        path="/"
        element={
          !user ? (
            <LoginPage
              onLoginSuccess={handleLoginSuccess}
              onShowRegister={() => navigate('/register')}
              onShowForgotPassword={() => navigate('/forgot-password')}
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
        path="/admin/safety-checks/:id/responses"
        element={
          user?.roles.includes('admin') ? (
            <AuthenticatedLayout
              user={user}
              error={error}
              onLogout={handleLogout}
            >
              <ResponseListPage />
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
        element={<SafetyCheckEditPage />}
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

      <Route
        path="/safety-checks/:id/respond"
        element={
          !user ? (
            <Navigate
              to="/"
              replace
              state={{
                from: `${location.pathname}${location.search}`,
              }}
            />
          ) : (
            <ResponsePage />
          )
        }
      />

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}

export default AppRoutes