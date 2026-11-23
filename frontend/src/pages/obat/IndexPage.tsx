import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { errorMessage } from '../../api/client'
import { listObats, setObatStatus } from '../../api/obat'
import { AksiMenu } from '../../components/AksiMenu'
import { Can } from '../../auth/MenuGuard'
import { useSession } from '../../auth/session'
import { useDebounced } from '../../hooks/useDebounced'
import { useToast } from '../../hooks/useToast'

export function IndexPage() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const sesi = useSession()
  const toast = useToast()

  const [q, setQ] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const qDebounced = useDebounced(q)

  const obats = useQuery({
    queryKey: ['obats', { q: qDebounced, status, page }],
    queryFn: () => listObats({ q: qDebounced, is_active: status, page }),
  })

  const statusMutation = useMutation({
    mutationFn: ({ id, aktif }: { id: string; aktif: boolean }) => setObatStatus(id, aktif),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['obats'] })
      toast.success(result.message)
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const list = obats.data
  const totalHalaman = list ? Math.max(1, Math.ceil(list.total / list.per)) : 1

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-semibold">List Obat</h1>
        <Can kode="farmasi.obat" action="can_create">
          <button
            type="button"
            onClick={() => navigate('/farmasi/obat/create')}
            className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700"
          >
            Add
          </button>
        </Can>
      </header>

      <section className="card">
        <div className="flex flex-wrap items-center gap-2 border-b border-slate-200 px-4 py-3">
          <input
            type="search"
            value={q}
            onChange={(e) => {
              setQ(e.target.value)
              setPage(1)
            }}
            placeholder="Cari nama atau kode obat"
            className="input max-w-xs"
            aria-label="Cari obat"
          />
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
            <option value="true">Aktif</option>
            <option value="false">Nonaktif</option>
          </select>
        </div>

        {obats.isError && (
          <div
            role="alert"
            className="mx-4 mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
          >
            {errorMessage(obats.error)}{' '}
            <button
              type="button"
              onClick={() => void obats.refetch()}
              className="font-semibold underline"
            >
              Coba lagi
            </button>
          </div>
        )}

        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Kode</th>
              <th className="px-4 py-3 font-medium">Nama</th>
              <th className="px-4 py-3 font-medium">Satuan</th>
              <th className="px-4 py-3 font-medium">Harga jual</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3">
                <span className="sr-only">Aksi</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {obats.isPending && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500">
                  Memuat...
                </td>
              </tr>
            )}

            {!obats.isPending && (list?.items.length ?? 0) === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500">
                  Belum ada obat.
                </td>
              </tr>
            )}

            {list?.items.map((obat) => (
              <tr key={obat.id} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3 font-mono">{obat.kode_obat}</td>
                <td className="px-4 py-3">{obat.nama_obat}</td>
                <td className="px-4 py-3">{obat.satuan?.name ?? '-'}</td>
                <td className="px-4 py-3 tabular-nums">
                  {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(
                    obat.harga_jual,
                  )}
                </td>
                <td className="px-4 py-3">
                  <span
                    className={[
                      'rounded px-1.5 py-0.5 text-[11px] font-medium',
                      obat.is_active
                        ? 'bg-emerald-50 text-emerald-700'
                        : 'bg-slate-100 text-slate-600',
                    ].join(' ')}
                  >
                    {obat.is_active ? 'Aktif' : 'Nonaktif'}
                  </span>
                </td>
                <td className="px-4 py-3">
                  {sesi.can('farmasi.obat', 'can_update') && (
                    <AksiMenu
                      items={[
                        {
                          label: 'Edit',
                          onClick: () => navigate(`/farmasi/obat/update/${obat.id}`),
                        },
                        {
                          label: obat.is_active ? 'Deactivated' : 'Activated',
                          onClick: () =>
                            statusMutation.mutate({ id: obat.id, aktif: !obat.is_active }),
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
