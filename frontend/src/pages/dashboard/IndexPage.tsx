import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { client, errorMessage } from '../../api/client'
import { listKunjungans } from '../../api/kunjungan'
import { listOrderLab } from '../../api/pemeriksaan'
import { listResep } from '../../api/resep'
import { listTagihan } from '../../api/tagihan'
import type { ApiResponse } from '../../api/types'
import { useSession } from '../../auth/session'
import { tanggalHariIni } from '../../lib/tanggal'

function formatRupiah(nilai: number): string {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(nilai)
}

const TANGGAL_PANJANG = new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(new Date())

function awalBulan(): string {
  const sekarang = new Date()

  return `${sekarang.getFullYear()}-${String(sekarang.getMonth() + 1).padStart(2, '0')}-01`
}

type Ringkasan = {
  kunjungan_total: number
  tindakan_total: number
}

type Pendapatan = {
  total: number
}

async function getRingkasan(dari: string, sampai: string): Promise<Ringkasan> {
  const response = await client.get<ApiResponse<Ringkasan>>('/dashboard/ringkasan', {
    params: { dari, sampai },
  })
  return response.data.data
}

async function getPendapatan(dari: string, sampai: string): Promise<Pendapatan> {
  const response = await client.get<ApiResponse<Pendapatan>>('/dashboard/pendapatan', {
    params: { dari, sampai },
  })
  return response.data.data
}

function Kartu({
  label,
  nilai,
  keterangan,
  ke,
}: {
  label: string
  nilai: string
  keterangan?: string
  ke?: string
}) {
  const isi = (
    <>
      <p className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</p>
      <p className="mt-1 text-2xl font-semibold tabular-nums">{nilai}</p>
      {keterangan !== undefined && <p className="mt-1 text-xs text-slate-500">{keterangan}</p>}
    </>
  )

  if (ke === undefined) {
    return <div className="card p-4">{isi}</div>
  }

  return (
    <Link to={ke} className="card block p-4 transition hover:border-teal-400 hover:bg-teal-50/40">
      {isi}
    </Link>
  )
}

export function IndexPage() {
  const sesi = useSession()
  const { user } = sesi
  const today = tanggalHariIni()

  const bisaAntrean =
    sesi.can('pendaftaran.kunjungan', 'can_view') ||
    sesi.can('pemeriksaan.daftar', 'can_view')

  const bisaFarmasi = sesi.can('farmasi.resep', 'can_view')
  const bisaLab = sesi.can('laboratorium.order', 'can_view')
  const bisaKasir = sesi.can('kasir.tagihan', 'can_view')
  const bisaLaporan = sesi.can('laporan.operasional', 'can_view')

  const menunggu = useQuery({
    queryKey: ['dash-menunggu', today],
    queryFn: () => listKunjungans({ tanggal: today, poli_id: '', status: 'menunggu', page: 1 }),
    enabled: bisaAntrean,
  })

  const diperiksa = useQuery({
    queryKey: ['dash-diperiksa', today],
    queryFn: () => listKunjungans({ tanggal: today, poli_id: '', status: 'diperiksa', page: 1 }),
    enabled: bisaAntrean,
  })

  const resep = useQuery({
    queryKey: ['dash-resep', today],
    queryFn: () => listResep({ tanggal: today, status: 'menunggu' }),
    enabled: bisaFarmasi,
  })

  const lab = useQuery({
    queryKey: ['dash-lab', today],
    queryFn: () => listOrderLab({ tanggal: today, status: 'diminta' }),
    enabled: bisaLab,
  })

  const tagihan = useQuery({
    queryKey: ['dash-tagihan'],
    queryFn: () => listTagihan({ tanggal: '', status: 'belum' }),
    enabled: bisaKasir,
  })

  const ringkasan = useQuery({
    queryKey: ['dash-ringkasan', awalBulan(), today],
    queryFn: () => getRingkasan(awalBulan(), today),
    enabled: bisaLaporan,
  })

  const pendapatan = useQuery({
    queryKey: ['dash-pendapatan', awalBulan(), today],
    queryFn: () => getPendapatan(awalBulan(), today),
    enabled: bisaLaporan,
  })

  const daftarTagihan = tagihan.data?.items ?? []
  const sisaTagihan = daftarTagihan.reduce((jumlah, t) => jumlah + t.sisa, 0)

  const galat = [
    menunggu,
    diperiksa,
    resep,
    lab,
    tagihan,
    ringkasan,
    pendapatan,
  ].find((q) => q.isError)

  const adaKartu = bisaAntrean || bisaFarmasi || bisaLab || bisaKasir || bisaLaporan

  return (
    <div className="space-y-6">
      <header>
        <p className="text-sm text-slate-500">{TANGGAL_PANJANG}</p>
        <h1 className="mt-1 text-xl font-semibold sm:text-2xl">
          Selamat datang, {user?.full_name}
        </h1>
      </header>

      {galat !== undefined && (
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {errorMessage(galat.error)}
        </div>
      )}

      {adaKartu && (
        <section aria-label="Ringkasan hari ini" className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {bisaAntrean && (
            <>
              <Kartu
                label="Menunggu"
                nilai={menunggu.data ? String(menunggu.data.total) : '—'}
                keterangan="Kunjungan hari ini"
                ke="/antrean"
              />
              <Kartu
                label="Sedang diperiksa"
                nilai={diperiksa.data ? String(diperiksa.data.total) : '—'}
                keterangan="Kunjungan hari ini"
                ke="/pemeriksaan"
              />
            </>
          )}

          {bisaFarmasi && (
            <Kartu
              label="Resep menunggu"
              nilai={resep.data ? String(resep.data.items.length) : '—'}
              keterangan="Belum diserahkan"
              ke="/farmasi/resep"
            />
          )}

          {bisaLab && (
            <Kartu
              label="Order lab diminta"
              nilai={lab.data ? String(lab.data.items.length) : '—'}
              keterangan="Belum ada hasil"
              ke="/laboratorium"
            />
          )}

          {bisaKasir && (
            <Kartu
              label="Tagihan belum lunas"
              nilai={tagihan.data ? String(daftarTagihan.length) : '—'}
              keterangan={tagihan.data ? `Sisa ${formatRupiah(sisaTagihan)}` : undefined}
              ke="/kasir"
            />
          )}

          {bisaLaporan && (
            <>
              <Kartu
                label="Kunjungan bulan ini"
                nilai={ringkasan.data ? String(ringkasan.data.kunjungan_total) : '—'}
                keterangan="Tidak termasuk batal"
                ke="/laporan"
              />
              <Kartu
                label="Pendapatan bulan ini"
                nilai={pendapatan.data ? formatRupiah(pendapatan.data.total) : '—'}
                keterangan="Pembayaran tercatat"
                ke="/laporan"
              />
            </>
          )}
        </section>
      )}

      {!adaKartu && (
        <p className="text-sm text-slate-500">
          Gunakan menu di samping untuk mulai bekerja.
        </p>
      )}
    </div>
  )
}
