import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Controller, useFieldArray, useForm } from 'react-hook-form'
import { useNavigate } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { createJadwal } from '../../api/jadwal'
import { listKaryawans } from '../../api/karyawan'
import { listPolis } from '../../api/poli'
import { FormMaster } from '../../components/FormMaster'
import { SearchableSelect } from '../../components/SearchableSelect'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  karyawan_id: z.string().min(1, 'Pilih dokter'),
  poli_id: z.string().min(1, 'Pilih poli'),
  sesi: z
    .array(
      z.object({
        hari: z.string().min(1, 'Pilih hari'),
        jam_mulai: z.string().min(1, 'Jam mulai wajib diisi'),
        jam_selesai: z.string().min(1, 'Jam selesai wajib diisi'),
        kuota: z.number().min(1, 'Kuota minimal 1'),
      }),
    )
    .min(1, 'Minimal satu sesi'),
})

type JadwalValues = z.infer<typeof schema>

export function CreatePage() {
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
      createJadwal({
        karyawan_id: values.karyawan_id,
        poli_id: values.poli_id,
        sesi: values.sesi.map((sesi) => ({
          hari: Number(sesi.hari),
          jam_mulai: sesi.jam_mulai,
          jam_selesai: sesi.jam_selesai,
          kuota: Number(sesi.kuota),
        })),
      }),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['jadwals'] })
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
      karyawan_id: '',
      poli_id: '',
      sesi: [{ hari: '', jam_mulai: '', jam_selesai: '', kuota: 1 }],
    },
  })

  const { fields, append, remove } = useFieldArray({ control, name: 'sesi' })

  return (
    <FormMaster judul="Tambah Jadwal" pending={mutation.isPending} onSubmit={handleSubmit((values) => mutation.mutate(values))}>
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

      <div className="space-y-2">
        {fields.map((field, index) => (
          <div key={field.id} className="rounded-md border border-slate-200 p-3">
            <div className="grid gap-3 sm:grid-cols-[1fr_7rem_7rem_5rem_auto]">
              <div>
                <label htmlFor={`sesi-${index}-hari`} className="mb-1.5 block text-sm font-medium">
                  Hari
                </label>
                <Controller
                  control={control}
                  name={`sesi.${index}.hari`}
                  render={({ field }) => (
                    <SearchableSelect
                      id={`sesi-${index}-hari`}
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
                    />
                  )}
                />
              </div>
              <div>
                <label htmlFor={`sesi-${index}-mulai`} className="mb-1.5 block text-sm font-medium">
                  Jam mulai
                </label>
                <input
                  id={`sesi-${index}-mulai`}
                  type="time"
                  className="input"
                  aria-invalid={errors.sesi?.[index]?.jam_mulai !== undefined}
                  {...register(`sesi.${index}.jam_mulai`)}
                />
              </div>
              <div>
                <label htmlFor={`sesi-${index}-selesai`} className="mb-1.5 block text-sm font-medium">
                  Jam selesai
                </label>
                <input
                  id={`sesi-${index}-selesai`}
                  type="time"
                  className="input"
                  aria-invalid={errors.sesi?.[index]?.jam_selesai !== undefined}
                  {...register(`sesi.${index}.jam_selesai`)}
                />
              </div>
              <div>
                <label htmlFor={`sesi-${index}-kuota`} className="mb-1.5 block text-sm font-medium">
                  Kuota
                </label>
                <input
                  id={`sesi-${index}-kuota`}
                  type="number"
                  className="input"
                  aria-invalid={errors.sesi?.[index]?.kuota !== undefined}
                  {...register(`sesi.${index}.kuota`, { valueAsNumber: true })}
                />
              </div>
              <div className="flex items-end">
                <button
                  type="button"
                  onClick={() => remove(index)}
                  disabled={fields.length === 1}
                  aria-label={`Hapus sesi ${index + 1}`}
                  className="rounded border border-red-200 px-2 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 disabled:opacity-40"
                >
                  Hapus
                </button>
              </div>
            </div>
            {(errors.sesi?.[index]?.hari || errors.sesi?.[index]?.jam_mulai || errors.sesi?.[index]?.jam_selesai || errors.sesi?.[index]?.kuota) && (
              <p className="mt-1 text-xs text-red-600">Lengkapi sesi ini: hari, jam, dan kuota minimal 1.</p>
            )}
          </div>
        ))}
      </div>

      <button
        type="button"
        onClick={() => append({ hari: '', jam_mulai: '', jam_selesai: '', kuota: 1 })}
        className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
      >
        + Tambah sesi
      </button>
    </FormMaster>
  )
}
