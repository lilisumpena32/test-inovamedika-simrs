import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { errorMessage } from '../../api/client'
import { listKaryawans, setKaryawanStatus } from '../../api/karyawan'
import { AksiMenu } from '../../components/AksiMenu'
import { Pagination } from '../../components/Pagination'
import { SearchableSelect } from '../../components/SearchableSelect'
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

  const karyawans = useQuery({
    queryKey: ['karyawans', { q: qDebounced, status, page }],
    queryFn: () => listKaryawans({ q: qDebounced, jabatan: '', is_active: status, page }),
  })

  const statusMutation = useMutation({
    mutationFn: ({ id, aktif }: { id: string; aktif: boolean }) => setKaryawanStatus(id, aktif),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['karyawans'] })
      toast.success(result.message)
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const bolehUbah = sesi.can('master.karyawan', 'can_update')

  const menuAksi = (id: string, aktif: boolean) => [
    { label: 'Edit', onClick: () => navigate(`/master/karyawan/update/${id}`) },
    { label: 'Akun login', onClick: () => navigate(`/master/karyawan/akun/${id}`) },
    {
      label: aktif ? 'Deactivated' : 'Activated',
      onClick: () => statusMutation.mutate({ id, aktif: !aktif }),
    },
  ]

  const list = karyawans.data
  const totalHalaman = list ? Math.max(1, Math.ceil(list.total / list.per)) : 1

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-xl font-semibold sm:text-2xl">List Karyawan</h1>
        <Can kode="master.karyawan" action="can_create">
          <button
            type="button"
            onClick={() => navigate('/master/karyawan/create')}
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
            onChange={(e) => {
              setQ(e.target.value)
              setPage(1)
            }}
            placeholder="Cari nama atau kode karyawan"
            className="input max-w-xs"
            aria-label="Cari karyawan"
          />
          <SearchableSelect
            value={status}
            onChange={(val) => {
              setStatus(val)
              setPage(1)
            }}
            options={[
              { label: 'Semua status', value: '' },
              { label: 'Aktif', value: 'true' },
              { label: 'Nonaktif', value: 'false' },
            ]}
            placeholder="Semua status"
            ariaLabel="Filter status"
          />
        </div>

        {karyawans.isError && (
          <div
            role="alert"
            className="mx-4 mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
          >
            {errorMessage(karyawans.error)}{' '}
            <button
              type="button"
              onClick={() => void karyawans.refetch()}
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
              <th className="px-4 py-3 font-medium">Kode</th>
              <th className="px-4 py-3 font-medium">Nama</th>
              <th className="px-4 py-3 font-medium">Jabatan</th>
              <th className="px-4 py-3 font-medium">Spesialis</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3">
                <span className="sr-only">Aksi</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {karyawans.isPending && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500">
                  Memuat...
                </td>
              </tr>
            )}

            {!karyawans.isPending && (list?.items.length ?? 0) === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500">
                  Belum ada karyawan.
                </td>
              </tr>
            )}

            {list?.items.map((karyawan) => (
              <tr key={karyawan.id} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3 font-mono">{karyawan.kode_karyawan}</td>
                <td className="px-4 py-3">{karyawan.nama_karyawan}</td>
                <td className="px-4 py-3">{karyawan.jabatan?.name ?? '-'}</td>
                <td className="px-4 py-3">{karyawan.spesialis?.name ?? '-'}</td>
                <td className="px-4 py-3">
                  <span
                    className={[
                      'rounded px-1.5 py-0.5 text-[11px] font-medium',
                      karyawan.is_active
                        ? 'bg-emerald-50 text-emerald-700'
                        : 'bg-slate-100 text-slate-600',
                    ].join(' ')}
                  >
                    {karyawan.is_active ? 'Aktif' : 'Nonaktif'}
                  </span>
                </td>
                <td className="px-4 py-3">
                  {bolehUbah && <AksiMenu items={menuAksi(karyawan.id, karyawan.is_active)} />}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        </div>

        <Pagination
          halaman={page}
          totalHalaman={totalHalaman}
          onSebelumnya={() => setPage((h) => h - 1)}
          onBerikutnya={() => setPage((h) => h + 1)}
        />
      </section>
    </div>
  )
}
