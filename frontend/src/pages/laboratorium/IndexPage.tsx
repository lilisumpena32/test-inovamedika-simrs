import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { listOrderLab, simpanHasilLab, type LabDaftarRow } from '../../api/pemeriksaan'
import { errorMessage } from '../../api/client'
import { useToast } from '../../hooks/useToast'
import { tanggalHariIni } from '../../lib/tanggal'

export function IndexPage() {
  const queryClient = useQueryClient()
  const toast = useToast()

  const [tanggal, setTanggal] = useState(tanggalHariIni())
  const [status, setStatus] = useState('diminta')
  const [hasil, setHasil] = useState<Record<string, string>>({})

  const orders = useQuery({
    queryKey: ['orderLab', { tanggal, status }],
    queryFn: () => listOrderLab({ tanggal, status }),
  })

  const simpan = useMutation({
    mutationFn: (id: string) => simpanHasilLab(id, (hasil[id] ?? '').trim()),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['orderLab'] })
      toast.success(result.message)
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const list = orders.data?.items ?? []

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">Order Lab</h1>

      <section className="card">
        <div className="flex flex-wrap items-center gap-2 border-b border-slate-200 px-4 py-3">
          <input
            type="date"
            value={tanggal}
            onChange={(e) => setTanggal(e.target.value)}
            className="input max-w-[10rem]"
            aria-label="Tanggal"
          />
          <select
            value={status}
            onChange={(e) => setStatus(e.target.value)}
            className="select"
            aria-label="Filter status"
          >
            <option value="diminta">Diminta</option>
            <option value="selesai">Selesai</option>
            <option value="">Semua status</option>
          </select>
        </div>

        {orders.isError && (
          <div
            role="alert"
            className="mx-4 mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
          >
            {errorMessage(orders.error)}{' '}
            <button
              type="button"
              onClick={() => void orders.refetch()}
              className="font-semibold underline"
            >
              Coba lagi
            </button>
          </div>
        )}

        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Pasien</th>
              <th className="px-4 py-3 font-medium">Poli</th>
              <th className="px-4 py-3 font-medium">Jenis lab</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3 font-medium">Hasil</th>
            </tr>
          </thead>
          <tbody>
            {orders.isPending && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-slate-500">
                  Memuat...
                </td>
              </tr>
            )}

            {!orders.isPending && list.length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-slate-500">
                  Tidak ada order lab.
                </td>
              </tr>
            )}

            {list.map((order) => (
              <BarisOrder
                key={order.id}
                order={order}
                nilai={hasil[order.id] ?? ''}
                onChange={(nilai) => setHasil((sebelum) => ({ ...sebelum, [order.id]: nilai }))}
                pending={simpan.isPending}
                onSubmit={() => simpan.mutate(order.id)}
              />
            ))}
          </tbody>
        </table>
      </section>
    </div>
  )
}

function BarisOrder({
  order,
  nilai,
  onChange,
  pending,
  onSubmit,
}: {
  order: LabDaftarRow
  nilai: string
  onChange: (nilai: string) => void
  pending: boolean
  onSubmit: () => void
}) {
  return (
    <tr className="border-b border-slate-100 last:border-0 align-top">
      <td className="px-4 py-3">
        <span className="font-mono text-xs text-slate-500">
          {order.kunjungan?.pasien?.no_rm ?? '-'}
        </span>
        <p>{order.kunjungan?.pasien?.nama ?? '-'}</p>
      </td>
      <td className="px-4 py-3">{order.kunjungan?.poli ?? '-'}</td>
      <td className="px-4 py-3">
        {order.jenis_lab?.nama ?? '-'}
        <p className="tabular-nums text-slate-500">
          {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(order.tarif)}
        </p>
      </td>
      <td className="px-4 py-3">{order.status}</td>
      <td className="px-4 py-3">
        {order.status === 'diminta' ? (
          <div className="flex items-start gap-2">
            <textarea
              rows={2}
              value={nilai}
              onChange={(e) => onChange(e.target.value)}
              aria-label={`Hasil ${order.jenis_lab?.nama ?? 'lab'}`}
              className="input min-w-[16rem]"
            />
            <button
              type="button"
              onClick={onSubmit}
              disabled={nilai.trim() === '' || pending}
              className="rounded-md bg-teal-600 px-2.5 py-2 text-xs font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
            >
              Simpan
            </button>
          </div>
        ) : (
          <p className="text-slate-600">{order.hasil ?? '-'}</p>
        )}
      </td>
    </tr>
  )
}
