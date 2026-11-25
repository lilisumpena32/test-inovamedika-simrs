import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { listAntreans } from '../../api/antrean'
import { errorMessage } from '../../api/client'
import { STATUS_LABEL } from '../../api/kunjungan'
import { listPolis } from '../../api/poli'
import { SearchableSelect } from '../../components/SearchableSelect'
import { tanggalHariIni } from '../../lib/tanggal'

export function IndexPage() {
  const [tanggal, setTanggal] = useState(tanggalHariIni())
  const [poliId, setPoliId] = useState('')

  const polis = useQuery({
    queryKey: ['polis'],
    queryFn: listPolis,
  })

  const antreans = useQuery({
    queryKey: ['antreans', { tanggal, poliId }],
    queryFn: () => listAntreans(tanggal, poliId),
  })

  const list = antreans.data?.items ?? []

  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold sm:text-2xl">List Antrean</h1>

      <section className="card">
        <div className="flex flex-wrap items-center gap-2 border-b border-slate-200 px-4 py-3 [&>*]:w-full sm:[&>*]:w-56">
          <input
            type="date"
            value={tanggal}
            onChange={(e) => setTanggal(e.target.value)}
            className="input max-w-[10rem]"
            aria-label="Tanggal antrean"
          />
          <SearchableSelect
            value={poliId}
            onChange={setPoliId}
            options={[
              { label: 'Semua poli', value: '' },
              ...(polis.data ?? []).map((poli) => ({
                label: poli.nama,
                value: poli.id,
              })),
            ]}
            placeholder="Semua poli"
            ariaLabel="Filter poli"
          />
        </div>

        {antreans.isError && (
          <div
            role="alert"
            className="mx-4 mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
          >
            {errorMessage(antreans.error)}{' '}
            <button
              type="button"
              onClick={() => void antreans.refetch()}
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
              <th className="px-4 py-3 font-medium">No. Kunjungan</th>
              <th className="px-4 py-3 font-medium">Pasien</th>
              <th className="px-4 py-3 font-medium">Dokter</th>
              <th className="px-4 py-3 font-medium">Status</th>
            </tr>
          </thead>
          <tbody>
            {antreans.isPending && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-slate-500">
                  Memuat...
                </td>
              </tr>
            )}

            {!antreans.isPending && list.length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-slate-500">
                  Belum ada antrean.
                </td>
              </tr>
            )}

            {list.map((antrean) => (
              <tr key={antrean.id} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3 text-lg font-semibold tabular-nums">
                  {antrean.kode_antrean}
                </td>
                <td className="px-4 py-3 font-mono">{antrean.no_kunjungan}</td>
                <td className="px-4 py-3">
                  <span className="font-mono text-xs text-slate-500">
                    {antrean.pasien?.no_rm ?? '-'}
                  </span>
                  <p>{antrean.pasien?.nama ?? '-'}</p>
                </td>
                <td className="px-4 py-3">{antrean.dokter ?? '-'}</td>
                <td className="px-4 py-3">{STATUS_LABEL[antrean.status] ?? antrean.status}</td>
              </tr>
            ))}
          </tbody>
        </table>
        </div>
      </section>
    </div>
  )
}
