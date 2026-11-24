import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { createPoli } from '../../api/poli'
import { FormMaster } from '../../components/FormMaster'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  kode: z
    .string()
    .regex(/^[A-Z0-9_]{3,16}$/, 'Kode 3 sampai 16 karakter huruf besar, angka, atau garis bawah'),
  nama: z.string().min(1, 'Nama poli wajib diisi').max(64, 'Maksimal 64 karakter'),
  biaya_daftar: z.number().min(0, 'Tidak boleh minus'),
  initial: z.string().regex(/^[A-Z]{3}$/, 'Initial antrean wajib 3 huruf'),
})

function besar(text: string): string {
  return text.toUpperCase()
}

type PoliValues = z.infer<typeof schema>

export function CreatePage() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const mutation = useMutation({
    mutationFn: createPoli,
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
    defaultValues: { kode: '', nama: '', biaya_daftar: 0, initial: '' },
  })

  return (
    <FormMaster judul="Tambah Poli" pending={mutation.isPending} onSubmit={handleSubmit((values) => mutation.mutate(values))}>
      <div>
        <label htmlFor="kode" className="mb-1.5 block text-sm font-medium">
          Kode
        </label>
        <input
          id="kode"
          type="text"
          autoFocus
          className="input"
          aria-invalid={errors.kode !== undefined}
          {...register('kode')}
        />
        {errors.kode && <p className="mt-1 text-xs text-red-600">{errors.kode.message}</p>}
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


