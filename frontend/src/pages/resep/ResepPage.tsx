import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'
import { errorMessage } from '../../api/client'
import { getResep, serahkanResep } from '../../api/resep'
import { useToast } from '../../hooks/useToast'

export function ResepPage() {
  const { id = '' } = useParams()
  const queryClient = useQueryClient()
  const toast = useToast()

  const resep = useQuery({
    queryKey: ['resep-detail', id],
    queryFn: () => getResep(id),
    enabled: id !== '',
    retry: false,
  })

  const serah = useMutation({
    mutationFn: () => serahkanResep(id),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['resep-detail', id] })
      void queryClient.invalidateQueries({ queryKey: ['reseps'] })
      toast.success(result.message)
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  if (resep.isPending || id === '') {
    return <p className="text-sm text-slate-500">Memuat...</p>
  }

  if (resep.isError) {
    return (
      <div className="space-y-4">
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {errorMessage(resep.error)}
        </div>
        <Link to="/farmasi/resep" className="text-sm font-medium text-teal-700 underline">
          Kembali ke daftar resep
        </Link>
      </div>
    )
  }

  const data = resep.data
  const kunjungan = data.kunjungan

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-xl font-semibold sm:text-2xl">Resep {kunjungan?.kode_antrean ?? ''}</h1>
        <Link to="/farmasi/resep" className="text-sm font-medium text-teal-700 underline">
          Kembali
        </Link>
      </header>

      <section className="card p-5 text-sm">
        <p>
          <span className="font-mono text-xs text-slate-500">{kunjungan?.pasien?.no_rm}</span>{' '}
          <span className="font-medium">{kunjungan?.pasien?.nama}</span>
        </p>
        <p className="text-slate-500">
          {kunjungan?.poli?.name} · {kunjungan?.dokter?.name} · {kunjungan?.tgl_kunjungan} · Status{' '}
          {data.status}
        </p>
      </section>

      <section className="card">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[32rem] text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Obat</th>
              <th className="px-4 py-3 font-medium">Dosis</th>
              <th className="px-4 py-3 font-medium">Frekuensi</th>
              <th className="px-4 py-3 font-medium">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            {data.items.map((item) => (
              <tr key={item.id} className="border-b border-slate-100 last:border-0">
                <td className="px-4 py-3">
                  <span className="font-mono text-xs text-slate-500">{item.obat?.kode}</span>
                  <p>{item.obat?.nama ?? '-'}</p>
                </td>
                <td className="px-4 py-3">{item.dosis ?? '-'}</td>
                <td className="px-4 py-3">{item.frekuensi ?? '-'}</td>
                <td className="px-4 py-3 tabular-nums">{item.jumlah}</td>
              </tr>
            ))}
          </tbody>
        </table>
        </div>
      </section>

      {data.status === 'menunggu' && (
        <button
          type="button"
          onClick={() => serah.mutate()}
          disabled={serah.isPending}
          className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
        >
          {serah.isPending ? 'Memproses...' : 'Serahkan obat'}
        </button>
      )}
    </div>
  )
}
