import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { listRoles } from '../../api/role'
import { listUsers, setUserStatus } from '../../api/user'
import { errorMessage } from '../../api/client'
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
  const [role, setRole] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const qDebounced = useDebounced(q)

  const users = useQuery({
    queryKey: ['users', { q: qDebounced, role, status, page }],
    queryFn: () => listUsers({ q: qDebounced, role, is_active: status, page }),
  })

  const roles = useQuery({
    queryKey: ['roles'],
    queryFn: listRoles,
    select: (data) => data.items,
  })

  const statusMutation = useMutation({
    mutationFn: ({ id, aktif }: { id: string; aktif: boolean }) => setUserStatus(id, aktif),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['users'] })
      toast.success(result.message)
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const list = users.data
  const totalHalaman = list ? Math.max(1, Math.ceil(list.total / list.per)) : 1

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-semibold">List User</h1>
        <Can kode="pengaturan.user" action="can_create">
          <button
            type="button"
            onClick={() => navigate('/setting/user/create')}
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
            placeholder="Cari username atau nama"
            className="input max-w-xs"
            aria-label="Cari user"
          />
          <select
            value={role}
            onChange={(e) => {
              setRole(e.target.value)
              setPage(1)
            }}
            className="select"
            aria-label="Filter role"
          >
            <option value="">Semua role</option>
            {(roles.data ?? []).map((item) => (
              <option key={item.id} value={item.code}>
                {item.name}
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
            <option value="true">Aktif</option>
            <option value="false">Nonaktif</option>
          </select>
        </div>

        {users.isError && (
          <div
            role="alert"
            className="mx-4 mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
          >
            {errorMessage(users.error)}{' '}
            <button
              type="button"
              onClick={() => void users.refetch()}
              className="font-semibold underline"
            >
              Coba lagi
            </button>
          </div>
        )}

        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Nama</th>
              <th className="px-4 py-3 font-medium">Username</th>
              <th className="px-4 py-3 font-medium">Email</th>
              <th className="px-4 py-3 font-medium">Role</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3">
                <span className="sr-only">Aksi</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {users.isPending && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500">
                  Memuat...
                </td>
              </tr>
            )}

            {!users.isPending && (list?.items.length ?? 0) === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500">
                  Belum ada user.
                </td>
              </tr>
            )}

            {list?.items.map((user) => {
              return (
                <tr key={user.id} className="border-b border-slate-100 last:border-0">
                  <td className="px-4 py-3">{user.full_name}</td>
                  <td className="px-4 py-3 font-mono">{user.username}</td>
                  <td className="px-4 py-3">{user.email ?? '-'}</td>
                  <td className="px-4 py-3">{user.role.name}</td>
                  <td className="px-4 py-3">
                    <span
                      className={[
                        'rounded px-1.5 py-0.5 text-[11px] font-medium',
                        user.is_active
                          ? 'bg-emerald-50 text-emerald-700'
                          : 'bg-slate-100 text-slate-600',
                      ].join(' ')}
                    >
                      {user.is_active ? 'Aktif' : 'Nonaktif'}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    {sesi.can('pengaturan.user', 'can_update') && (
                      <AksiMenu
                        items={[
                          {
                            label: 'Edit',
                            onClick: () => navigate(`/setting/user/update/${user.id}`),
                          },
                          {
                            label: user.is_active ? 'Deactivated' : 'Activated',
                            onClick: () =>
                              statusMutation.mutate({ id: user.id, aktif: !user.is_active }),
                          },
                        ]}
                      />
                    )}
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>

        <div className="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-sm text-slate-500">
          <p>
            {list ? `Halaman ${list.page} dari ${totalHalaman}` : ''}
          </p>
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
