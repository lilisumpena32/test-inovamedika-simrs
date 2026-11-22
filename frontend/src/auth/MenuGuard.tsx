import { Link } from 'react-router-dom'
import type { ReactNode } from 'react'
import type { MenuNode } from '../api/types'
import { useSession } from './session'

export function MenuGuard({ kode, children }: { kode: string; children: ReactNode }) {
  const { can } = useSession()

  if (!can(kode, 'can_view')) {
    return (
      <div className="rounded-lg border border-slate-200 bg-white p-8 text-center">
        <h1 className="text-base font-semibold">Akses ditolak</h1>
        <p className="mt-1 text-sm text-slate-500">
          Halaman ini tidak terbuka untuk akun Anda. Hubungi administrator kalau aksesnya
          dibutuhkan.
        </p>
        <Link
          to="/"
          className="mt-5 inline-block rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Kembali ke dashboard
        </Link>
      </div>
    )
  }

  return <>{children}</>
}

export function Can({
  kode,
  action,
  children,
}: {
  kode: string
  action: keyof MenuNode['can']
  children: ReactNode
}) {
  const { can } = useSession()

  return can(kode, action) ? <>{children}</> : null
}
