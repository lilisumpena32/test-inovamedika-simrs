import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Controller, useForm } from 'react-hook-form'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { getJadwal, updateJadwal } from '../../api/jadwal'
import type { JadwalRow } from '../../api/jadwal'
import { listKaryawans } from '../../api/karyawan'
import { listPolis } from '../../api/poli'
import { FormMaster } from '../../components/FormMaster'
import { SearchableSelect } from '../../components/SearchableSelect'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  karyawan_id: z.string().min(1, 'Pilih dokter'),
  poli_id: z.string().min(1, 'Pilih poli'),
  hari: z.string().min(1, 'Pilih hari'),
  jam_mulai: z.string().min(1, 'Jam mulai wajib diisi'),
  jam_selesai: z.string().min(1, 'Jam selesai wajib diisi'),
  kuota: z.number().min(1, 'Kuota minimal 1'),
})

type JadwalValues = z.infer<typeof schema>

export function EditPage() {
  const { id } = useParams<{ id: string }>()

  const jadwals = useQuery({
    queryKey: ['jadwal', id],
    queryFn: () => getJadwal(id as string),
    enabled: id !== undefined,
    retry: false,
  })

  if (jadwals.isPending) {
    return <p className="text-sm text-slate-500">Memuat...</p>
  }

  if (jadwals.isError) {
    return (
      <div className="space-y-4">
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {errorMessage(jadwals.error)}
        </div>
        <Link to="/master/jadwal" className="text-sm font-medium text-teal-700 underline">
          Kembali ke List Jadwal
        </Link>
      </div>
    )
  }

  return <Form jadwal={jadwals.data} />
}

function Form({ jadwal }: { jadwal: JadwalRow }) {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const karyawans = useQuery({
    queryKey: ['karyawan-dokter'],
    queryFn: () => listKaryawans({ q: '', jabatan: 'dokter', is_active: 'true', page: 1 }),
    select: (data) => data.items,
  })

  const polis = useQuery({
    queryKey: ['polis'],
    queryFn: listPolis,
  })

  const mutation = useMutation({
    mutationFn: (values: JadwalValues) =>
      updateJadwal(jadwal.id, {
        karyawan_id: values.karyawan_id,
        poli_id: values.poli_id,
        hari: Number(values.hari),
        jam_mulai: values.jam_mulai,
        jam_selesai: values.jam_selesai,
        kuota: Number(values.kuota),
      }),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['jadwals'] })
      void queryClient.invalidateQueries({ queryKey: ['jadwal', jadwal.id] })
      toast.success(result.message)
      navigate('/master/jadwal')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const {
    register,
    handleSubmit,
    control,
    formState: { errors },
  } = useForm<JadwalValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      karyawan_id: jadwal.karyawan?.id ?? '',
      poli_id: jadwal.poli?.id ?? '',
      hari: String(jadwal.hari),
      jam_mulai: jadwal.jam_mulai,
      jam_selesai: jadwal.jam_selesai,
      kuota: jadwal.kuota,
    },
  })

  return (
    <FormMaster judul="Ubah Jadwal" pending={mutation.isPending} onSubmit={handleSubmit((values) => mutation.mutate(values))}>
      <div>
        <label htmlFor="karyawan_id" className="mb-1.5 block text-sm font-medium">
          Dokter
        </label>
        <Controller
          control={control}
          name="karyawan_id"
          render={({ field }) => (
            <>
              <SearchableSelect
                id="karyawan_id"
                value={field.value}
                onChange={field.onChange}
                options={[
                  { label: 'Pilih dokter', value: '' },
                  ...(karyawans.data ?? []).map((karyawan) => ({
                    label: karyawan.nama_karyawan,
                    value: karyawan.id,
                  })),
                ]}
                placeholder="Pilih dokter"
                ariaLabel="Dokter"
                invalid={errors.karyawan_id !== undefined}
              />
              {errors.karyawan_id && (
                <p className="mt-1 text-xs text-red-600">{errors.karyawan_id.message}</p>
              )}
            </>
          )}
        />
      </div>

      <div>
        <label htmlFor="poli_id" className="mb-1.5 block text-sm font-medium">
          Poli
        </label>
        <Controller
          control={control}
          name="poli_id"
          render={({ field }) => (
            <>
              <SearchableSelect
                id="poli_id"
                value={field.value}
                onChange={field.onChange}
                options={[
                  { label: 'Pilih poli', value: '' },
                  ...(polis.data ?? []).map((poli) => ({
                    label: poli.nama,
                    value: poli.id,
                  })),
                ]}
                placeholder="Pilih poli"
                ariaLabel="Poli"
                invalid={errors.poli_id !== undefined}
              />
              {errors.poli_id && (
                <p className="mt-1 text-xs text-red-600">{errors.poli_id.message}</p>
              )}
            </>
          )}
        />
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <div>
          <label htmlFor="hari" className="mb-1.5 block text-sm font-medium">
            Hari
          </label>
          <Controller
            control={control}
            name="hari"
            render={({ field }) => (
              <>
                <SearchableSelect
                  id="hari"
                  value={field.value}
                  onChange={field.onChange}
                  options={[
                    { label: 'Pilih', value: '' },
                    { label: 'Senin', value: '1' },
                    { label: 'Selasa', value: '2' },
                    { label: 'Rabu', value: '3' },
                    { label: 'Kamis', value: '4' },
                    { label: 'Jumat', value: '5' },
                    { label: 'Sabtu', value: '6' },
                    { label: 'Minggu', value: '7' },
                  ]}
                  placeholder="Pilih"
                  ariaLabel="Hari"
                  invalid={errors.hari !== undefined}
                />
                {errors.hari && (
                  <p className="mt-1 text-xs text-red-600">{errors.hari.message}</p>
                )}
              </>
            )}
          />
        </div>
        <div>
          <label htmlFor="jam_mulai" className="mb-1.5 block text-sm font-medium">
            Jam mulai
          </label>
          <input
            id="jam_mulai"
            type="time"
            className="input"
            aria-invalid={errors.jam_mulai !== undefined}
            {...register('jam_mulai')}
          />
          {errors.jam_mulai && (
            <p className="mt-1 text-xs text-red-600">{errors.jam_mulai.message}</p>
          )}
        </div>
        <div>
          <label htmlFor="jam_selesai" className="mb-1.5 block text-sm font-medium">
            Jam selesai
          </label>
          <input
            id="jam_selesai"
            type="time"
            className="input"
            aria-invalid={errors.jam_selesai !== undefined}
            {...register('jam_selesai')}
          />
          {errors.jam_selesai && (
            <p className="mt-1 text-xs text-red-600">{errors.jam_selesai.message}</p>
          )}
        </div>
      </div>

      <div>
        <label htmlFor="kuota" className="mb-1.5 block text-sm font-medium">
          Kuota
        </label>
        <input
          id="kuota"
          type="number"
          className="input"
          aria-invalid={errors.kuota !== undefined}
          {...register('kuota', { valueAsNumber: true })}
        />
        {errors.kuota && <p className="mt-1 text-xs text-red-600">{errors.kuota.message}</p>}
      </div>
    </FormMaster>
  )
}





