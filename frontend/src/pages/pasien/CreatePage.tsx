import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { Controller, useForm } from 'react-hook-form'
import { useNavigate } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { createPasien } from '../../api/pasien'
import { FormMaster } from '../../components/FormMaster'
import { SearchableSelect } from '../../components/SearchableSelect'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  nama: z.string().min(1, 'Nama pasien wajib diisi').max(120, 'Maksimal 120 karakter'),
  nik: z
    .string()
    .regex(/^\d{16}$/, 'NIK harus 16 digit angka')
    .optional()
    .or(z.literal('')),
  tanggal_lahir: z.string(),
  jenis_kelamin: z.string().min(1, 'Pilih jenis kelamin'),
  no_hp: z.string(),
  alamat: z.string(),
})

type PasienValues = z.infer<typeof schema>

export function CreatePage() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const mutation = useMutation({
    mutationFn: (values: PasienValues) =>
      createPasien({
        ...values,
        nik: values.nik === undefined || values.nik === '' ? null : values.nik,
      }),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['pasiens'] })
      toast.success(result.message)
      navigate('/master/pasien')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const {
    register,
    handleSubmit,
    control,
    formState: { errors },
  } = useForm<PasienValues>({
    resolver: zodResolver(schema),
    defaultValues: { nama: '', nik: '', tanggal_lahir: '', jenis_kelamin: '', no_hp: '', alamat: '' },
  })

  return (
    <FormMaster judul="Tambah Pasien" pending={mutation.isPending} onSubmit={handleSubmit((values) => mutation.mutate(values))}>
      <div>
        <label htmlFor="nama" className="mb-1.5 block text-sm font-medium">
          Nama pasien
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
        <label htmlFor="nik" className="mb-1.5 block text-sm font-medium">
          NIK
        </label>
        <input
          id="nik"
          type="text"
          className="input"
          aria-invalid={errors.nik !== undefined}
          {...register('nik')}
        />
        {errors.nik && <p className="mt-1 text-xs text-red-600">{errors.nik.message}</p>}
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label htmlFor="tanggal_lahir" className="mb-1.5 block text-sm font-medium">
            Tanggal lahir
          </label>
          <input id="tanggal_lahir" type="date" className="input" {...register('tanggal_lahir')} />
        </div>
        <div>
          <label htmlFor="jenis_kelamin" className="mb-1.5 block text-sm font-medium">
            Jenis kelamin
          </label>
          <Controller
            control={control}
            name="jenis_kelamin"
            render={({ field }) => (
              <>
                <SearchableSelect
                  id="jenis_kelamin"
                  value={field.value}
                  onChange={field.onChange}
                  options={[
                    { label: 'Pilih', value: '' },
                    { label: 'Laki-laki', value: 'L' },
                    { label: 'Perempuan', value: 'P' },
                  ]}
                  placeholder="Pilih"
                  ariaLabel="Jenis kelamin"
                  invalid={errors.jenis_kelamin !== undefined}
                />
                {errors.jenis_kelamin && (
                  <p className="mt-1 text-xs text-red-600">{errors.jenis_kelamin.message}</p>
                )}
              </>
            )}
          />
        </div>
      </div>

      <div>
        <label htmlFor="no_hp" className="mb-1.5 block text-sm font-medium">
          No HP
        </label>
        <input id="no_hp" type="text" className="input" {...register('no_hp')} />
      </div>

      <div>
        <label htmlFor="alamat" className="mb-1.5 block text-sm font-medium">
          Alamat
        </label>
        <textarea id="alamat" rows={3} className="input" {...register('alamat')} />
      </div>
    </FormMaster>
  )
}
