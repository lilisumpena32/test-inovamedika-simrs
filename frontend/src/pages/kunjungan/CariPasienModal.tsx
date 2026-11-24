import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { errorMessage } from '../../api/client'
import type { PasienRow } from '../../api/pasien'
import { listPasiens } from '../../api/pasien'
import { useDebounced } from '../../hooks/useDebounced'

export function CariPasienModal({
  onPilih,
  onTutup,
}: {
  onPilih: (pasien: PasienRow) => void
  onTutup: () => void
}) {
  const [cari, setCari] = useState('')
  const cariDebounced = useDebounced(cari)

  const pasiens = useQuery({
    queryKey: ['pasien-cari', cariDebounced],
    queryFn: () => listPasiens({ q: cariDebounced, is_active: 'true', page: 1 }),
    enabled: cariDebounced.trim().length > 0,
  })

  const daftarPasien = pasiens.data?.items ?? []

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-slate-950/60" aria-hidden="true" />

      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="judul-cari-pasien"
        className="relative max-h-[85vh] w-full max-w-2xl space-y-4 overflow-y-auto rounded-lg bg-white p-5 shadow-xl"
      >
        <h2 id="judul-cari-pasien" className="text-lg font-semibold">
          Cari Pasien
        </h2>

        <div>
          <label htmlFor="kata_cari_pasien" className="mb-1.5 block text-sm font-medium">
            No. RM, NIK, atau nama
          </label>
          <input
            id="kata_cari_pasien"
            type="search"
            value={cari}
            onChange={(e) => setCari(e.target.value)}
            className="input"
            autoFocus
          />
        </div>

        {cari.trim() === '' && (
          <p className="text-sm text-slate-500">Ketik no. RM, NIK, atau nama pasien.</p>
        )}

        {cari.trim() !== '' && (
          <div className="max-h-80 overflow-y-auto rounded-md border border-slate-200">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                  <th className="px-3 py-2 font-medium">No. RM</th>
                  <th className="px-3 py-2 font-medium">Nama</th>
                  <th className="px-3 py-2 font-medium">NIK</th>
                  <th className="px-3 py-2 font-medium">Alamat</th>
                </tr>
              </thead>
              <tbody>
                {pasiens.isFetching && (
                  <tr>
                    <td colSpan={4} className="px-3 py-4 text-center text-slate-500">
                      Mencari pasien...
                    </td>
                  </tr>
                )}

                {pasiens.isError && (
                  <tr>
                    <td colSpan={4} className="px-3 py-4 text-center text-red-600">
                      {errorMessage(pasiens.error)}
                    </td>
                  </tr>
                )}

                {pasiens.isSuccess && daftarPasien.length === 0 && (
                  <tr>
                    <td colSpan={4} className="px-3 py-4 text-center text-slate-500">
                      Pasien tidak ditemukan.
                    </td>
                  </tr>
                )}

                {daftarPasien.map((pasien) => (
                  <tr
                    key={pasien.id}
                    onClick={() => onPilih(pasien)}
                    className="cursor-pointer border-b border-slate-100 last:border-0 hover:bg-slate-50"
                  >
                    <td className="px-3 py-2 font-mono text-xs">{pasien.no_rm}</td>
                    <td className="px-3 py-2">{pasien.nama}</td>
                    <td className="px-3 py-2 font-mono text-xs text-slate-600">
                      {pasien.nik || '-'}
                    </td>
                    <td className="px-3 py-2 text-slate-600">{pasien.alamat || '-'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        <div className="flex gap-2 pt-2">
          <button
            type="button"
            onClick={onTutup}
            className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  )
}
