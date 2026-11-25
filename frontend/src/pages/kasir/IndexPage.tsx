import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { listKunjungans } from '../../api/kunjungan'
import { errorMessage } from '../../api/client'
import { buatTagihan, listTagihan, type TagihanRow } from '../../api/tagihan'
import { type ApiResponse } from '../../api/types'
import { tanggalHariIni } from '../../lib/tanggal'
import { useToast } from '../../hooks/useToast'
import { SearchableSelect } from '../../components/SearchableSelect'

function formatRupiah(nilai: number): string {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(nilai)
}

export function IndexPage() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const [tanggal, setTanggal] = useState(tanggalHariIni())
  const [status, setStatus] = useState('')
  const [modalTerbuka, setModalTerbuka] = useState(false)
  const [pilihId, setPilihId] = useState('')

  const kunjungans = useQuery({
    queryKey: ['kunjungan-tagihan', { tanggal }],
    queryFn: () => listKunjungans({ tanggal, poli_id: '', status: 'menunggu_bayar', page: 1 }),
    enabled: modalTerbuka,
  })

  const tagihans = useQuery({
    queryKey: ['tagihans', { tanggal, status }],
    queryFn: () => listTagihan({ tanggal, status }),
  })

  const buat = useMutation({
    mutationFn: (kunjunganId: string) => buatTagihan(kunjunganId),
    onSuccess: (result: ApiResponse<TagihanRow>, _kunjunganId: string) => {
      void queryClient.invalidateQueries({ queryKey: ['tagihans'] })
      toast.success(result.message)
      setModalTerbuka(false)
      navigate(`/kasir/${result.data.id}`)
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const list = tagihans.data?.items ?? []
  const daftarKunjungan = kunjungans.data?.items ?? []

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-xl font-semibold sm:text-2xl">List Tagihan</h1>
        <button
          type="button"
          onClick={() => setModalTerbuka(true)}
          className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700"
        >
          Buat tagihan
        </button>
      </header>

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
              { label: 'Semua status', value: '' },
              { label: 'Belum dibayar', value: 'belum' },
              { label: 'Dibayar sebagian', value: 'sebagian' },
              { label: 'Lunas', value: 'lunas' },
            ]}
            placeholder="Semua status"
            ariaLabel="Filter status"
          />
        </div>

        {tagihans.isError && (
          <div
            role="alert"
            className="mx-4 mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
          >
            {errorMessage(tagihans.error)}{' '}
            <button
              type="button"
              onClick={() => void tagihans.refetch()}
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
              <th className="px-4 py-3 font-medium">No. tagihan</th>
              <th className="px-4 py-3 font-medium">Pasien</th>
              <th className="px-4 py-3 font-medium">Total</th>
              <th className="px-4 py-3 font-medium">Dibayar</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3">
                <span className="sr-only">Aksi</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {tagihans.isPending && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500">
                  Memuat...
                </td>
              </tr>
            )}

            {!tagihans.isPending && list.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500">
                  Belum ada tagihan.
                </td>
              </tr>
            )}

            {list.map((tagihan: TagihanRow) => (
              <tr key={tagihan.id} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3 font-mono">{tagihan.no_tagihan}</td>
                <td className="px-4 py-3">
                  <span className="font-mono text-xs text-slate-500">
                    {tagihan.kunjungan?.pasien?.no_rm ?? '-'}
                  </span>
                  <p>{tagihan.kunjungan?.pasien?.nama ?? '-'}</p>
                </td>
                <td className="px-4 py-3 tabular-nums">{formatRupiah(tagihan.total)}</td>
                <td className="px-4 py-3 tabular-nums">{formatRupiah(tagihan.dibayar)}</td>
                <td className="px-4 py-3">{tagihan.status}</td>
                <td className="px-4 py-3">
                  <Link
                    to={`/kasir/${tagihan.id}`}
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

      {modalTerbuka && (
        <div className="fixed inset-0 z-40 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-slate-950/50" aria-hidden="true" />

          <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="judul-buat-tagihan"
            className="relative w-full max-w-md space-y-4 rounded-lg bg-white p-5 shadow-xl"
          >
            <h2 id="judul-buat-tagihan" className="text-lg font-semibold">
              Buat Tagihan
            </h2>

            <div>
              <label htmlFor="pilih-kunjungan" className="mb-1.5 block text-sm font-medium">
                Kunjungan menunggu pembayaran
              </label>
              <SearchableSelect
                value={pilihId}
                onChange={setPilihId}
                options={[
                  {
                    label:
                      kunjungans.isFetching
                        ? 'Memuat kunjungan...'
                        : 'Pilih kunjungan',
                    value: '',
                  },
                  ...daftarKunjungan.map(
                    (kunjungan: {
                      id: string
                      kode_antrean: string
                      pasien: { nama: string } | null
                    }) => ({
                      label: `${kunjungan.kode_antrean} — ${kunjungan.pasien?.nama}`,
                      value: kunjungan.id,
                    })
                  ),
                ]}
                placeholder="Pilih kunjungan"
                ariaLabel="Pilih kunjungan"
              />
              {kunjungans.isError && (
                <p className="mt-1 text-xs text-red-600">{errorMessage(kunjungans.error)}</p>
              )}
              {kunjungans.isSuccess && daftarKunjungan.length === 0 && (
                <p className="mt-1 text-xs text-slate-500">Tidak ada kunjungan menunggu pembayaran.</p>
              )}
            </div>

            <div className="flex justify-end gap-2 pt-2">
              <button
                type="button"
                onClick={() => buat.mutate(pilihId)}
                disabled={pilihId === '' || buat.isPending}
                className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
              >
                {buat.isPending ? 'Membuat...' : 'Buat'}
              </button>
              <button
                type="button"
                onClick={() => setModalTerbuka(false)}
                className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
