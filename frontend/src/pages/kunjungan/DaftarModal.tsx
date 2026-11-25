import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { errorMessage } from '../../api/client'
import { createKunjungan, listJadwalTersedia } from '../../api/kunjungan'
import type { PoliRow } from '../../api/poli'
import type { PasienRow } from '../../api/pasien'
import { useToast } from '../../hooks/useToast'
import { tanggalHariIni } from '../../lib/tanggal'
import { CariPasienModal } from './CariPasienModal'

function kunciIdempotensi(): string {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
    return crypto.randomUUID()
  }

  return `${Date.now()}-${Math.random().toString(16).slice(2)}`
}

function jam(nilai: string): string {
  return nilai.slice(0, 5)
}

export function DaftarModal({ poli, onTutup }: { poli: PoliRow; onTutup: () => void }) {
  const queryClient = useQueryClient()
  const toast = useToast()

  const [tanggal, setTanggal] = useState(tanggalHariIni())
  const [pasien, setPasien] = useState<PasienRow | null>(null)
  const [jadwalId, setJadwalId] = useState('')
  const [kunci] = useState(kunciIdempotensi)
  const [cariTerbuka, setCariTerbuka] = useState(false)

  const jadwals = useQuery({
    queryKey: ['jadwal-tersedia', { poliId: poli.id, tanggal }],
    queryFn: () => listJadwalTersedia(poli.id, tanggal),
    enabled: tanggal !== '',
  })

  const mutation = useMutation({
    mutationFn: () =>
      createKunjungan({
        idempotency_key: kunci,
        pasien_id: pasien === null ? '' : pasien.id,
        jadwal_id: jadwalId,
        tgl_kunjungan: tanggal,
      }),
    onSuccess: (result) => {
      void queryClient.invalidateQueries({ queryKey: ['kunjungans'] })
      void queryClient.invalidateQueries({ queryKey: ['antreans'] })
      toast.success(result.message)
      onTutup()
    },
    onError: (error) => toast.error(errorMessage(error)),
  })

  const daftarJadwal = jadwals.data?.items ?? []
  const siapSimpan = pasien !== null && jadwalId !== ''

  return (
    <div className="fixed inset-0 z-40 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-slate-950/50" aria-hidden="true" />

      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="judul-daftar"
        className="relative max-h-[90dvh] w-full max-w-md space-y-4 overflow-y-auto rounded-t-xl bg-white p-4 shadow-xl sm:rounded-lg sm:p-5"
      >
        <h2 id="judul-daftar" className="text-lg font-semibold">
          Daftar Pasien
        </h2>
        <p className="-mt-2 text-sm text-slate-500">{poli.nama}</p>

        <div>
          <label htmlFor="tgl_kunjungan" className="mb-1.5 block text-sm font-medium">
            Tanggal kunjungan
          </label>
          <input
            id="tgl_kunjungan"
            type="date"
            value={tanggal}
            onChange={(e) => {
              setTanggal(e.target.value)
              setJadwalId('')
            }}
            className="input"
            required
          />
        </div>

        <div>
          <label htmlFor="pasien_terpilih" className="mb-1.5 block text-sm font-medium">
            Pasien
          </label>
          <div className="flex gap-2">
            <input
              id="pasien_terpilih"
              type="text"
              readOnly
              value={pasien === null ? '' : `${pasien.no_rm} — ${pasien.nama}`}
              placeholder="Belum ada pasien dipilih"
              aria-label="Pasien terpilih"
              className="input bg-slate-50 text-slate-600"
            />
            <button
              type="button"
              onClick={() => setCariTerbuka(true)}
              className="whitespace-nowrap rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
              Cari
            </button>
          </div>
        </div>

        <fieldset>
          <legend className="mb-1.5 block text-sm font-medium">Jadwal praktek</legend>

          {jadwals.isPending && <p className="text-sm text-slate-500">Memuat jadwal...</p>}

          {jadwals.isError && (
            <p className="text-sm text-red-600">{errorMessage(jadwals.error)}</p>
          )}

          {jadwals.isSuccess && daftarJadwal.length === 0 && (
            <p className="text-sm text-slate-500">Tidak ada jadwal praktek pada tanggal ini.</p>
          )}

          {daftarJadwal.length > 0 && (
            <div className="space-y-2">
              {daftarJadwal.map((jadwal) => (
                <label
                  key={jadwal.id}
                  className={[
                    'flex cursor-pointer items-start gap-3 rounded-md border px-3 py-2 text-sm',
                    jadwalId === jadwal.id
                      ? 'border-teal-500 bg-teal-50/60'
                      : 'border-slate-200 hover:bg-slate-50',
                    jadwal.sisa === 0 ? 'cursor-not-allowed opacity-60' : '',
                  ].join(' ')}
                >
                  <input
                    type="radio"
                    name="jadwal_id"
                    value={jadwal.id}
                    checked={jadwalId === jadwal.id}
                    onChange={() => setJadwalId(jadwal.id)}
                    disabled={jadwal.sisa === 0}
                    className="mt-1"
                  />
                  <span>
                    <span className="font-medium">{jadwal.dokter?.name ?? '-'}</span>
                    <span className="ml-2 tabular-nums text-slate-600">
                      {jam(jadwal.jam_mulai)}–{jam(jadwal.jam_selesai)}
                    </span>
                    <span className="ml-2 text-slate-500">
                      Sisa {jadwal.sisa} dari {jadwal.kuota}
                    </span>
                  </span>
                </label>
              ))}
            </div>
          )}
        </fieldset>

        <div className="flex gap-2 pt-2">
          <button
            type="button"
            onClick={() => {
              if (siapSimpan) mutation.mutate()
            }}
            disabled={!siapSimpan || mutation.isPending}
            className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
          >
            {mutation.isPending ? 'Menyimpan...' : 'Simpan'}
          </button>
          <button
            type="button"
            onClick={onTutup}
            className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Batal
          </button>
        </div>
      </div>

      {cariTerbuka && (
        <CariPasienModal
          onPilih={(terpilih) => {
            setPasien(terpilih)
            setCariTerbuka(false)
          }}
          onTutup={() => setCariTerbuka(false)}
        />
      )}
    </div>
  )
}
