import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './auth/AuthProvider'
import { MenuGuard } from './auth/MenuGuard'
import { ProtectedRoute } from './auth/ProtectedRoute'
import { AppLayout } from './components/AppLayout'
import { ToastProvider } from './components/Toast'
import { IndexPage as DashboardPage } from './pages/dashboard/IndexPage'
import { IndexPage as JadwalIndexPage } from './pages/jadwal/IndexPage'
import { CreatePage as JadwalCreatePage } from './pages/jadwal/CreatePage'
import { EditPage as JadwalEditPage } from './pages/jadwal/EditPage'
import { IndexPage as KunjunganIndexPage } from './pages/kunjungan/IndexPage'
import { ListPage as KunjunganListPage } from './pages/kunjungan/ListPage'
import { EditPage as KunjunganEditPage } from './pages/kunjungan/EditPage'
import { IndexPage as AntreanIndexPage } from './pages/antrean/IndexPage'
import { LoginPage } from './pages/login/LoginPage'
import { IndexPage as KaryawanIndexPage } from './pages/karyawan/IndexPage'
import { CreatePage as KaryawanCreatePage } from './pages/karyawan/CreatePage'
import { EditPage as KaryawanEditPage } from './pages/karyawan/EditPage'
import { IndexPage as ObatIndexPage } from './pages/obat/IndexPage'
import { CreatePage as ObatCreatePage } from './pages/obat/CreatePage'
import { EditPage as ObatEditPage } from './pages/obat/EditPage'
import { IndexPage as PasienIndexPage } from './pages/pasien/IndexPage'
import { CreatePage as PasienCreatePage } from './pages/pasien/CreatePage'
import { EditPage as PasienEditPage } from './pages/pasien/EditPage'
import { NotFound } from './pages/NotFound'
import { IndexPage as PoliIndexPage } from './pages/poli/IndexPage'
import { CreatePage as PoliCreatePage } from './pages/poli/CreatePage'
import { EditPage as PoliEditPage } from './pages/poli/EditPage'
import { AccessPage as RoleAccessPage } from './pages/role/AccessPage'
import { CreatePage as RoleCreatePage } from './pages/role/CreatePage'
import { EditPage as RoleEditPage } from './pages/role/EditPage'
import { IndexPage as RoleIndexPage } from './pages/role/IndexPage'
import { AkunPage as KaryawanAkunPage } from './pages/karyawan/AkunPage'
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
                    path="master/pasien"
                    element={
                      <MenuGuard kode="master.pasien">
                        <PasienIndexPage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="master/pasien/create"
                    element={
                      <MenuGuard kode="master.pasien">
                        <PasienCreatePage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="master/pasien/update/:id"
                    element={
                      <MenuGuard kode="master.pasien">
                        <PasienEditPage />
                      </MenuGuard>
                    }
                  />

                  <Route
                    path="master/poli"
                    element={
                      <MenuGuard kode="master.poli">
                        <PoliIndexPage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="master/poli/create"
                    element={
                      <MenuGuard kode="master.poli">
                        <PoliCreatePage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="master/poli/update/:id"
                    element={
                      <MenuGuard kode="master.poli">
                        <PoliEditPage />
                      </MenuGuard>
                    }
                  />

                  <Route
                    path="master/karyawan"
                    element={
                      <MenuGuard kode="master.karyawan">
                        <KaryawanIndexPage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="master/karyawan/create"
                    element={
                      <MenuGuard kode="master.karyawan">
                        <KaryawanCreatePage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="master/karyawan/update/:id"
                    element={
                      <MenuGuard kode="master.karyawan">
                        <KaryawanEditPage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="master/karyawan/akun/:id"
                    element={
                      <MenuGuard kode="master.karyawan">
                        <KaryawanAkunPage />
                      </MenuGuard>
                    }
                  />

                  <Route
                    path="master/jadwal"
                    element={
                      <MenuGuard kode="master.jadwal">
                        <JadwalIndexPage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="master/jadwal/create"
                    element={
                      <MenuGuard kode="master.jadwal">
                        <JadwalCreatePage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="master/jadwal/update/:id"
                    element={
                      <MenuGuard kode="master.jadwal">
                        <JadwalEditPage />
                      </MenuGuard>
                    }
                  />

                  <Route
                    path="pendaftaran"
                    element={
                      <MenuGuard kode="pendaftaran.kunjungan">
                        <KunjunganIndexPage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="pendaftaran/list"
                    element={
                      <MenuGuard kode="pendaftaran.kunjungan">
                        <KunjunganListPage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="pendaftaran/update/:id"
                    element={
                      <MenuGuard kode="pendaftaran.kunjungan">
                        <KunjunganEditPage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="antrean"
                    element={
                      <MenuGuard kode="pendaftaran.antrean">
                        <AntreanIndexPage />
                      </MenuGuard>
                    }
                  />

                  <Route
                    path="farmasi/obat"
                    element={
                      <MenuGuard kode="farmasi.obat">
                        <ObatIndexPage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="farmasi/obat/create"
                    element={
                      <MenuGuard kode="farmasi.obat">
                        <ObatCreatePage />
                      </MenuGuard>
                    }
                  />
                  <Route
                    path="farmasi/obat/update/:id"
                    element={
                      <MenuGuard kode="farmasi.obat">
                        <ObatEditPage />
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
