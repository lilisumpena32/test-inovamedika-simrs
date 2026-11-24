import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { errorMessage } from '../../api/client'
import {
  getPemeriksaan,
  isiPemeriksaan,
  type PemeriksaanInput,
  listLabKunjungan,
  listTindakanKunjungan,
  selesaiPemeriksaan,
  simpanPemeriksaan,
  tambahLab,
  tambahTindakan,
  hapusTindakanKunjungan,
} from '../../api/pemeriksaan'
import { getKunjungan } from '../../api/kunjungan'
import { listTindakanAktif } from '../../api/tindakan'
import { listJenisLabAktif } from '../../api/jenislab'
import { listObats } from '../../api/obat'
import { buatResep, getResepKunjungan, ubahResep, type ResepItemInput } from '../../api/resep'
import { useSession } from '../../auth/session'
import { useToast } from '../../hooks/useToast'

function formatRupiah(nilai: number): string {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(nilai)
}

export function PemeriksaanPage() {
  const { id = '' } = useParams()
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const sesi = useSession()
  const toast = useToast()

  const [form, setForm] = useState<PemeriksaanInput>(isiPemeriksaan(null))
  const [terisi, setTerisi] = useState(false)
  const [tindakanId, setTindakanId] = useState('')
  const [jenisLabId, setJenisLabId] = useState('')

  const kunjungan = useQuery({
    queryKey: ['kunjungan', id],
    queryFn: () => getKunjungan(id),
    enabled: id !== '',
    retry: false,
  })

  const pemeriksaan = useQuery({
    queryKey: ['pemeriksaan', id],
    queryFn: () => getPemeriksaan(id),
    enabled: id !== '',
  })

  if (pemeriksaan.isSuccess && !terisi) {
    setForm(isiPemeriksaan(pemeriksaan.data))
    setTerisi(true)
  }

  const data = kunjungan.data
  const bisaUbah = data !== undefined && sesi.can('pemeriksaan.daftar', 'can_update')
  const bisaTransaksi =
    data !== undefined &&
    bisaUbah &&
    ['menunggu', 'diperiksa', 'menunggu_obat'].includes(data.status)

  const simpan = useMutation({
    mutationFn: () => simpanPemeriksaan(id, form),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['kunjungan', id] })
      void queryClient.invalidateQueries({ queryKey: ['kunjungan-pemeriksaan'] })
      toast.success(result.message)
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const selesai = useMutation({
    mutationFn: () => selesaiPemeriksaan(id),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['kunjungan', id] })
      toast.success(result.message)
      navigate('/pemeriksaan')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  if (kunjungan.isPending || id === '') {
    return <p className="text-sm text-slate-500">Memuat...</p>
  }

  if (kunjungan.isError || data === undefined) {
    return (
      <div className="space-y-4">
        <div
          role="alert"
          className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          {errorMessage(kunjungan.error)}
        </div>
        <Link to="/pemeriksaan" className="text-sm font-medium text-teal-700 underline">
          Kembali ke daftar pemeriksaan
        </Link>
      </div>
    )
  }

  function ubah(kunci: keyof PemeriksaanInput, nilai: string) {
    setForm((sebelum) => ({ ...sebelum, [kunci]: nilai }))
  }

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-semibold">Pemeriksaan {data.kode_antrean}</h1>
        <Link to="/pemeriksaan" className="text-sm font-medium text-teal-700 underline">
          Kembali
        </Link>
      </header>

      <section className="card p-5 text-sm">
        <p>
          <span className="font-mono text-xs text-slate-500">{data.pasien?.no_rm}</span>{' '}
          <span className="font-medium">{data.pasien?.nama}</span>
        </p>
        <p className="text-slate-500">
          {data.poli?.name} · {data.dokter?.name} · {data.tgl_kunjungan} · Status{' '}
          {data.status}
        </p>
      </section>

      <FormEmr
        form={form}
        bisaUbah={bisaUbah}
        pending={simpan.isPending}
        onChange={ubah}
        onSubmit={() => simpan.mutate()}
      />

      <BagianTindakan kunjunganId={id} aktif={bisaTransaksi} tindakanId={tindakanId} setTindakanId={setTindakanId} />

      <BagianLab kunjunganId={id} aktif={bisaTransaksi} jenisLabId={jenisLabId} setJenisLabId={setJenisLabId} />

      <BagianResep kunjunganId={id} aktif={bisaTransaksi} />

      {data.status === 'diperiksa' && bisaUbah && (
        <button
          type="button"
          onClick={() => selesai.mutate()}
          disabled={selesai.isPending}
          className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
        >
          {selesai.isPending ? 'Menyimpan...' : 'Selesai tanpa resep'}
        </button>
      )}
    </div>
  )
}

