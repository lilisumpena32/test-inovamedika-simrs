import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { listKunjungans, STATUS_LABEL } from '../../api/kunjungan'
import { errorMessage } from '../../api/client'
import { listPolis } from '../../api/poli'
import { SearchableSelect } from '../../components/SearchableSelect'
import { Can } from '../../auth/MenuGuard'
import { tanggalHariIni } from '../../lib/tanggal'

export function IndexPage() {
  const navigate = useNavigate()

  const [tanggal, setTanggal] = useState(tanggalHariIni())
  const [poliId, setPoliId] = useState('')

  const polis = useQuery({
    queryKey: ['polis'],
    queryFn: listPolis,
  })

  const kunjungans = useQuery({
    queryKey: ['kunjungan-pemeriksaan', { tanggal, poliId }],
    queryFn: () => listKunjungans({ tanggal, poli_id: poliId, status: '', page: 1 }),
  })

  const list = (kunjungans.data?.items ?? []).filter((k) =>
    ['menunggu', 'diperiksa', 'menunggu_obat'].includes(k.status),
  )

  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold sm:text-2xl">Pasien Menunggu Pemeriksaan</h1>

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

        {kunjungans.isError && (
          <div
            role="alert"
            className="mx-4 mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
          >
            {errorMessage(kunjungans.error)}{' '}
            <button
              type="button"
              onClick={() => void kunjungans.refetch()}
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
              <th className="px-4 py-3 font-medium">Poli</th>
              <th className="px-4 py-3 font-medium">Dokter</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3">
                <span className="sr-only">Aksi</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {kunjungans.isPending && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500">
                  Memuat...
                </td>
              </tr>
            )}

            {!kunjungans.isPending && list.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500">
                  Tidak ada pasien menunggu.
                </td>
              </tr>
            )}

            {list.map((kunjungan) => (
              <tr key={kunjungan.id} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3 font-mono">{kunjungan.kode_antrean}</td>
                <td className="px-4 py-3">
                  <span className="font-mono text-xs text-slate-500">
                    {kunjungan.pasien?.no_rm ?? '-'}
                  </span>
                  <p>{kunjungan.pasien?.nama ?? '-'}</p>
                </td>
                <td className="px-4 py-3">{kunjungan.poli?.name ?? '-'}</td>
                <td className="px-4 py-3">{kunjungan.dokter?.name ?? '-'}</td>
                <td className="px-4 py-3">{STATUS_LABEL[kunjungan.status] ?? kunjungan.status}</td>
                <td className="px-4 py-3">
                  <Can kode="pemeriksaan.daftar" action="can_view">
                    <button
                      type="button"
                      onClick={() => navigate(`/pemeriksaan/${kunjungan.id}`)}
                      className="rounded-md border border-teal-300 px-2.5 py-1.5 text-xs font-medium text-teal-700 hover:bg-teal-50"
                    >
                      Periksa
                    </button>
                  </Can>
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
