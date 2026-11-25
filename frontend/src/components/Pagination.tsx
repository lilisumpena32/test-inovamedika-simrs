/**
 * Footer pagination standar untuk semua halaman daftar.
 * - Teks "Halaman x dari y"
 * - Tombol Sebelumnya / Berikutnya
 * - Stack vertikal di mobile, horizontal di sm+
 * - Target sentuh minimal 44px di mobile (min-h-11)
 */
export function Pagination({
  halaman,
  totalHalaman,
  onSebelumnya,
  onBerikutnya,
}: {
  halaman: number
  totalHalaman: number
  onSebelumnya: () => void
  onBerikutnya: () => void
}) {
  const adaHalaman = totalHalaman > 0

  return (
    <div
      className="border-t border-slate-200 px-4 py-3 text-sm text-slate-500 sm:flex sm:items-center sm:justify-between"
      role="navigation"
      aria-label="Navigasi halaman"
    >
      <p className="mb-2 text-center sm:mb-0 sm:text-left">
        {adaHalaman ? `Halaman ${halaman} dari ${totalHalaman}` : ''}
      </p>
      <div className="flex justify-center gap-2 sm:justify-end">
        <button
          type="button"
          disabled={!adaHalaman || halaman <= 1}
          onClick={onSebelumnya}
          aria-label="Halaman sebelumnya"
          className="min-h-11 rounded border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40 sm:min-h-0 sm:text-sm"
        >
          Sebelumnya
        </button>
        <button
          type="button"
          disabled={!adaHalaman || halaman >= totalHalaman}
          onClick={onBerikutnya}
          aria-label="Halaman berikutnya"
          className="min-h-11 rounded border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-40 sm:min-h-0 sm:text-sm"
        >
          Berikutnya
        </button>
      </div>
    </div>
  )
}
