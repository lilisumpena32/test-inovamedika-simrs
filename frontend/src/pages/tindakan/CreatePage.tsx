import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { createTindakan } from '../../api/tindakan'
import { FormMaster } from '../../components/FormMaster'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  nama: z.string().min(1, 'Nama tindakan wajib diisi').max(64, 'Maksimal 64 karakter'),
  tarif: z.number().min(0, 'Tarif tidak boleh minus'),
})

type TindakanValues = z.infer<typeof schema>

export function CreatePage() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const mutation = useMutation({
    mutationFn: (values: TindakanValues) => createTindakan(values),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['tindakans'] })
      toast.success(result.message)
      navigate('/master/tindakan')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<TindakanValues>({
    resolver: zodResolver(schema),
    defaultValues: { nama: '', tarif: 0 },
  })

  return (
    <FormMaster judul="Tambah Tindakan" pending={mutation.isPending} onSubmit={handleSubmit((values) => mutation.mutate(values))}>
      <div>
        <label htmlFor="nama" className="mb-1.5 block text-sm font-medium">
          Nama tindakan
        </label>
        <input
          id="nama"
          type="text"
          autoFocus
          className="input"
          aria-invalid={errors.nama !== undefined}
          {...register('nama')}
        />
        {errors.nama && <p className="mt-1 text-xs text-red-600">{errors.nama.message}</p>}
      </div>

      <div>
        <label htmlFor="tarif" className="mb-1.5 block text-sm font-medium">
          Tarif
        </label>
        <input
          id="tarif"
          type="number"
          className="input"
          aria-invalid={errors.tarif !== undefined}
          {...register('tarif', { valueAsNumber: true })}
        />
        {errors.tarif && <p className="mt-1 text-xs text-red-600">{errors.tarif.message}</p>}
      </div>
    </FormMaster>
  )
}