function FormEmr({
  form,
  bisaUbah,
  pending,
  onChange,
  onSubmit,
}: {
  form: PemeriksaanInput
  bisaUbah: boolean
  pending: boolean
  onChange: (kunci: keyof PemeriksaanInput, nilai: string) => void
  onSubmit: () => void
}) {
  const galat = form.diagnosis_utama.trim() === ''

  return (
    <section className="card max-w-3xl space-y-4 p-5">
      <h2 className="font-semibold">Rekam medis elektronik</h2>

      <div className="grid gap-3 sm:grid-cols-2">
        <div className="sm:col-span-2">
          <label htmlFor="keluhan" className="mb-1.5 block text-sm font-medium">
            Keluhan
          </label>
          <textarea
            id="keluhan"
            rows={2}
            disabled={!bisaUbah}
            value={form.keluhan}
            onChange={(e) => onChange('keluhan', e.target.value)}
            className="input"
          />
        </div>
        <div className="sm:col-span-2">
          <label htmlFor="anamnesis" className="mb-1.5 block text-sm font-medium">
            Anamnesis
          </label>
          <textarea
            id="anamnesis"
            rows={2}
            disabled={!bisaUbah}
            value={form.anamnesis}
            onChange={(e) => onChange('anamnesis', e.target.value)}
            className="input"
          />
        </div>
        <div>
          <label htmlFor="tensi" className="mb-1.5 block text-sm font-medium">
            Tensi
          </label>
          <input
            id="tensi"
            type="text"
            placeholder="120/80"
            disabled={!bisaUbah}
            value={form.tensi}
            onChange={(e) => onChange('tensi', e.target.value)}
            className="input"
          />
        </div>
        <div>
          <label htmlFor="suhu" className="mb-1.5 block text-sm font-medium">
            Suhu (°C)
          </label>
          <input
            id="suhu"
            type="number"
            step="0.1"
            disabled={!bisaUbah}
            value={form.suhu}
            onChange={(e) => onChange('suhu', e.target.value)}
            className="input"
          />
        </div>
        <div>
          <label htmlFor="nadi" className="mb-1.5 block text-sm font-medium">
            Nadi
          </label>
          <input
            id="nadi"
            type="number"
            disabled={!bisaUbah}
            value={form.nadi}
            onChange={(e) => onChange('nadi', e.target.value)}
            className="input"
          />
        </div>
        <div>
          <label htmlFor="pernapasan" className="mb-1.5 block text-sm font-medium">
            Pernapasan
          </label>
          <input
            id="pernapasan"
            type="number"
            disabled={!bisaUbah}
            value={form.pernapasan}
            onChange={(e) => onChange('pernapasan', e.target.value)}
            className="input"
          />
        </div>
        <div>
          <label htmlFor="tinggi_cm" className="mb-1.5 block text-sm font-medium">
            Tinggi (cm)
          </label>
          <input
            id="tinggi_cm"
            type="number"
            disabled={!bisaUbah}
            value={form.tinggi_cm}
            onChange={(e) => onChange('tinggi_cm', e.target.value)}
            className="input"
          />
        </div>
        <div>
          <label htmlFor="berat_kg" className="mb-1.5 block text-sm font-medium">
            Berat (kg)
          </label>
          <input
            id="berat_kg"
            type="number"
            disabled={!bisaUbah}
            value={form.berat_kg}
            onChange={(e) => onChange('berat_kg', e.target.value)}
            className="input"
          />
        </div>
        <div className="sm:col-span-2">
          <label htmlFor="hasil_fisik" className="mb-1.5 block text-sm font-medium">
            Hasil pemeriksaan fisik
          </label>
          <textarea
            id="hasil_fisik"
            rows={2}
            disabled={!bisaUbah}
            value={form.hasil_fisik}
            onChange={(e) => onChange('hasil_fisik', e.target.value)}
            className="input"
          />
        </div>
        <div>
          <label htmlFor="diagnosis_utama" className="mb-1.5 block text-sm font-medium">
            Diagnosis utama
          </label>
          <input
            id="diagnosis_utama"
            type="text"
            disabled={!bisaUbah}
            value={form.diagnosis_utama}
            onChange={(e) => onChange('diagnosis_utama', e.target.value)}
            className="input"
            required
          />
          {galat && <p className="mt-1 text-xs text-red-600">Diagnosis utama wajib diisi</p>}
        </div>
        <div>
          <label htmlFor="diagnosis_sekunder" className="mb-1.5 block text-sm font-medium">
            Diagnosis sekunder
          </label>
          <input
            id="diagnosis_sekunder"
            type="text"
            disabled={!bisaUbah}
            value={form.diagnosis_sekunder}
            onChange={(e) => onChange('diagnosis_sekunder', e.target.value)}
            className="input"
          />
        </div>
      </div>

      {bisaUbah && (
        <div className="flex gap-2">
          <button
            type="button"
            onClick={onSubmit}
            disabled={pending || form.diagnosis_utama.trim() === ''}
            className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
          >
            {pending ? 'Menyimpan...' : 'Simpan pemeriksaan'}
          </button>
        </div>
      )}
    </section>
  )
}

