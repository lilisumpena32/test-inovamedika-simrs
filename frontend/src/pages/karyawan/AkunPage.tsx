import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Controller, useForm } from 'react-hook-form'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { getKaryawan, setKaryawanAkun } from '../../api/karyawan'
import type { KaryawanRow } from '../../api/karyawan'
import { listUsers } from '../../api/user'
import { SearchableSelect } from '../../components/SearchableSelect'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  user_id: z.string(),
})

type AkunValues = z.infer<typeof schema>

export function AkunPage() {
  const { id } = useParams<{ id: string }>()

  const karyawans = useQuery({
    queryKey: ['karyawan', id],
    queryFn: () => getKaryawan(id as string),
    enabled: id !== undefined,
    retry: false,
  })

  if (karyawans.isPending) {
    return <p className="text-sm text-slate-500">Memuat...</p>
  }

  if (karyawans.isError) {
    return (
      <div className="space-y-4">
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {errorMessage(karyawans.error)}
        </div>
        <Link to="/master/karyawan" className="text-sm font-medium text-teal-700 underline">
          Kembali ke List Karyawan
        </Link>
      </div>
    )
  }

  return <Form karyawan={karyawans.data} />
}

function Form({ karyawan }: { karyawan: KaryawanRow }) {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const akun = useQuery({
    queryKey: ['users-tanpa-karyawan'],
    queryFn: () => listUsers({ q: '', role: '', is_active: 'true', tanpa_karyawan: '1', page: 1 }),
    select: (data) => data.items,
  })

  const mutation = useMutation({
    mutationFn: (values: AkunValues) =>
      setKaryawanAkun(karyawan.id, values.user_id === '' ? null : values.user_id),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['karyawans'] })
      void queryClient.invalidateQueries({ queryKey: ['karyawan', karyawan.id] })
      void queryClient.invalidateQueries({ queryKey: ['users-tanpa-karyawan'] })
      toast.success(result.message)
      navigate('/master/karyawan')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const {
    handleSubmit,
    control,
  } = useForm<AkunValues>({
    resolver: zodResolver(schema),
    defaultValues: { user_id: karyawan.user?.id ?? '' },
  })

  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold sm:text-2xl">Akun Karyawan</h1>

      <section className="card p-5">
        <p className="text-sm font-semibold">{karyawan.nama_karyawan}</p>
        <p className="font-mono text-sm text-slate-500">{karyawan.kode_karyawan}</p>

        <form
          className="mt-5 space-y-4"
          onSubmit={handleSubmit((values) => mutation.mutate(values))}
          noValidate
        >
          <div>
            <label htmlFor="user_id" className="mb-1.5 block text-sm font-medium">
              Akun login
            </label>
            <Controller
              control={control}
              name="user_id"
              render={({ field }) => (
                <SearchableSelect
                  id="user_id"
                  value={field.value}
                  onChange={field.onChange}
                  options={[
                    { label: '(tanpa akun)', value: '' },
                    ...(karyawan.user
                      ? [{ label: `${karyawan.user.username} (terhubung)`, value: karyawan.user.id }]
                      : []),
                    ...(akun.data ?? []).map((user) => ({
                      label: user.username,
                      value: user.id,
                    })),
                  ]}
                  placeholder="(tanpa akun)"
                  ariaLabel="Akun login"
                />
              )}
            />
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
              onClick={() => navigate('/master/karyawan')}
              className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
              Batal
            </button>
          </div>
        </form>
      </section>
    </div>
  )
}
