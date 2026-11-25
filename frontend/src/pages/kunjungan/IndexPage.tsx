import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { errorMessage } from '../../api/client'
import { listPolis } from '../../api/poli'
import type { PoliRow } from '../../api/poli'
import { DaftarModal } from './DaftarModal'

export function IndexPage() {
  const [poliDipilih, setPoliDipilih] = useState<PoliRow | null>(null)

  const polis = useQuery({
    queryKey: ['polis'],
    queryFn: listPolis,
  })

  const daftarPoli = (polis.data ?? []).filter((poli) => poli.is_active)

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-xl font-semibold sm:text-2xl">Kunjungan</h1>
        <Link
          to="/pendaftaran/list"
          className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Daftar kunjungan
        </Link>
      </header>

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {polis.isPending && (
          <p className="text-sm text-slate-500">Memuat poli...</p>
        )}

        {polis.isError && (
          <p role="alert" className="text-sm text-red-600">
            {errorMessage(polis.error)}
          </p>
        )}

        {polis.isSuccess && daftarPoli.length === 0 && (
          <p className="text-sm text-slate-500">Belum ada poli aktif.</p>
        )}

        {daftarPoli.map((poli) => (
          <button
            key={poli.id}
            type="button"
            onClick={() => setPoliDipilih(poli)}
            className="card px-4 py-4 text-left text-sm font-medium hover:border-teal-500 hover:text-teal-700"
          >
            {poli.nama}
          </button>
        ))}
      </div>

      {poliDipilih && (
        <DaftarModal poli={poliDipilih} onTutup={() => setPoliDipilih(null)} />
      )}
    </div>
  )
}
