import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { getPoli, updatePoli } from '../../api/poli'
import type { PoliRow } from '../../api/poli'
import { FormMaster } from '../../components/FormMaster'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  nama: z.string().min(1, 'Nama poli wajib diisi').max(64, 'Maksimal 64 karakter'),
  biaya_daftar: z.number().min(0, 'Tidak boleh minus'),
  initial: z.string().regex(/^[A-Z]{3}$/, 'Initial antrean wajib 3 huruf'),
})

function besar(text: string): string {
  return text.toUpperCase()
}

type PoliValues = z.infer<typeof schema>

export function EditPage() {
  const { id } = useParams<{ id: string }>()

  const polis = useQuery({
    queryKey: ['poli', id],
    queryFn: () => getPoli(id as string),
    enabled: id !== undefined,
    retry: false,
  })

  if (polis.isPending) {
    return <p className="text-sm text-slate-500">Memuat...</p>
  }

  if (polis.isError) {
    return (
      <div className="space-y-4">
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {errorMessage(polis.error)}
        </div>
        <Link to="/master/poli" className="text-sm font-medium text-teal-700 underline">
          Kembali ke List Poli
        </Link>
      </div>
    )
  }

  return <Form poli={polis.data} />
}

function Form({ poli }: { poli: PoliRow }) {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const mutation = useMutation({
    mutationFn: (values: PoliValues) => updatePoli(poli.id, values),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['polis'] })
      toast.success(result.message)
      navigate('/master/poli')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<PoliValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      nama: poli.nama,
      biaya_daftar: poli.biaya_daftar,
      initial: poli.initial,
    },
  })

  return (
    <FormMaster judul="Ubah Poli" pending={mutation.isPending} onSubmit={handleSubmit((values) => mutation.mutate(values))}>
      <div>
        <label htmlFor="kode" className="mb-1.5 block text-sm font-medium">
          Kode
        </label>
        <input id="kode" type="text" value={poli.kode} disabled className="input" />
      </div>

      <div>
        <label htmlFor="nama" className="mb-1.5 block text-sm font-medium">
          Nama poli
        </label>
        <input
          id="nama"
          type="text"
          className="input"
          aria-invalid={errors.nama !== undefined}
          {...register('nama')}
        />
        {errors.nama && <p className="mt-1 text-xs text-red-600">{errors.nama.message}</p>}
      </div>

      <div>
        <label htmlFor="initial" className="mb-1.5 block text-sm font-medium">
          Initial antrean
        </label>
        <input
          id="initial"
          type="text"
          className="input uppercase"
          aria-invalid={errors.initial !== undefined}
          {...register('initial', { setValueAs: besar })}
        />
        {errors.initial && <p className="mt-1 text-xs text-red-600">{errors.initial.message}</p>}
      </div>

      <div>
        <label htmlFor="biaya_daftar" className="mb-1.5 block text-sm font-medium">
          Biaya daftar
        </label>
        <input
          id="biaya_daftar"
          type="number"
          className="input"
          aria-invalid={errors.biaya_daftar !== undefined}
          {...register('biaya_daftar', { valueAsNumber: true })}
        />
        {errors.biaya_daftar && (
          <p className="mt-1 text-xs text-red-600">{errors.biaya_daftar.message}</p>
        )}
      </div>
    </FormMaster>
  )
}


