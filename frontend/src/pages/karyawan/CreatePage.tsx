import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router-dom'
import { useState } from 'react'
import { z } from 'zod'
import { errorMessage } from '../../api/client'
import { createKaryawan, listJabatans, listSpesialis } from '../../api/karyawan'
import { FormMaster } from '../../components/FormMaster'
import { useToast } from '../../hooks/useToast'

const schema = z.object({
  nama_karyawan: z.string().min(1, 'Nama karyawan wajib diisi').max(120, 'Maksimal 120 karakter'),
  jenis_kelamin: z.string().min(1, 'Pilih jenis kelamin'),
  tanggal_lahir: z.string(),
  jabatan_id: z.string().min(1, 'Pilih jabatan'),
  spesialis_id: z.string(),
  no_hp: z.string(),
  user_id: z.string(),
  tanggal_bergabung: z.string(),
})

type KaryawanValues = z.infer<typeof schema>

export function CreatePage() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const toast = useToast()

  const jabatans = useQuery({ queryKey: ['jabatans'], queryFn: listJabatans })
  const spesialis = useQuery({ queryKey: ['spesialis'], queryFn: listSpesialis })

  const mutation = useMutation({
    mutationFn: createKaryawan,
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['karyawans'] })
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
      nama_karyawan: '',
      jenis_kelamin: '',
      tanggal_lahir: '',
      jabatan_id: '',
      spesialis_id: '',
      no_hp: '',
      user_id: '',
      tanggal_bergabung: '',
    },
  })

  const [jabatanDipilih, setJabatanDipilih] = useState('')
  const dokter = jabatans.data?.find((j) => j.id === jabatanDipilih)?.kode === 'dokter'

  return (
    <FormMaster judul="Tambah Karyawan" pending={mutation.isPending} onSubmit={handleSubmit((values) => mutation.mutate(values))}>
      <div>
        <label htmlFor="nama_karyawan" className="mb-1.5 block text-sm font-medium">
          Nama karyawan
        </label>
        <input
          id="nama_karyawan"
          type="text"
          autoFocus
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
            <option value="">Pilih</option>
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
          </select>
          {errors.jenis_kelamin && (
            <p className="mt-1 text-xs text-red-600">{errors.jenis_kelamin.message}</p>
          )}
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
          <select id="spesialis_id" className="input" {...register('spesialis_id')}>
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


