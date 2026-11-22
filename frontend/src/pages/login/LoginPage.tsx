import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { Navigate, useLocation, useNavigate } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { useSession } from '../../auth/session'

const schema = z.object({
  username: z.string().min(1, 'Username wajib diisi'),
  password: z.string().min(1, 'Password wajib diisi'),
})

type LoginValues = z.infer<typeof schema>

export function LoginPage() {
  const { token, user, signIn } = useSession()
  const navigate = useNavigate()
  const location = useLocation()
  const from = (location.state as { from?: string } | null)?.from ?? '/'

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginValues>({
    resolver: zodResolver(schema),
    defaultValues: { username: '', password: '' },
  })

  const mutation = useMutation({
    mutationFn: (values: LoginValues) => signIn(values.username, values.password),
    onSuccess: () => navigate(from, { replace: true }),
  })

  if (token !== null && user !== null) {
    return <Navigate to="/" replace />
  }

  return (
    <div className="grid min-h-screen place-items-center bg-slate-100 px-4 py-10">
      <div className="w-full max-w-sm">
        <div className="card p-8">
          <h1 className="text-center text-lg font-semibold">Login</h1>

          <form
            className="mt-6 space-y-4"
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
                autoComplete="username"
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
              <label htmlFor="password" className="mb-1.5 block text-sm font-medium">
                Password
              </label>
              <input
                id="password"
                type="password"
                autoComplete="current-password"
                className="input"
                aria-invalid={errors.password !== undefined}
                {...register('password')}
              />
              {errors.password && (
                <p className="mt-1 text-xs text-red-600">{errors.password.message}</p>
              )}
            </div>

            {mutation.isError && (
              <div
                role="alert"
                className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
              >
                {errorMessage(mutation.error)}
              </div>
            )}

            <button
              type="submit"
              disabled={mutation.isPending}
              className="w-full rounded-md bg-teal-600 px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {mutation.isPending ? 'Memproses...' : 'Masuk'}
            </button>
          </form>
        </div>

        <p className="mt-4 text-center text-xs text-slate-500">
          © 2026 Inova Medika · Versi 1.0
        </p>
      </div>
    </div>
  )
}
