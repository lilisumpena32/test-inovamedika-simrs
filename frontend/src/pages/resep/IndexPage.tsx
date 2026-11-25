import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { listResep } from '../../api/resep'
import { SearchableSelect } from '../../components/SearchableSelect'
import { errorMessage } from '../../api/client'
import { tanggalHariIni } from '../../lib/tanggal'

export function IndexPage() {
  const [tanggal, setTanggal] = useState(tanggalHariIni())
  const [status, setStatus] = useState('menunggu')

  const reseps = useQuery({
    queryKey: ['reseps', { tanggal, status }],
    queryFn: () => listResep({ tanggal, status }),
  })

  const list = reseps.data?.items ?? []

  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold sm:text-2xl">Resep</h1>

      <section className="card">
        <div className="flex flex-wrap items-center gap-2 border-b border-slate-200 px-4 py-3 [&>*]:w-full sm:[&>*]:w-56">
          <input
            type="date"
            value={tanggal}
            onChange={(e) => setTanggal(e.target.value)}
            className="input max-w-[10rem]"
            aria-label="Tanggal"
          />
          <SearchableSelect
            value={status}
            onChange={setStatus}
            options={[
              { label: 'Menunggu', value: 'menunggu' },
              { label: 'Selesai', value: 'selesai' },
              { label: 'Semua status', value: '' },
            ]}
            placeholder="Semua status"
            ariaLabel="Filter status"
          />
        </div>

        {reseps.isError && (
          <div
            role="alert"
            className="mx-4 mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
          >
            {errorMessage(reseps.error)}{' '}
            <button
              type="button"
              onClick={() => void reseps.refetch()}
              className="font-semibold underline"
            >
              Coba lagi
            </button>
          </div>
        )}

        <div className="overflow-x-auto">
          <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Antrean</th>
              <th className="px-4 py-3 font-medium">Pasien</th>
              <th className="px-4 py-3 font-medium">Dokter</th>
              <th className="px-4 py-3 font-medium">Jumlah item</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3">
                <span className="sr-only">Aksi</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {reseps.isPending && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500">
                  Memuat...
                </td>
              </tr>
            )}

            {!reseps.isPending && list.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500">
                  Tidak ada resep.
                </td>
              </tr>
            )}

            {list.map((resep) => (
              <tr key={resep.id} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3 font-mono">{resep.kunjungan?.kode_antrean ?? '-'}</td>
                <td className="px-4 py-3">
                  <span className="font-mono text-xs text-slate-500">
                    {resep.kunjungan?.pasien?.no_rm ?? '-'}
                  </span>
                  <p>{resep.kunjungan?.pasien?.nama ?? '-'}</p>
                </td>
                <td className="px-4 py-3">{resep.kunjungan?.dokter?.name ?? '-'}</td>
                <td className="px-4 py-3 tabular-nums">{resep.items.length}</td>
                <td className="px-4 py-3">{resep.status}</td>
                <td className="px-4 py-3">
                  <Link
                    to={`/farmasi/resep/${resep.id}`}
                    className="rounded-md border border-teal-300 px-2.5 py-1.5 text-xs font-medium text-teal-700 hover:bg-teal-50"
                  >
                    Buka
                  </Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        </div>
      </section>
    </div>
  )
}
