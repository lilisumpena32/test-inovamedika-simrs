import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useSession } from './session'

export function ProtectedRoute() {
  const { token, user, ready, failed, refresh, signOut } = useSession()
  const location = useLocation()

  if (token === null) {
    return <Navigate to="/login" replace state={{ from: location.pathname + location.search }} />
  }

  if (!ready) {
    return (
      <div className="grid min-h-screen place-items-center bg-slate-100">
        <div className="text-center">
          <div className="mx-auto size-8 animate-spin rounded-full border-2 border-slate-300 border-t-teal-600" />
          <p className="mt-3 text-sm text-slate-500">Memuat...</p>
        </div>
      </div>
    )
  }

  if (failed) {
    return (
      <div className="grid min-h-screen place-items-center bg-slate-100 px-4">
        <div className="w-full max-w-sm rounded-lg border border-slate-200 bg-white p-6 text-center">
          <h1 className="text-base font-semibold">Data akun gagal dimuat</h1>
          <p className="mt-1 text-sm text-slate-500">
            Server tidak merespons. Periksa koneksi, lalu coba lagi.
          </p>
          <div className="mt-5 flex justify-center gap-2">
            <button
              type="button"
              onClick={refresh}
              className="rounded-md bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700"
            >
              Coba lagi
            </button>
            <button
              type="button"
              onClick={signOut}
              className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
              Keluar
            </button>
          </div>
        </div>
      </div>
    )
  }

  if (user === null) {
    return <Navigate to="/login" replace />
  }

  return <Outlet />
}
