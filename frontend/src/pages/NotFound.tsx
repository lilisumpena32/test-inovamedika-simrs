import { Link } from 'react-router-dom'

export function NotFound() {
  return (
    <div className="grid min-h-screen place-items-center bg-slate-100 px-4">
      <div className="text-center">
        <p className="text-sm font-semibold text-teal-700">404</p>
        <h1 className="mt-1 text-xl font-semibold">Halaman tidak ditemukan</h1>
        <Link
          to="/"
          className="mt-5 inline-block rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Kembali ke dashboard
        </Link>
      </div>
    </div>
  )
}
