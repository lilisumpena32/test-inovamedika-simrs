import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { listRoles, updateRole } from '../../api/role'
import type { RoleRow } from '../../api/role'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  role_name: z.string().min(1, 'Nama role wajib diisi').max(64, 'Maksimal 64 karakter'),
})

type RoleValues = z.infer<typeof schema>

export function EditPage() {
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

  return <Form role={role} />
}

function Form({ role }: { role: RoleRow }) {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const mutation = useMutation({
    mutationFn: (values: RoleValues) => updateRole(role.id, values),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['roles'] })
      toast.success(result.message)
      navigate('/setting/role')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<RoleValues>({
    resolver: zodResolver(schema),
    defaultValues: { role_name: role.name },
  })

  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold sm:text-2xl">Ubah Role</h1>

      <form
        className="card max-w-xl space-y-4 p-5"
        onSubmit={handleSubmit((values) => mutation.mutate(values))}
        noValidate
      >
        <div>
          <label htmlFor="kode" className="mb-1.5 block text-sm font-medium">
            Kode
          </label>
          <input id="kode" type="text" value={role.code} disabled className="input" />
        </div>

        <div>
          <label htmlFor="role_name" className="mb-1.5 block text-sm font-medium">
            Nama role
          </label>
          <input
            id="role_name"
            type="text"
            autoFocus
            className="input"
            aria-invalid={errors.role_name !== undefined}
            {...register('role_name')}
          />
          {errors.role_name && (
            <p className="mt-1 text-xs text-red-600">{errors.role_name.message}</p>
          )}
        </div>

        <div className="flex gap-2 pt-2">
          <button
            type="submit"
            disabled={mutation.isPending}
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
      </form>
    </div>
  )
}
