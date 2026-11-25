import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { errorMessage } from '../../api/client'
import { listTindakanMaster, setTindakanStatus } from '../../api/tindakan'
import { AksiMenu } from '../../components/AksiMenu'
import { SearchableSelect } from '../../components/SearchableSelect'
import { Can } from '../../auth/MenuGuard'
import { useSession } from '../../auth/session'
import { useDebounced } from '../../hooks/useDebounced'
import { useToast } from '../../hooks/useToast'

function formatRupiah(nilai: number): string {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(nilai)
}

export function IndexPage() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const sesi = useSession()
  const toast = useToast()

  const [q, setQ] = useState('')
  const [status, setStatus] = useState('')
  const qDebounced = useDebounced(q)

  const tindakans = useQuery({
    queryKey: ['tindakans', { q: qDebounced, status }],
    queryFn: () => listTindakanMaster({ q: qDebounced, is_active: status }),
  })

  const statusMutation = useMutation({
    mutationFn: ({ id, aktif }: { id: string; aktif: boolean }) => setTindakanStatus(id, aktif),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['tindakans'] })
      toast.success(result.message)
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const list = tindakans.data

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-xl font-semibold sm:text-2xl">List Tindakan</h1>
        <Can kode="master.tindakan" action="can_create">
          <button
            type="button"
            onClick={() => navigate('/master/tindakan/create')}
            className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700"
          >
            Add
          </button>
        </Can>
      </header>

      <section className="card">
        <div className="flex flex-wrap items-center gap-2 border-b border-slate-200 px-4 py-3 [&>*]:w-full sm:[&>*]:w-56">
          <input
            type="search"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Cari nama atau kode tindakan"
            className="input max-w-xs"
            aria-label="Cari tindakan"
          />
          <SearchableSelect
            value={status}
            onChange={setStatus}
            options={[
              { label: 'Semua status', value: '' },
              { label: 'Aktif', value: 'true' },
              { label: 'Nonaktif', value: 'false' },
            ]}
            placeholder="Semua status"
            ariaLabel="Filter status"
          />
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Kode</th>
              <th className="px-4 py-3 font-medium">Nama</th>
              <th className="px-4 py-3 font-medium">Tarif</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3">
                <span className="sr-only">Aksi</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {tindakans.isPending && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-slate-500">
                  Memuat...
                </td>
              </tr>
            )}

            {!tindakans.isPending && (list?.items.length ?? 0) === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-slate-500">
                  Belum ada tindakan.
                </td>
              </tr>
            )}

            {list?.items.map((tindakan) => (
              <tr key={tindakan.id} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3 font-mono">{tindakan.kode_tindakan}</td>
                <td className="px-4 py-3">{tindakan.nama}</td>
                <td className="px-4 py-3 tabular-nums">{formatRupiah(tindakan.tarif)}</td>
                <td className="px-4 py-3">
                  <span
                    className={[
                      'rounded px-1.5 py-0.5 text-[11px] font-medium',
                      tindakan.is_active
                        ? 'bg-emerald-50 text-emerald-700'
                        : 'bg-slate-100 text-slate-600',
                    ].join(' ')}
                  >
                    {tindakan.is_active ? 'Aktif' : 'Nonaktif'}
                  </span>
                </td>
                <td className="px-4 py-3">
                  {sesi.can('master.tindakan', 'can_update') && (
                    <AksiMenu
                      items={[
                        {
                          label: 'Edit',
                          onClick: () => navigate(`/master/tindakan/update/${tindakan.id}`),
                        },
                        {
                          label: tindakan.is_active ? 'Deactivated' : 'Activated',
                          onClick: () =>
                            statusMutation.mutate({ id: tindakan.id, aktif: !tindakan.is_active }),
                        },
                      ]}
                    />
                  )}
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
