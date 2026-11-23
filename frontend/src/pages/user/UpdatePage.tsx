import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { listRoles } from '../../api/role'
import { getUser, updateUser } from '../../api/user'
import type { UserRow } from '../../api/user'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  full_name: z.string().min(1, 'Nama lengkap wajib diisi').max(120, 'Maksimal 120 karakter'),
  email: z.string().max(128, 'Maksimal 128 karakter'),
  role_id: z.string().min(1, 'Role wajib dipilih'),
})

type UserValues = z.infer<typeof schema>

export function UpdatePage() {
  const { id } = useParams<{ id: string }>()

  const user = useQuery({
    queryKey: ['user', id],
    queryFn: () => getUser(id as string),
    enabled: id !== undefined,
    retry: false,
  })

  if (user.isPending) {
    return <p className="text-sm text-slate-500">Memuat...</p>
  }

  if (user.isError) {
    return (
      <div className="space-y-4">
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {errorMessage(user.error)}
        </div>
        <Link to="/setting/user" className="text-sm font-medium text-teal-700 underline">
          Kembali ke List User
        </Link>
      </div>
    )
  }

  return <Form user={user.data} />
}

function Form({ user }: { user: UserRow }) {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const roles = useQuery({
    queryKey: ['roles'],
    queryFn: listRoles,
    select: (data) => data.items,
  })

  const updateMutation = useMutation({
    mutationFn: (values: UserValues) => updateUser(user.id, values),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['users'] })
      void queryClient.invalidateQueries({ queryKey: ['user', user.id] })
      toast.success(result.message)
      navigate('/setting/user')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<UserValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      full_name: user.full_name,
      email: user.email ?? '',
      role_id: user.role.id,
    },
  })

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">Ubah User</h1>

      <form
        className="card max-w-xl space-y-4 p-5"
        onSubmit={handleSubmit((values) => updateMutation.mutate(values))}
        noValidate
      >
        <div>
          <label htmlFor="username" className="mb-1.5 block text-sm font-medium">
            Username
          </label>
          <input id="username" type="text" value={user.username} disabled className="input" />
        </div>

        <div>
          <label htmlFor="full_name" className="mb-1.5 block text-sm font-medium">
            Nama lengkap
          </label>
          <input
            id="full_name"
            type="text"
            className="input"
            aria-invalid={errors.full_name !== undefined}
            {...register('full_name')}
          />
          {errors.full_name && (
            <p className="mt-1 text-xs text-red-600">{errors.full_name.message}</p>
          )}
        </div>

        <div>
          <label htmlFor="email" className="mb-1.5 block text-sm font-medium">
            Email
          </label>
          <input
            id="email"
            type="email"
            className="input"
            aria-invalid={errors.email !== undefined}
            {...register('email')}
          />
          {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email.message}</p>}
        </div>

        <div>
          <label htmlFor="role_id" className="mb-1.5 block text-sm font-medium">
            Role
          </label>
          <select
            id="role_id"
            className="input"
            aria-invalid={errors.role_id !== undefined}
            {...register('role_id')}
          >
            <option value="">Pilih role</option>
            {(roles.data ?? []).map((role) => (
              <option key={role.id} value={role.id}>
                {role.name}
              </option>
            ))}
          </select>
          {errors.role_id && (
            <p className="mt-1 text-xs text-red-600">{errors.role_id.message}</p>
          )}
        </div>

        <div className="flex flex-wrap gap-2 pt-2">
          <button
            type="submit"
            disabled={updateMutation.isPending}
            className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
          >
            {updateMutation.isPending ? 'Menyimpan...' : 'Simpan'}
          </button>

          <button
            type="button"
            onClick={() => navigate('/setting/user')}
            className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Batal
          </button>
        </div>
      </form>
    </div>
  )
}


