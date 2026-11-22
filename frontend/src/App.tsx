import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './auth/AuthProvider'
import { MenuGuard } from './auth/MenuGuard'
import { ProtectedRoute } from './auth/ProtectedRoute'
import { AppLayout } from './components/AppLayout'
import { IndexPage as DashboardPage } from './pages/dashboard/IndexPage'
import { LoginPage } from './pages/login/LoginPage'
import { IndexPage as RoleIndexPage } from './pages/role/IndexPage'
import { NotFound } from './pages/NotFound'
import { IndexPage as UserIndexPage } from './pages/user/IndexPage'

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
                  path="setting/role"
                  element={
                    <MenuGuard kode="pengaturan.role">
                      <RoleIndexPage />
                    </MenuGuard>
                  }
                />
              </Route>
            </Route>

            <Route path="*" element={<NotFound />} />
          </Routes>
        </AuthProvider>
      </BrowserRouter>
    </QueryClientProvider>
  )
}
