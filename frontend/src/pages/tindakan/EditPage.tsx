import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { getTindakan, updateTindakan } from '../../api/tindakan'
import { FormMaster } from '../../components/FormMaster'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  nama: z.string().min(1, 'Nama tindakan wajib diisi').max(64, 'Maksimal 64 karakter'),
  tarif: z.number().min(0, 'Tarif tidak boleh minus'),
})

type TindakanValues = z.infer<typeof schema>

export function EditPage() {
  const { id } = useParams<{ id: string }>()

  const tindakan = useQuery({
    queryKey: ['tindakan', id],
    queryFn: () => getTindakan(id as string),
    enabled: id !== undefined,
    retry: false,
  })

  if (tindakan.isPending) {
    return <p className="text-sm text-slate-500">Memuat...</p>
  }

  if (tindakan.isError) {
    return (
      <div className="space-y-4">
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {errorMessage(tindakan.error)}
        </div>
        <Link to="/master/tindakan" className="text-sm font-medium text-teal-700 underline">
          Kembali ke List Tindakan
        </Link>
      </div>
    )
  }

  return <Form id={tindakan.data.id} awal={{ nama: tindakan.data.nama, tarif: tindakan.data.tarif }} />
}

function Form({ id, awal }: { id: string; awal: TindakanValues }) {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const mutation = useMutation({
    mutationFn: (values: TindakanValues) => updateTindakan(id, values),
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
    defaultValues: awal,
  })

  return (
    <FormMaster judul="Ubah Tindakan" pending={mutation.isPending} onSubmit={handleSubmit((values) => mutation.mutate(values))}>
      <div>
        <label htmlFor="nama" className="mb-1.5 block text-sm font-medium">
          Nama tindakan
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