function BagianTindakan({
  kunjunganId,
  aktif,
  tindakanId,
  setTindakanId,
}: {
  kunjunganId: string
  aktif: boolean
  tindakanId: string
  setTindakanId: (nilai: string) => void
}) {
  const queryClient = useQueryClient()
  const toast = useToast()

  const tindakans = useQuery({
    queryKey: ['tindakan-aktif'],
    queryFn: listTindakanAktif,
  })

  const baris = useQuery({
    queryKey: ['tindakan-kunjungan', kunjunganId],
    queryFn: () => listTindakanKunjungan(kunjunganId),
  })

  const tambah = useMutation({
    mutationFn: () => tambahTindakan(kunjunganId, tindakanId),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['tindakan-kunjungan', kunjunganId] })
      toast.success(result.message)
      setTindakanId('')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const hapus = useMutation({
    mutationFn: (detailId: string) => hapusTindakanKunjungan(kunjunganId, detailId),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['tindakan-kunjungan', kunjunganId] })
      toast.success(result.message)
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const list = baris.data ?? []

  return (
    <section className="card max-w-3xl space-y-3 p-5">
      <h2 className="font-semibold">Tindakan</h2>

      {aktif && (
        <div className="flex gap-2">
          <select
            value={tindakanId}
            onChange={(e) => setTindakanId(e.target.value)}
            className="input max-w-sm"
            aria-label="Pilih tindakan"
          >
            <option value="">Pilih tindakan</option>
            {(tindakans.data ?? []).map((t) => (
              <option key={t.id} value={t.id}>
                {t.nama} — {formatRupiah(t.tarif)}
              </option>
            ))}
          </select>
          <button
            type="button"
            onClick={() => tindakanId !== '' && tambah.mutate()}
            disabled={tindakanId === '' || tambah.isPending}
            className="rounded-md border border-teal-300 px-3 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50 disabled:opacity-50"
          >
            Tambah
          </button>
        </div>
      )}

      {list.length === 0 && <p className="text-sm text-slate-500">Belum ada tindakan.</p>}

      {list.length > 0 && (
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="py-2 font-medium">Tindakan</th>
              <th className="py-2 font-medium">Tarif</th>
              {aktif && <th className="py-2">
                <span className="sr-only">Aksi</span>
              </th>}
            </tr>
          </thead>
          <tbody>
            {list.map((detail) => (
              <tr key={detail.id} className="border-b border-slate-100 last:border-0">
                <td className="py-2">{detail.tindakan?.nama ?? '-'}</td>
                <td className="py-2 tabular-nums">{formatRupiah(detail.tarif)}</td>
                {aktif && (
                  <td className="py-2 text-right">
                    <button
                      type="button"
                      onClick={() => hapus.mutate(detail.id)}
                      className="text-xs font-medium text-red-600 hover:underline"
                    >
                      Batalkan
                    </button>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </section>
  )
}

function BagianLab({
  kunjunganId,
  aktif,
  jenisLabId,
  setJenisLabId,
}: {
  kunjunganId: string
  aktif: boolean
  jenisLabId: string
  setJenisLabId: (nilai: string) => void
}) {
  const queryClient = useQueryClient()
  const toast = useToast()

  const jenisLabs = useQuery({
    queryKey: ['jenis-lab-aktif'],
    queryFn: listJenisLabAktif,
  })

  const baris = useQuery({
    queryKey: ['lab-kunjungan', kunjunganId],
    queryFn: () => listLabKunjungan(kunjunganId),
  })

  const tambah = useMutation({
    mutationFn: () => tambahLab(kunjunganId, jenisLabId),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['lab-kunjungan', kunjunganId] })
      toast.success(result.message)
      setJenisLabId('')
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const list = baris.data ?? []

  return (
    <section className="card max-w-3xl space-y-3 p-5">
      <h2 className="font-semibold">Pemeriksaan penunjang (lab)</h2>

      {aktif && (
        <div className="flex gap-2">
          <select
            value={jenisLabId}
            onChange={(e) => setJenisLabId(e.target.value)}
            className="input max-w-sm"
            aria-label="Pilih jenis lab"
          >
            <option value="">Pilih jenis lab</option>
            {(jenisLabs.data ?? []).map((j) => (
              <option key={j.id} value={j.id}>
                {j.nama} — {formatRupiah(j.tarif)}
              </option>
            ))}
          </select>
          <button
            type="button"
            onClick={() => jenisLabId !== '' && tambah.mutate()}
            disabled={jenisLabId === '' || tambah.isPending}
            className="rounded-md border border-teal-300 px-3 py-2 text-sm font-medium text-teal-700 hover:bg-teal-50 disabled:opacity-50"
          >
            Minta
          </button>
        </div>
      )}

      {list.length === 0 && <p className="text-sm text-slate-500">Belum ada permintaan lab.</p>}

      {list.length > 0 && (
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="py-2 font-medium">Jenis</th>
              <th className="py-2 font-medium">Tarif</th>
              <th className="py-2 font-medium">Status</th>
              <th className="py-2 font-medium">Hasil</th>
            </tr>
          </thead>
          <tbody>
            {list.map((order) => (
              <tr key={order.id} className="border-b border-slate-100 last:border-0 align-top">
                <td className="py-2">{order.jenis_lab?.nama ?? '-'}</td>
                <td className="py-2 tabular-nums">{formatRupiah(order.tarif)}</td>
                <td className="py-2">{order.status}</td>
                <td className="py-2 text-slate-600">{order.hasil ?? '-'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </section>
  )
}

function BagianResep({ kunjunganId, aktif }: { kunjunganId: string; aktif: boolean }) {
  const queryClient = useQueryClient()
  const toast = useToast()

  const obats = useQuery({
    queryKey: ['obat-pilihan'],
    queryFn: () => listObats({ q: '', is_active: 'true', page: 1 }),
    select: (data) => data.items,
  })

  const resep = useQuery({
    queryKey: ['resep', kunjunganId],
    queryFn: () => getResepKunjungan(kunjunganId),
  })

  const [items, setItems] = useState<ResepItemInput[]>([{ obat_id: '', dosis: '', frekuensi: '', jumlah: 1 }])

  const simpan = useMutation({
    mutationFn: () => {
      const bersih = items.filter((i) => i.obat_id !== '' && i.jumlah > 0)

      return resep.data === null
        ? buatResep(kunjunganId, bersih)
        : ubahResep(kunjunganId, bersih)
    },
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['resep', kunjunganId] })
      void queryClient.invalidateQueries({ queryKey: ['kunjungan', kunjunganId] })
      void queryClient.invalidateQueries({ queryKey: ['kunjungan-pemeriksaan'] })
      toast.success(result.message)
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const list = resep.data?.items ?? []
  const sudahSerah = resep.data?.status === 'selesai'

  return (
    <section className="card max-w-3xl space-y-3 p-5">
      <h2 className="font-semibold">Resep</h2>

      {(resep.data === null || resep.data === undefined || resep.data.status === 'menunggu') && aktif && !sudahSerah && (
        <>
          <div className="space-y-2">
            {items.map((item, index) => (
              <div key={index} className="grid gap-2 sm:grid-cols-[1fr_8rem_8rem_4rem_auto]">
                <div>
                  <label htmlFor={`resep-${index}-obat`} className="sr-only">
                    Obat
                  </label>
                  <select
                    id={`resep-${index}-obat`}
                    value={item.obat_id}
                    onChange={(e) => {
                      const nilai = e.target.value
                      setItems((sebelum) =>
                        sebelum.map((i, pos) => (pos === index ? { ...i, obat_id: nilai } : i)),
                      )
                    }}
                    className="input"
                  >
                    <option value="">Pilih obat</option>
                    {(obats.data ?? []).map((obat) => (
                      <option key={obat.id} value={obat.id}>
                        {obat.nama_obat} ({obat.satuan?.name ?? '-'}, stok {obat.stok})
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label htmlFor={`resep-${index}-dosis`} className="sr-only">
                    Dosis
                  </label>
                  <input
                    id={`resep-${index}-dosis`}
                    type="text"
                    placeholder="Dosis"
                    value={item.dosis}
                    onChange={(e) => {
                      const nilai = e.target.value
                      setItems((sebelum) =>
                        sebelum.map((i, pos) => (pos === index ? { ...i, dosis: nilai } : i)),
                      )
                    }}
                    className="input"
                  />
                </div>
                <div>
                  <label htmlFor={`resep-${index}-frek`} className="sr-only">
                    Frekuensi
                  </label>
                  <input
                    id={`resep-${index}-frek`}
                    type="text"
                    placeholder="Frekuensi"
                    value={item.frekuensi}
                    onChange={(e) => {
                      const nilai = e.target.value
                      setItems((sebelum) =>
                        sebelum.map((i, pos) => (pos === index ? { ...i, frekuensi: nilai } : i)),
                      )
                    }}
                    className="input"
                  />
                </div>
                <div>
                  <label htmlFor={`resep-${index}-jumlah`} className="sr-only">
                    Jumlah
                  </label>
                  <input
                    id={`resep-${index}-jumlah`}
                    type="number"
                    min={1}
                    value={item.jumlah}
                    onChange={(e) => {
                      const nilai = Number(e.target.value)
                      setItems((sebelum) =>
                        sebelum.map((i, pos) => (pos === index ? { ...i, jumlah: nilai } : i)),
                      )
                    }}
                    className="input"
                  />
                </div>
                <div className="flex items-end">
                  <button
                    type="button"
                    onClick={() => setItems((sebelum) => sebelum.filter((_, pos) => pos !== index))}
                    disabled={items.length === 1}
                    aria-label={`Hapus item ${index + 1}`}
                    className="rounded border border-red-200 px-2 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 disabled:opacity-40"
                  >
                    Hapus
                  </button>
                </div>
              </div>
            ))}
          </div>

          <div className="flex gap-2">
            <button
              type="button"
              onClick={() =>
                setItems((sebelum) => [...sebelum, { obat_id: '', dosis: '', frekuensi: '', jumlah: 1 }])
              }
              className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
              + Tambah item
            </button>
            <button
              type="button"
              onClick={() => simpan.mutate()}
              disabled={simpan.isPending || items.every((i) => i.obat_id === '')}
              className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
            >
              {resep.data === null ? 'Simpan resep' : 'Ubah resep'}
            </button>
          </div>
        </>
      )}

      {list.length > 0 && (
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="py-2 font-medium">Obat</th>
              <th className="py-2 font-medium">Dosis</th>
              <th className="py-2 font-medium">Frekuensi</th>
              <th className="py-2 font-medium">Jumlah</th>
            </tr>
          </thead>
          <tbody>
            {list.map((item) => (
              <tr key={item.id} className="border-b border-slate-100 last:border-0">
                <td className="py-2">{item.obat?.nama ?? '-'}</td>
                <td className="py-2">{item.dosis ?? '-'}</td>
                <td className="py-2">{item.frekuensi ?? '-'}</td>
                <td className="py-2 tabular-nums">{item.jumlah}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}

      {sudahSerah && <p className="text-sm text-emerald-700">Obat sudah diserahkan farmasi.</p>}
      {!sudahSerah && !aktif && resep.data !== null && (
        <p className="text-sm text-slate-500">Resep menunggu penyerahan farmasi.</p>
      )}
    </section>
  )
}
