import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { createObat, listKategoris, listGolonganObats, listSatuans } from '../../api/obat'
import { FormMaster } from '../../components/FormMaster'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  nama_obat: z.string().min(1, 'Nama obat wajib diisi').max(120, 'Maksimal 120 karakter'),
  kategori_id: z.string().min(1, 'Pilih kategori'),
  golongan_obat_id: z.string().min(1, 'Pilih golongan'),
  satuan_id: z.string().min(1, 'Pilih satuan'),
  harga_beli: z.number().min(0, 'Tidak boleh minus'),
  harga_jual: z.number().min(0, 'Tidak boleh minus'),
  stok_minimum: z.number().min(0, 'Tidak boleh minus'),
})

type ObatValues = z.infer<typeof schema>

export function CreatePage() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const kategoris = useQuery({ queryKey: ['kategoris'], queryFn: listKategoris })
  const golongans = useQuery({ queryKey: ['golongans'], queryFn: listGolonganObats })
  const satuans = useQuery({ queryKey: ['satuans'], queryFn: listSatuans })

  const mutation = useMutation({
    mutationFn: createObat,
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['obats'] })
      toast.success(result.message)
      navigate('/farmasi/obat')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ObatValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      nama_obat: '',
      kategori_id: '',
      golongan_obat_id: '',
      satuan_id: '',
      harga_beli: 0,
      harga_jual: 0,
      stok_minimum: 0,
    },
  })

  return (
    <FormMaster judul="Tambah Obat" pending={mutation.isPending} onSubmit={handleSubmit((values) => mutation.mutate(values))}>
      <div>
        <label htmlFor="nama_obat" className="mb-1.5 block text-sm font-medium">
          Nama obat
        </label>
        <input
          id="nama_obat"
          type="text"
          autoFocus
          className="input"
          aria-invalid={errors.nama_obat !== undefined}
          {...register('nama_obat')}
        />
        {errors.nama_obat && (
          <p className="mt-1 text-xs text-red-600">{errors.nama_obat.message}</p>
        )}
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <div>
          <label htmlFor="kategori_id" className="mb-1.5 block text-sm font-medium">
            Kategori
          </label>
          <select
            id="kategori_id"
            className="input"
            aria-invalid={errors.kategori_id !== undefined}
            {...register('kategori_id')}
          >
            <option value="">Pilih</option>
            {(kategoris.data ?? []).map((item) => (
              <option key={item.id} value={item.id}>
                {item.name}
              </option>
            ))}
          </select>
          {errors.kategori_id && (
            <p className="mt-1 text-xs text-red-600">{errors.kategori_id.message}</p>
          )}
        </div>
        <div>
          <label htmlFor="golongan_obat_id" className="mb-1.5 block text-sm font-medium">
            Golongan
          </label>
          <select
            id="golongan_obat_id"
            className="input"
            aria-invalid={errors.golongan_obat_id !== undefined}
            {...register('golongan_obat_id')}
          >
            <option value="">Pilih</option>
            {(golongans.data ?? []).map((item) => (
              <option key={item.id} value={item.id}>
                {item.name}
              </option>
            ))}
          </select>
          {errors.golongan_obat_id && (
            <p className="mt-1 text-xs text-red-600">{errors.golongan_obat_id.message}</p>
          )}
        </div>
        <div>
          <label htmlFor="satuan_id" className="mb-1.5 block text-sm font-medium">
            Satuan
          </label>
          <select
            id="satuan_id"
            className="input"
            aria-invalid={errors.satuan_id !== undefined}
            {...register('satuan_id')}
          >
            <option value="">Pilih</option>
            {(satuans.data ?? []).map((item) => (
              <option key={item.id} value={item.id}>
                {item.name}
              </option>
            ))}
          </select>
          {errors.satuan_id && (
            <p className="mt-1 text-xs text-red-600">{errors.satuan_id.message}</p>
          )}
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <div>
          <label htmlFor="harga_beli" className="mb-1.5 block text-sm font-medium">
            Harga beli
          </label>
          <input
            id="harga_beli"
            type="number"
            className="input"
            aria-invalid={errors.harga_beli !== undefined}
            {...register('harga_beli', { valueAsNumber: true })}
          />
          {errors.harga_beli && (
            <p className="mt-1 text-xs text-red-600">{errors.harga_beli.message}</p>
          )}
        </div>
        <div>
          <label htmlFor="harga_jual" className="mb-1.5 block text-sm font-medium">
            Harga jual
          </label>
          <input
            id="harga_jual"
            type="number"
            className="input"
            aria-invalid={errors.harga_jual !== undefined}
            {...register('harga_jual', { valueAsNumber: true })}
          />
          {errors.harga_jual && (
            <p className="mt-1 text-xs text-red-600">{errors.harga_jual.message}</p>
          )}
        </div>
        <div>
          <label htmlFor="stok_minimum" className="mb-1.5 block text-sm font-medium">
            Stok minimum
          </label>
          <input
            id="stok_minimum"
            type="number"
            className="input"
            aria-invalid={errors.stok_minimum !== undefined}
            {...register('stok_minimum', { valueAsNumber: true })}
          />
          {errors.stok_minimum && (
            <p className="mt-1 text-xs text-red-600">{errors.stok_minimum.message}</p>
          )}
        </div>
      </div>
    </FormMaster>
  )
}
