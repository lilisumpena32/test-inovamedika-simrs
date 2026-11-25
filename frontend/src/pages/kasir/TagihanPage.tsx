import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { bayarTagihan, getTagihan } from '../../api/tagihan'
import { errorMessage } from '../../api/client'
import { SearchableSelect } from '../../components/SearchableSelect'
import { useToast } from '../../hooks/useToast'

function formatRupiah(nilai: number): string {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(nilai)
}

export function TagihanPage() {
  const { id = '' } = useParams()
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const [metode, setMetode] = useState('tunai')
  const [nominal, setNominal] = useState('')

  const tagihan = useQuery({
    queryKey: ['tagihan', id],
    queryFn: () => getTagihan(id),
    enabled: id !== '',
    retry: false,
  })

  const bayar = useMutation({
    mutationFn: () =>
      bayarTagihan(id, {
        idempotency_key: crypto.randomUUID(),
        metode,
        nominal: Number(nominal),
      }),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['tagihan', id] })
      void queryClient.invalidateQueries({ queryKey: ['tagihans'] })
      toast.success(result.message)
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  if (tagihan.isPending || id === '') {
    return <p className="text-sm text-slate-500">Memuat...</p>
  }

  if (tagihan.isError) {
    return (
      <div className="space-y-4">
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {errorMessage(tagihan.error)}
        </div>
        <Link to="/kasir" className="text-sm font-medium text-teal-700 underline">
          Kembali ke List Tagihan
        </Link>
      </div>
    )
  }

  const data = tagihan.data
  const kunjungan = data.kunjungan
  const lunas = data.status === 'lunas'

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-xl font-semibold sm:text-2xl">Tagihan {data.no_tagihan}</h1>
        <button
          type="button"
          onClick={() => navigate(`/kasir/${id}/cetak`)}
          className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Cetak
        </button>
      </header>

      <section className="card p-5 text-sm">
        <p>
          <span className="font-mono text-xs text-slate-500">{kunjungan?.pasien?.no_rm}</span>{' '}
          <span className="font-medium">{kunjungan?.pasien?.nama}</span>
        </p>
        <p className="text-slate-500">
          {kunjungan?.poli?.name} · {kunjungan?.dokter?.name} · {kunjungan?.tgl_kunjungan}
        </p>
        <p className="mt-1">
          Status tagihan:{' '}
          <span className="font-medium">{data.status}</span>
        </p>
      </section>

      <section className="card">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[32rem] text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Uraian</th>
              <th className="px-4 py-3 font-medium">Jumlah</th>
              <th className="px-4 py-3 font-medium">Harga</th>
              <th className="px-4 py-3 font-medium">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            {data.items.map((item) => (
              <tr key={item.id} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3">{item.uraian}</td>
                <td className="px-4 py-3 tabular-nums">{item.jumlah}</td>
                <td className="px-4 py-3 tabular-nums">{formatRupiah(item.harga)}</td>
                <td className="px-4 py-3 tabular-nums">{formatRupiah(item.harga * item.jumlah)}</td>
              </tr>
            ))}
            <tr className="font-semibold">
              <td className="px-4 py-3" colSpan={3}>
                Total
              </td>
              <td className="px-4 py-3 tabular-nums">{formatRupiah(data.total)}</td>
            </tr>
          </tbody>
        </table>
        </div>
      </section>

      <section className="card max-w-md space-y-3 p-5">
        <h2 className="font-semibold">Pembayaran</h2>
        <p className="text-sm text-slate-600">
          Dibayar {formatRupiah(data.dibayar)} — sisa {formatRupiah(data.sisa)}
        </p>

        {data.pembayaran.length > 0 && (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[24rem] text-sm">
            <tbody>
              {data.pembayaran.map((p) => (
                <tr key={p.id} className="border-b border-slate-100 last:border-0">
                  <td className="py-2">{p.metode}</td>
                  <td className="py-2 tabular-nums">{formatRupiah(p.nominal)}</td>
                  <td className="py-2 text-right text-xs text-slate-500">{p.waktu}</td>
                </tr>
              ))}
            </tbody>
          </table>
          </div>
        )}

        {!lunas && (
          <div className="grid gap-3 sm:grid-cols-2">
            <div>
              <label htmlFor="metode" className="mb-1.5 block text-sm font-medium">
                Metode
              </label>
              <SearchableSelect
                id="metode"
                value={metode}
                onChange={setMetode}
                options={[
                  { label: 'Tunai', value: 'tunai' },
                  { label: 'Non-tunai', value: 'non_tunai' },
                ]}
                placeholder="Pilih metode"
                ariaLabel="Metode pembayaran"
              />
            </div>
            <div>
              <label htmlFor="nominal" className="mb-1.5 block text-sm font-medium">
                Nominal
              </label>
              <input
                id="nominal"
                type="number"
                min={1}
                value={nominal}
                onChange={(e) => setNominal(e.target.value)}
                className="input"
              />
            </div>
          </div>
        )}

        {!lunas && (
          <button
            type="button"
            onClick={() => nominal !== '' && bayar.mutate()}
            disabled={bayar.isPending || nominal === '' || Number(nominal) <= 0}
            className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
          >
            {bayar.isPending ? 'Menyimpan...' : 'Catat pembayaran'}
          </button>
        )}

        {lunas && <p className="text-sm font-medium text-emerald-700">Tagihan lunas.</p>}
      </section>
    </div>
  )
}
