import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { getJenisLab, updateJenisLab } from '../../api/jenislab'
import { FormMaster } from '../../components/FormMaster'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  nama: z.string().min(1, 'Nama jenis lab wajib diisi').max(64, 'Maksimal 64 karakter'),
  tarif: z.number().min(0, 'Tarif tidak boleh minus'),
})

type JenisLabValues = z.infer<typeof schema>

export function EditPage() {
  const { id } = useParams<{ id: string }>()

  const jenisLab = useQuery({
    queryKey: ['jenisLab', id],
    queryFn: () => getJenisLab(id as string),
    enabled: id !== undefined,
    retry: false,
  })

  if (jenisLab.isPending) {
    return <p className="text-sm text-slate-500">Memuat...</p>
  }

  if (jenisLab.isError) {
    return (
      <div className="space-y-4">
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {errorMessage(jenisLab.error)}
        </div>
        <Link to="/master/jenis-lab" className="text-sm font-medium text-teal-700 underline">
          Kembali ke List Jenis Lab
        </Link>
      </div>
    )
  }

  return <Form id={jenisLab.data.id} awal={{ nama: jenisLab.data.nama, tarif: jenisLab.data.tarif }} />
}

function Form({ id, awal }: { id: string; awal: JenisLabValues }) {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const mutation = useMutation({
    mutationFn: (values: JenisLabValues) => updateJenisLab(id, values),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['jenisLabs'] })
      toast.success(result.message)
      navigate('/master/jenis-lab')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<JenisLabValues>({
    resolver: zodResolver(schema),
    defaultValues: awal,
  })

  return (
    <FormMaster judul="Ubah Jenis Lab" pending={mutation.isPending} onSubmit={handleSubmit((values) => mutation.mutate(values))}>
      <div>
        <label htmlFor="nama" className="mb-1.5 block text-sm font-medium">
          Nama jenis lab
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
