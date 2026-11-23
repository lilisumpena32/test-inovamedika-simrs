import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './auth/AuthProvider'
import { MenuGuard } from './auth/MenuGuard'
import { ProtectedRoute } from './auth/ProtectedRoute'
import { AppLayout } from './components/AppLayout'
import { ToastProvider } from './components/Toast'
import { IndexPage as DashboardPage } from './pages/dashboard/IndexPage'
import { LoginPage } from './pages/login/LoginPage'
import { AccessPage as RoleAccessPage } from './pages/role/AccessPage'
import { CreatePage as RoleCreatePage } from './pages/role/CreatePage'
import { EditPage as RoleEditPage } from './pages/role/EditPage'
import { IndexPage as RoleIndexPage } from './pages/role/IndexPage'
import { NotFound } from './pages/NotFound'
import { CreatePage as UserCreatePage } from './pages/user/CreatePage'
import { IndexPage as UserIndexPage } from './pages/user/IndexPage'
import { UpdatePage as UserUpdatePage } from './pages/user/UpdatePage'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
    },
  },
})

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <ToastProvider>
        <BrowserRouter>
        <AuthProvider>
          <Routes>
            <Route path="/login" element={<LoginPage />} />

            <Route element={<ProtectedRoute />}>
              <Route element={<AppLayout />}>
                <Route
                  index
                  element={
                    <MenuGuard kode="dashboard">
                      <DashboardPage />
                    </MenuGuard>
                  }
                />
                <Route
                  path="setting/user"
                  element={
                    <MenuGuard kode="pengaturan.user">
                      <UserIndexPage />
                    </MenuGuard>
                  }
                />
                <Route
                  path="setting/user/create"
                  element={
                    <MenuGuard kode="pengaturan.user">
                      <UserCreatePage />
                    </MenuGuard>
                  }
                />
                <Route
                  path="setting/user/update/:id"
                  element={
                    <MenuGuard kode="pengaturan.user">
                      <UserUpdatePage />
                    </MenuGuard>
                  }
                />
                <Route
                  path="setting/role"
                  element={
                    <MenuGuard kode="pengaturan.role">
                      <RoleIndexPage />
                    </MenuGuard>
                  }
                />
                <Route
                  path="setting/role/create"
                  element={
                    <MenuGuard kode="pengaturan.role">
                      <RoleCreatePage />
                    </MenuGuard>
                  }
                />
                <Route
                  path="setting/role/update/:id"
                  element={
                    <MenuGuard kode="pengaturan.role">
                      <RoleEditPage />
                    </MenuGuard>
                  }
                />
                <Route
                  path="setting/role/access/:id"
                  element={
                    <MenuGuard kode="pengaturan.role">
                      <RoleAccessPage />
                    </MenuGuard>
                  }
                />
              </Route>
            </Route>

            <Route path="*" element={<NotFound />} />
          </Routes>
        </AuthProvider>
      </BrowserRouter>
      </ToastProvider>
    </QueryClientProvider>
  )
}
