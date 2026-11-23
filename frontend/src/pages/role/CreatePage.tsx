import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { createRole } from '../../api/role'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  role_code: z
    .string()
    .regex(/^[A-Za-z0-9_]{3,32}$/, 'Kode role 3 sampai 32 karakter huruf, angka, atau garis bawah'),
  role_name: z.string().min(1, 'Nama role wajib diisi').max(64, 'Maksimal 64 karakter'),
})

type RoleValues = z.infer<typeof schema>

export function CreatePage() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const mutation = useMutation({
    mutationFn: createRole,
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
    defaultValues: { role_code: '', role_name: '' },
  })

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">Tambah Role</h1>

      <form
        className="card max-w-xl space-y-4 p-5"
        onSubmit={handleSubmit((values) => mutation.mutate(values))}
        noValidate
      >
        <div>
          <label htmlFor="role_code" className="mb-1.5 block text-sm font-medium">
            Kode
          </label>
          <input
            id="role_code"
            type="text"
            autoFocus
            className="input"
            aria-invalid={errors.role_code !== undefined}
            {...register('role_code')}
          />
          {errors.role_code && (
            <p className="mt-1 text-xs text-red-600">{errors.role_code.message}</p>
          )}
        </div>

        <div>
          <label htmlFor="role_name" className="mb-1.5 block text-sm font-medium">
            Nama role
          </label>
          <input
            id="role_name"
            type="text"
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
