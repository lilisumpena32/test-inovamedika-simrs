import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { errorMessage } from '../../api/client'
import { batalKunjungan, getKunjungan, STATUS_LABEL } from '../../api/kunjungan'
import { useSession } from '../../auth/session'
import { useToast } from '../../hooks/useToast'

function Baris({ label, nilai }: { label: string; nilai: string }) {
  return (
    <div className="flex justify-between gap-4 border-b border-slate-100 py-2 last:border-0">
      <dt className="text-slate-500">{label}</dt>
      <dd className="text-right font-medium">{nilai}</dd>
    </div>
  )
}

export function EditPage() {
  const { id = '' } = useParams()
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const sesi = useSession()
  const toast = useToast()

  const kunjungan = useQuery({
    queryKey: ['kunjungan', id],
    queryFn: () => getKunjungan(id),
    enabled: id !== '',
  })

  const batal = useMutation({
    mutationFn: () => batalKunjungan(id),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['kunjungans'] })
      void queryClient.invalidateQueries({ queryKey: ['antreans'] })
      void queryClient.invalidateQueries({ queryKey: ['kunjungan', id] })
      toast.success(result.message)
      navigate('/pendaftaran/list')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const data = kunjungan.data

  return (
    <div className="space-y-6">
      <h1 className="text-xl font-semibold sm:text-2xl">Kunjungan</h1>

      <section className="card max-w-xl space-y-4 p-5">
        {kunjungan.isPending && <p className="text-sm text-slate-500">Memuat...</p>}

        {kunjungan.isError && (
          <p role="alert" className="text-sm text-red-600">
            {errorMessage(kunjungan.error)}
          </p>
        )}

        {data && (
          <>
            <dl className="text-sm">
              <Baris label="No. kunjungan" nilai={data.no_kunjungan} />
              <Baris label="Tanggal" nilai={data.tgl_kunjungan} />
              <Baris
                label="Pasien"
                nilai={`${data.pasien?.no_rm ?? '-'} — ${data.pasien?.nama ?? '-'}`}
              />
              <Baris label="Poli" nilai={data.poli?.name ?? '-'} />
              <Baris label="Dokter" nilai={data.dokter?.name ?? '-'} />
              <Baris label="No. antrean" nilai={data.kode_antrean} />
              <Baris label="Status" nilai={STATUS_LABEL[data.status] ?? data.status} />
            </dl>

            <div className="flex gap-2">
              {data.status === 'menunggu' && sesi.can('pendaftaran.kunjungan', 'can_update') && (
                <button
                  type="button"
                  onClick={() => batal.mutate()}
                  disabled={batal.isPending}
                  className="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60"
                >
                  {batal.isPending ? 'Membatalkan...' : 'Batalkan kunjungan'}
                </button>
              )}
              <Link
                to="/pendaftaran"
                className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
              >
                Kembali
              </Link>
            </div>
          </>
        )}

        {!data && !kunjungan.isPending && (
          <Link to="/pendaftaran" className="text-sm font-medium text-teal-700 underline">
            Kembali ke daftar kunjungan
          </Link>
        )}
      </section>
    </div>
  )
}
