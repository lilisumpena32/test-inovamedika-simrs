import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { getKaryawan, listJabatans, listSpesialis, updateKaryawan } from '../../api/karyawan'
import type { KaryawanRow } from '../../api/karyawan'
import { FormMaster } from '../../components/FormMaster'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  nama_karyawan: z.string().min(1, 'Nama karyawan wajib diisi').max(120, 'Maksimal 120 karakter'),
  jenis_kelamin: z.string().min(1, 'Pilih jenis kelamin'),
  tanggal_lahir: z.string(),
  jabatan_id: z.string().min(1, 'Pilih jabatan'),
  spesialis_id: z.string(),
  no_hp: z.string(),
  tanggal_bergabung: z.string(),
})

type KaryawanValues = z.infer<typeof schema>

export function EditPage() {
  const { id } = useParams<{ id: string }>()

  const karyawans = useQuery({
    queryKey: ['karyawan', id],
    queryFn: () => getKaryawan(id as string),
    enabled: id !== undefined,
    retry: false,
  })

  if (karyawans.isPending) {
    return <p className="text-sm text-slate-500">Memuat...</p>
  }

  if (karyawans.isError) {
    return (
      <div className="space-y-4">
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {errorMessage(karyawans.error)}
        </div>
        <Link to="/master/karyawan" className="text-sm font-medium text-teal-700 underline">
          Kembali ke List Karyawan
        </Link>
      </div>
    )
  }

  return <Form karyawan={karyawans.data} />
}

function Form({ karyawan }: { karyawan: KaryawanRow }) {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const jabatans = useQuery({ queryKey: ['jabatans'], queryFn: listJabatans })
  const spesialis = useQuery({ queryKey: ['spesialis'], queryFn: listSpesialis })

  const mutation = useMutation({
    mutationFn: (values: KaryawanValues) => updateKaryawan(karyawan.id, values),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['karyawans'] })
      void queryClient.invalidateQueries({ queryKey: ['karyawan', karyawan.id] })
      toast.success(result.message)
      navigate('/master/karyawan')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<KaryawanValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      nama_karyawan: karyawan.nama_karyawan,
      jenis_kelamin: karyawan.jenis_kelamin,
      tanggal_lahir: karyawan.tanggal_lahir ?? '',
      jabatan_id: karyawan.jabatan?.id ?? '',
      spesialis_id: karyawan.spesialis?.id ?? '',
      no_hp: karyawan.no_hp ?? '',
      tanggal_bergabung: karyawan.tanggal_bergabung ?? '',
    },
  })

  const [jabatanDipilih, setJabatanDipilih] = useState(karyawan.jabatan?.kode ?? '')
  const dokter = jabatans.data?.find((j) => j.id === jabatanDipilih)?.kode === 'dokter'

  return (
    <FormMaster judul="Ubah Karyawan" pending={mutation.isPending} onSubmit={handleSubmit((values) => mutation.mutate(values))}>
      <div>
        <label htmlFor="kode_karyawan" className="mb-1.5 block text-sm font-medium">
          Kode karyawan
        </label>
        <input
          id="kode_karyawan"
          type="text"
          value={karyawan.kode_karyawan}
          disabled
          className="input"
        />
      </div>

      <div>
        <label htmlFor="nama_karyawan" className="mb-1.5 block text-sm font-medium">
          Nama karyawan
        </label>
        <input
          id="nama_karyawan"
          type="text"
          className="input"
          aria-invalid={errors.nama_karyawan !== undefined}
          {...register('nama_karyawan')}
        />
        {errors.nama_karyawan && (
          <p className="mt-1 text-xs text-red-600">{errors.nama_karyawan.message}</p>
        )}
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label htmlFor="jenis_kelamin" className="mb-1.5 block text-sm font-medium">
            Jenis kelamin
          </label>
          <select
            id="jenis_kelamin"
            className="input"
            aria-invalid={errors.jenis_kelamin !== undefined}
            {...register('jenis_kelamin')}
          >
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
          </select>
        </div>
        <div>
          <label htmlFor="tanggal_lahir" className="mb-1.5 block text-sm font-medium">
            Tanggal lahir
          </label>
          <input id="tanggal_lahir" type="date" className="input" {...register('tanggal_lahir')} />
        </div>
      </div>

      <div>
        <label htmlFor="jabatan_id" className="mb-1.5 block text-sm font-medium">
          Jabatan
        </label>
        <select
          id="jabatan_id"
          className="input"
          aria-invalid={errors.jabatan_id !== undefined}
          {...register('jabatan_id', { onChange: (e) => setJabatanDipilih(e.target.value) })}
        >
          <option value="">Pilih jabatan</option>
          {(jabatans.data ?? []).map((jabatan) => (
            <option key={jabatan.id} value={jabatan.id}>
              {jabatan.name}
            </option>
          ))}
        </select>
        {errors.jabatan_id && (
          <p className="mt-1 text-xs text-red-600">{errors.jabatan_id.message}</p>
        )}
      </div>

      {dokter && (
        <div>
          <label htmlFor="spesialis_id" className="mb-1.5 block text-sm font-medium">
            Spesialis
          </label>
          <select
            id="spesialis_id"
            className="input"
            {...register('spesialis_id')}
          >
            <option value="">Pilih spesialis</option>
            {(spesialis.data ?? []).map((item) => (
              <option key={item.id} value={item.id}>
                {item.name}
              </option>
            ))}
          </select>
        </div>
      )}

      <div>
        <label htmlFor="no_hp" className="mb-1.5 block text-sm font-medium">
          No HP
        </label>
        <input id="no_hp" type="text" className="input" {...register('no_hp')} />
      </div>

      <div>
        <label htmlFor="tanggal_bergabung" className="mb-1.5 block text-sm font-medium">
          Tanggal bergabung
        </label>
        <input
          id="tanggal_bergabung"
          type="date"
          className="input"
          {...register('tanggal_bergabung')}
        />
      </div>
    </FormMaster>
  )
}



