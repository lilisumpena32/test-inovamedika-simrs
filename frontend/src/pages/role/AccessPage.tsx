import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { errorMessage } from '../../api/client'
import { listRoles, setRoleAccess } from '../../api/role'
import type { RoleData, RoleRow } from '../../api/role'
import type { MenuAccess } from '../../api/types'
import { useToast } from '../../hooks/useToast'

const HAK: ReadonlyArray<{ key: keyof MenuAccess; label: string }> = [
  { key: 'can_view', label: 'Lihat' },
  { key: 'can_create', label: 'Tambah' },
  { key: 'can_update', label: 'Ubah' },
  { key: 'can_delete', label: 'Hapus' },
  { key: 'can_auth', label: 'Setujui' },
]

const TANPA_HAK: MenuAccess = {
  can_view: false,
  can_create: false,
  can_update: false,
  can_delete: false,
  can_auth: false,
}

export function AccessPage() {
  const { id } = useParams<{ id: string }>()

  const roles = useQuery({
    queryKey: ['roles'],
    queryFn: listRoles,
  })

  const role = roles.data?.items.find((item) => item.id === id)

  if (roles.isPending) {
    return <p className="text-sm text-slate-500">Memuat...</p>
  }

  if (roles.isError) {
    return (
      <div className="space-y-4">
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {errorMessage(roles.error)}
        </div>
        <Link to="/setting/role" className="text-sm font-medium text-teal-700 underline">
          Kembali ke List Role
        </Link>
      </div>
    )
  }

  if (role === undefined) {
    return (
      <div className="space-y-4">
        <p className="text-sm text-slate-500">Role tidak ditemukan.</p>
        <Link to="/setting/role" className="text-sm font-medium text-teal-700 underline">
          Kembali ke List Role
        </Link>
      </div>
    )
  }

  return <Form data={roles.data} role={role} />
}

function Form({ data, role }: { data: RoleData; role: RoleRow }) {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const [hak, setHak] = useState<Record<string, MenuAccess>>(
    () => data.matrix[role.id] ?? {},
  )

  const mutation = useMutation({
    mutationFn: () => setRoleAccess(role.id, hak),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['roles'] })
      toast.success(result.message)
      navigate('/setting/role')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const ubah = (menuId: string, key: keyof MenuAccess, nilai: boolean) => {
    setHak((lama) => ({
      ...lama,
      [menuId]: { ...(lama[menuId] ?? TANPA_HAK), [key]: nilai },
    }))
  }

  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold sm:text-2xl">Access Role</h1>

      <section className="card">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[40rem] text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Menu</th>
              {HAK.map((item) => (
                <th key={item.key} className="px-4 py-3 text-center font-medium">
                  {item.label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {data.menus.map((menu) => (
              <tr key={menu.id} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3">
                  {menu.parent !== null && (
                    <span className="mr-1.5 text-xs text-slate-400">{menu.parent}</span>
                  )}
                  {menu.label}
                </td>
                {HAK.map((item) => (
                  <td key={item.key} className="px-4 py-3 text-center">
                    <input
                      type="checkbox"
                      checked={(hak[menu.id] ?? TANPA_HAK)[item.key]}
                      onChange={(e) => ubah(menu.id, item.key, e.target.checked)}
                      aria-label={`${menu.label} ${item.label}`}
                      className="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                    />
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
        </div>
      </section>

      <div className="flex gap-2">
        <button
          type="button"
          disabled={mutation.isPending}
          onClick={() => mutation.mutate()}
          className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
        >
          {mutation.isPending ? 'Menyimpan...' : 'Simpan'}
        </button>
        <button
          type="button"
          onClick={() => navigate('/setting/role')}
          className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Batal
        </button>
      </div>
    </div>
  )
}
