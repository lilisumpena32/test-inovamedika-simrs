import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useFieldArray, useForm } from 'react-hook-form'
import { useNavigate } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { createJadwal } from '../../api/jadwal'
import { listKaryawans } from '../../api/karyawan'
import { listPolis } from '../../api/poli'
import { FormMaster } from '../../components/FormMaster'
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
        <select
          id="karyawan_id"
          className="input"
          aria-invalid={errors.karyawan_id !== undefined}
          {...register('karyawan_id')}
        >
          <option value="">Pilih dokter</option>
          {(karyawans.data ?? []).map((karyawan) => (
            <option key={karyawan.id} value={karyawan.id}>
              {karyawan.nama_karyawan}
            </option>
          ))}
        </select>
        {errors.karyawan_id && (
          <p className="mt-1 text-xs text-red-600">{errors.karyawan_id.message}</p>
        )}
      </div>

      <div>
        <label htmlFor="poli_id" className="mb-1.5 block text-sm font-medium">
          Poli
        </label>
        <select
          id="poli_id"
          className="input"
          aria-invalid={errors.poli_id !== undefined}
          {...register('poli_id')}
        >
          <option value="">Pilih poli</option>
          {(polis.data ?? []).map((poli) => (
            <option key={poli.id} value={poli.id}>
              {poli.nama}
            </option>
          ))}
        </select>
        {errors.poli_id && <p className="mt-1 text-xs text-red-600">{errors.poli_id.message}</p>}
      </div>

      <div className="space-y-2">
        {fields.map((field, index) => (
          <div key={field.id} className="rounded-md border border-slate-200 p-3">
            <div className="grid gap-3 sm:grid-cols-[1fr_7rem_7rem_5rem_auto]">
              <div>
                <label htmlFor={`sesi-${index}-hari`} className="mb-1.5 block text-sm font-medium">
                  Hari
                </label>
                <select
                  id={`sesi-${index}-hari`}
                  className="input"
                  aria-invalid={errors.sesi?.[index]?.hari !== undefined}
                  {...register(`sesi.${index}.hari`)}
                >
                  <option value="">Pilih</option>
                  <option value="1">Senin</option>
                  <option value="2">Selasa</option>
                  <option value="3">Rabu</option>
                  <option value="4">Kamis</option>
                  <option value="5">Jumat</option>
                  <option value="6">Sabtu</option>
                  <option value="7">Minggu</option>
                </select>
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
