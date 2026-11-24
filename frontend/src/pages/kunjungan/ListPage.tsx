import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { errorMessage } from '../../api/client'
import { listKunjungans, STATUS_LABEL } from '../../api/kunjungan'
import { listPolis } from '../../api/poli'
import { AksiMenu } from '../../components/AksiMenu'
import { useSession } from '../../auth/session'
import { tanggalHariIni } from '../../lib/tanggal'

const WARNA_STATUS: Record<string, string> = {
  menunggu: 'bg-amber-50 text-amber-700',
  diperiksa: 'bg-sky-50 text-sky-700',
  menunggu_obat: 'bg-indigo-50 text-indigo-700',
  menunggu_bayar: 'bg-orange-50 text-orange-700',
  selesai: 'bg-emerald-50 text-emerald-700',
  batal: 'bg-slate-100 text-slate-600',
}

function formatRupiah(nilai: number): string {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(nilai)
}

export function ListPage() {
  const navigate = useNavigate()
  const sesi = useSession()

  const [tanggal, setTanggal] = useState(tanggalHariIni())
  const [poliId, setPoliId] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)

  const polis = useQuery({
    queryKey: ['polis'],
    queryFn: listPolis,
  })

  const kunjungans = useQuery({
    queryKey: ['kunjungans', { tanggal, poliId, status, page }],
    queryFn: () => listKunjungans({ tanggal, poli_id: poliId, status, page }),
  })

  const list = kunjungans.data
  const totalHalaman = list ? Math.max(1, Math.ceil(list.total / list.per)) : 1

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-semibold">List Kunjungan</h1>
        <Link
          to="/pendaftaran"
          className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Kembali
        </Link>
      </header>

      <section className="card">
        <div className="flex flex-wrap items-center gap-2 border-b border-slate-200 px-4 py-3">
          <input
            type="date"
            value={tanggal}
            onChange={(e) => {
              setTanggal(e.target.value)
              setPage(1)
            }}
            className="input max-w-[10rem]"
            aria-label="Tanggal kunjungan"
          />
          <select
            value={poliId}
            onChange={(e) => {
              setPoliId(e.target.value)
              setPage(1)
            }}
            className="select"
            aria-label="Filter poli"
          >
            <option value="">Semua poli</option>
            {(polis.data ?? []).map((poli) => (
              <option key={poli.id} value={poli.id}>
                {poli.nama}
              </option>
            ))}
          </select>
          <select
            value={status}
            onChange={(e) => {
              setStatus(e.target.value)
              setPage(1)
            }}
            className="select"
            aria-label="Filter status"
          >
            <option value="">Semua status</option>
            {Object.entries(STATUS_LABEL).map(([nilai, label]) => (
              <option key={nilai} value={nilai}>
                {label}
              </option>
            ))}
          </select>
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

        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">No. Kunjungan</th>
              <th className="px-4 py-3 font-medium">Tanggal</th>
              <th className="px-4 py-3 font-medium">Antrean</th>
              <th className="px-4 py-3 font-medium">Pasien</th>
              <th className="px-4 py-3 font-medium">Poli</th>
              <th className="px-4 py-3 font-medium">Dokter</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3 font-medium">Biaya daftar</th>
              <th className="px-4 py-3">
                <span className="sr-only">Aksi</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {kunjungans.isPending && (
              <tr>
                <td colSpan={9} className="px-4 py-6 text-center text-slate-500">
                  Memuat...
                </td>
              </tr>
            )}

            {!kunjungans.isPending && (list?.items.length ?? 0) === 0 && (
              <tr>
                <td colSpan={9} className="px-4 py-6 text-center text-slate-500">
                  Belum ada kunjungan.
                </td>
              </tr>
            )}

            {list?.items.map((kunjungan) => (
              <tr key={kunjungan.id} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3 font-mono">{kunjungan.no_kunjungan}</td>
                <td className="px-4 py-3 tabular-nums">{kunjungan.tgl_kunjungan}</td>
                <td className="px-4 py-3 font-mono">{kunjungan.kode_antrean}</td>
                <td className="px-4 py-3">
                  <span className="font-mono text-xs text-slate-500">
                    {kunjungan.pasien?.no_rm ?? '-'}
                  </span>
                  <p>{kunjungan.pasien?.nama ?? '-'}</p>
                </td>
                <td className="px-4 py-3">{kunjungan.poli?.name ?? '-'}</td>
                <td className="px-4 py-3">{kunjungan.dokter?.name ?? '-'}</td>
                <td className="px-4 py-3">
                  <span
                    className={[
                      'rounded px-1.5 py-0.5 text-[11px] font-medium',
                      WARNA_STATUS[kunjungan.status] ?? 'bg-slate-100 text-slate-600',
                    ].join(' ')}
                  >
                    {STATUS_LABEL[kunjungan.status] ?? kunjungan.status}
                  </span>
                </td>
                <td className="px-4 py-3 tabular-nums">{formatRupiah(kunjungan.biaya_daftar)}</td>
                <td className="px-4 py-3">
                  {sesi.can('pendaftaran.kunjungan', 'can_update') && (
                    <AksiMenu
                      items={[
                        {
                          label: 'Edit',
                          onClick: () => navigate(`/pendaftaran/update/${kunjungan.id}`),
                        },
                      ]}
                    />
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        <div className="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-sm text-slate-500">
          <p>{list ? `Halaman ${list.page} dari ${totalHalaman}` : ''}</p>
          <div className="flex gap-2">
            <button
              type="button"
              disabled={page <= 1}
              onClick={() => setPage((h) => h - 1)}
              className="rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40"
            >
              Sebelumnya
            </button>
            <button
              type="button"
              disabled={list ? page >= totalHalaman : true}
              onClick={() => setPage((h) => h + 1)}
              className="rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40"
            >
              Berikutnya
            </button>
          </div>
        </div>
      </section>
    </div>
  )
}
