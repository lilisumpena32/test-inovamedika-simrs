import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { errorMessage } from '../../api/client'
import { listJadwals, namaHari, setJadwalStatus } from '../../api/jadwal'
import { listKaryawans } from '../../api/karyawan'
import { AksiMenu } from '../../components/AksiMenu'
import { Can } from '../../auth/MenuGuard'
import { useSession } from '../../auth/session'
import { useToast } from '../../hooks/useToast'

export function IndexPage() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const sesi = useSession()
  const toast = useToast()

  const [filterDokter, setFilterDokter] = useState('')

  const jadwals = useQuery({
    queryKey: ['jadwals', filterDokter],
    queryFn: () => listJadwals({ karyawan_id: filterDokter, poli_id: '' }),
  })

  const dokters = useQuery({
    queryKey: ['karyawan-dokter'],
    queryFn: () => listKaryawans({ q: '', jabatan: 'dokter', is_active: '', page: 1 }),
    select: (data) => data.items,
  })

  const statusMutation = useMutation({
    mutationFn: ({ id, aktif }: { id: string; aktif: boolean }) => setJadwalStatus(id, aktif),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['jadwals'] })
      toast.success(result.message)
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-semibold">List Jadwal</h1>
        <Can kode="master.jadwal" action="can_create">
          <button
            type="button"
            onClick={() => navigate('/master/jadwal/create')}
            className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700"
          >
            Add
          </button>
        </Can>
      </header>

      <section className="card">
        <div className="flex flex-wrap items-center gap-2 border-b border-slate-200 px-4 py-3">
          <select
            value={filterDokter}
            onChange={(e) => setFilterDokter(e.target.value)}
            className="select"
            aria-label="Filter dokter"
          >
            <option value="">Semua dokter</option>
            {(dokters.data ?? []).map((dokter) => (
              <option key={dokter.id} value={dokter.id}>
                {dokter.nama_karyawan}
              </option>
            ))}
          </select>
        </div>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Dokter</th>
              <th className="px-4 py-3 font-medium">Poli</th>
              <th className="px-4 py-3 font-medium">Hari</th>
              <th className="px-4 py-3 font-medium">Jam</th>
              <th className="px-4 py-3 font-medium">Kuota</th>
              <th className="px-4 py-3 font-medium">Status</th>
              <th className="px-4 py-3">
                <span className="sr-only">Aksi</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {jadwals.isPending && (
              <tr>
                <td colSpan={7} className="px-4 py-6 text-center text-slate-500">
                  Memuat...
                </td>
              </tr>
            )}

            {jadwals.isError && (
              <tr>
                <td colSpan={7} className="px-4 py-6 text-center text-slate-500">
                  {jadwals.error.message}
                </td>
              </tr>
            )}

            {jadwals.data?.length === 0 && (
              <tr>
                <td colSpan={7} className="px-4 py-6 text-center text-slate-500">
                  Belum ada jadwal.
                </td>
              </tr>
            )}

            {jadwals.data?.map((jadwal) => (
              <tr key={jadwal.id} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3">{jadwal.karyawan?.name ?? '-'}</td>
                <td className="px-4 py-3">{jadwal.poli?.name ?? '-'}</td>
                <td className="px-4 py-3">{namaHari(jadwal.hari)}</td>
                <td className="px-4 py-3 tabular-nums">
                  {jadwal.jam_mulai} - {jadwal.jam_selesai}
                </td>
                <td className="px-4 py-3 tabular-nums">{jadwal.kuota}</td>
                <td className="px-4 py-3">
                  <span
                    className={[
                      'rounded px-1.5 py-0.5 text-[11px] font-medium',
                      jadwal.is_active
                        ? 'bg-emerald-50 text-emerald-700'
                        : 'bg-slate-100 text-slate-600',
                    ].join(' ')}
                  >
                    {jadwal.is_active ? 'Aktif' : 'Nonaktif'}
                  </span>
                </td>
                <td className="px-4 py-3">
                  {sesi.can('master.jadwal', 'can_update') && (
                    <AksiMenu
                      items={[
                        {
                          label: 'Edit',
                          onClick: () => navigate(`/master/jadwal/update/${jadwal.id}`),
                        },
                        {
                          label: jadwal.is_active ? 'Deactivated' : 'Activated',
                          onClick: () =>
                            statusMutation.mutate({ id: jadwal.id, aktif: !jadwal.is_active }),
                        },
                      ]}
                    />
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>
    </div>
  )
}
