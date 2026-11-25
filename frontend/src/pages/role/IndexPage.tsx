import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { errorMessage } from '../../api/client'
import { listRoles, setRoleStatus } from '../../api/role'
import { AksiMenu } from '../../components/AksiMenu'
import { Can } from '../../auth/MenuGuard'
import { useSession } from '../../auth/session'
import { useToast } from '../../hooks/useToast'

export function IndexPage() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const sesi = useSession()
  const toast = useToast()

  const roles = useQuery({
    queryKey: ['roles'],
    queryFn: listRoles,
  })

  const statusMutation = useMutation({
    mutationFn: ({ id, aktif }: { id: string; aktif: boolean }) => setRoleStatus(id, aktif),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['roles'] })
      toast.success(result.message)
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const data = roles.data

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-xl font-semibold sm:text-2xl">List Role</h1>
        <Can kode="pengaturan.role" action="can_create">
          <button
            type="button"
            onClick={() => navigate('/setting/role/create')}
            className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700"
          >
            Add
          </button>
        </Can>
      </header>

      <section className="card">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Role</th>
              <th className="px-4 py-3 font-medium">Kode</th>
              <th className="px-4 py-3 font-medium">User</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3">
                <span className="sr-only">Aksi</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {roles.isPending && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-slate-500">
                  Memuat...
                </td>
              </tr>
            )}

            {roles.isError && (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-slate-500">
                  {roles.error.message}
                </td>
              </tr>
            )}

            {data?.items.map((role) => (
              <tr key={role.id} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3">{role.name}</td>
                <td className="px-4 py-3 font-mono">{role.code}</td>
                <td className="px-4 py-3 tabular-nums">{role.user_count}</td>
                <td className="px-4 py-3">
                  <span
                    className={[
                      'rounded px-1.5 py-0.5 text-[11px] font-medium',
                      role.is_active
                        ? 'bg-emerald-50 text-emerald-700'
                        : 'bg-slate-100 text-slate-600',
                    ].join(' ')}
                  >
                    {role.is_active ? 'Aktif' : 'Nonaktif'}
                  </span>
                </td>
                <td className="px-4 py-3">
                  {sesi.can('pengaturan.role', 'can_update') && (
                    <AksiMenu
                      items={[
                        {
                          label: 'Edit',
                          onClick: () => navigate(`/setting/role/update/${role.id}`),
                        },
                        {
                          label: 'Access',
                          onClick: () => navigate(`/setting/role/access/${role.id}`),
                        },
                        {
                          label: role.is_active ? 'Deactivated' : 'Activated',
                          onClick: () =>
                            statusMutation.mutate({ id: role.id, aktif: !role.is_active }),
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
