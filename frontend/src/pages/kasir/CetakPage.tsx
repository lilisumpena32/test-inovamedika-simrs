import { useQuery } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'
import { getTagihan } from '../../api/tagihan'
import { errorMessage } from '../../api/client'

function formatRupiah(nilai: number): string {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(nilai)
}

export function CetakPage() {
  const { id = '' } = useParams()

  const tagihan = useQuery({
    queryKey: ['tagihan', id],
    queryFn: () => getTagihan(id),
    enabled: id !== '',
    retry: false,
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

  return (
    <div className="space-y-6 print:space-y-4">
      <div className="flex gap-2 print:hidden">
        <button
          type="button"
          onClick={() => window.print()}
          className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700"
        >
          Cetak
        </button>
        <Link
          to="/kasir"
          className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Kembali
        </Link>
      </div>

      <section className="card mx-auto max-w-2xl p-6">
        <header className="border-b border-slate-200 pb-4">
          <h1 className="text-lg font-semibold">SIMRS Inova Medika</h1>
          <p className="text-sm text-slate-500">Invoice & bukti pembayaran rawat jalan</p>
        </header>

        <dl className="mt-4 grid grid-cols-2 gap-2 text-sm">
          <dt className="text-slate-500">No. tagihan</dt>
          <dd className="text-right font-mono">{data.no_tagihan}</dd>
          <dt className="text-slate-500">Pasien</dt>
          <dd className="text-right">
            {kunjungan?.pasien?.no_rm} — {kunjungan?.pasien?.nama}
          </dd>
          <dt className="text-slate-500">Poli / dokter</dt>
          <dd className="text-right">
            {kunjungan?.poli?.name} / {kunjungan?.dokter?.name}
          </dd>
          <dt className="text-slate-500">Tanggal kunjungan</dt>
          <dd className="text-right">{kunjungan?.tgl_kunjungan}</dd>
          <dt className="text-slate-500">Status</dt>
          <dd className="text-right">{data.status}</dd>
        </dl>

        <table className="mt-4 w-full text-sm">
          <thead>
            <tr className="border-b border-slate-300 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="py-2 font-medium">Uraian</th>
              <th className="py-2 font-medium">Jumlah</th>
              <th className="py-2 font-medium">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            {data.items.map((item) => (
              <tr key={item.id} className="border-b border-slate-100">
                <td className="py-2">{item.uraian}</td>
                <td className="py-2 tabular-nums">{item.jumlah}</td>
                <td className="py-2 tabular-nums">{formatRupiah(item.harga * item.jumlah)}</td>
              </tr>
            ))}
            <tr className="font-semibold">
              <td className="py-2" colSpan={2}>
                Total
              </td>
              <td className="py-2 tabular-nums">{formatRupiah(data.total)}</td>
            </tr>
          </tbody>
        </table>

        <h2 className="mt-4 text-sm font-semibold">Riwayat pembayaran</h2>
        <table className="w-full text-sm">
          <tbody>
            {data.pembayaran.length === 0 && (
              <tr>
                <td className="py-2 text-slate-500">Belum ada pembayaran.</td>
              </tr>
            )}
            {data.pembayaran.map((p) => (
              <tr key={p.id} className="border-b border-slate-100">
                <td className="py-2">{p.metode}</td>
                <td className="py-2 text-xs text-slate-500">{p.waktu}</td>
                <td className="py-2 text-right tabular-nums">{formatRupiah(p.nominal)}</td>
              </tr>
            ))}
          </tbody>
        </table>

        <p className="mt-4 text-sm">
          Dibayar {formatRupiah(data.dibayar)} — sisa {formatRupiah(data.sisa)}
        </p>
      </section>
    </div>
  )
}
