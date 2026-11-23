import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { listRoles } from '../../api/role'
import { createUser } from '../../api/user'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  username: z
    .string()
    .regex(/^[A-Za-z0-9_.]{3,64}$/, 'Username 3 sampai 64 karakter huruf, angka, titik, atau garis bawah'),
  full_name: z.string().min(1, 'Nama lengkap wajib diisi').max(120, 'Maksimal 120 karakter'),
  email: z.string().max(128, 'Maksimal 128 karakter'),
  role_id: z.string().min(1, 'Role wajib dipilih'),
  password: z.string().min(8, 'Password minimal 8 karakter'),
})

type UserValues = z.infer<typeof schema>

export function CreatePage() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const roles = useQuery({
    queryKey: ['roles'],
    queryFn: listRoles,
    select: (data) => data.items,
  })

  const mutation = useMutation({
    mutationFn: createUser,
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['users'] })
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
    defaultValues: { username: '', full_name: '', email: '', role_id: '', password: '' },
  })

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">Tambah User</h1>

      <form
        className="card max-w-xl space-y-4 p-5"
        onSubmit={handleSubmit((values) => mutation.mutate(values))}
        noValidate
      >
        <div>
          <label htmlFor="username" className="mb-1.5 block text-sm font-medium">
            Username
          </label>
          <input
            id="username"
            type="text"
            autoComplete="off"
            autoFocus
            className="input"
            aria-invalid={errors.username !== undefined}
            {...register('username')}
          />
          {errors.username && (
            <p className="mt-1 text-xs text-red-600">{errors.username.message}</p>
          )}
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

        <div>
          <label htmlFor="password" className="mb-1.5 block text-sm font-medium">
            Password
          </label>
          <input
            id="password"
            type="password"
            autoComplete="new-password"
            className="input"
            aria-invalid={errors.password !== undefined}
            {...register('password')}
          />
          {errors.password && (
            <p className="mt-1 text-xs text-red-600">{errors.password.message}</p>
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

