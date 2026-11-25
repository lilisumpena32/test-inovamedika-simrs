import { useEffect, useId, useMemo, useRef, useState } from 'react'
import { ChevronDown, Check } from 'lucide-react'
import { usePanelPosition } from './usePanelPosition'

export type SearchableOption = { label: string; value: string }

export type SearchableSelectProps = {
  /** Nilai yang sedang dipilih. */
  value: string
  onChange: (nilai: string) => void
  options: SearchableOption[]
  /** Teks saat belum ada pilihan. */
  placeholder?: string
  ariaLabel?: string
  /** id untuk <label htmlFor>. */
  id?: string
  /** Kelas tambahan pada input. */
  className?: string
  /** Tampilkan gaya galat (border merah). */
  invalid?: boolean
  disabled?: boolean
}

/**
 * Combobox searchable tanpa dependency baru.
 * - Pola ARIA combobox (input + listbox) sehingga <label htmlFor> tetap bekerja.
 * - Keyboard: ArrowDown/ArrowUp/Home/End/Enter/Esc/Tab.
 * - Filter case-insensitive di klien.
 * - Panel lewat portal + position: fixed agar tidak terpotong
 *   overflow-x-auto pada tabel atau modal.
 */
export function SearchableSelect({
  value,
  onChange,
  options,
  placeholder = 'Pilih',
  ariaLabel,
  id,
  className,
  invalid = false,
  disabled = false,
}: SearchableSelectProps) {
  const autoId = useId()
  const listId = `${id ?? autoId}-listbox`
  const inputId = id

  const [terbuka, setTerbuka] = useState(false)
  const [teks, setTeks] = useState('')
  const [terfokus, setTerfokus] = useState(-1)

  const triggerRef = useRef<HTMLDivElement>(null)
  const panelRef = useRef<HTMLDivElement>(null)
  const inputRef = useRef<HTMLInputElement>(null)

  const { top, left, width } = usePanelPosition(triggerRef, panelRef, terbuka)

  const labelTerpilih = useMemo(
    () => options.find((o) => o.value === value)?.label ?? '',
    [options, value],
  )

  const opsiTampil = useMemo(() => {
    const q = teks.trim().toLowerCase()
    if (q === '') return options
    return options.filter((o) => o.label.toLowerCase().includes(q))
  }, [options, teks])

  // Klik di luar menutup panel
  useEffect(() => {
    if (!terbuka) return
    const handleClick = (e: MouseEvent) => {
      const target = e.target as Node
      if (
        !triggerRef.current?.contains(target) &&
        !panelRef.current?.contains(target)
      ) {
        tutup()
      }
    }
    document.addEventListener('mousedown', handleClick)
    return () => document.removeEventListener('mousedown', handleClick)
  })

  // Scroll opsi aktif agar terlihat
  useEffect(() => {
    if (!terbuka || terfokus < 0) return
    const aktif = opsiTampil[terfokus]
    if (!aktif) return
    document
      .getElementById(`${listId}-${terfokus}`)
      ?.scrollIntoView({ block: 'nearest' })
  }, [terbuka, terfokus, opsiTampil, listId])

  function buka() {
    if (disabled) return
    setTerbuka(true)
    setTeks('')
    setTerfokus(Math.max(0, options.findIndex((o) => o.value === value)))
  }

  function tutup() {
    setTerbuka(false)
    setTeks('')
    setTerfokus(-1)
  }

  function pilih(nilai: string) {
    onChange(nilai)
    tutup()
    inputRef.current?.focus()
  }

  function tekan(e: React.KeyboardEvent) {
    if (e.key === 'ArrowDown') {
      e.preventDefault()
      if (!terbuka) {
        buka()
        return
      }
      setTerfokus((s) => Math.min(s + 1, opsiTampil.length - 1))
    } else if (e.key === 'ArrowUp') {
      e.preventDefault()
      if (!terbuka) {
        buka()
        return
      }
      setTerfokus((s) => Math.max(s - 1, 0))
    } else if (e.key === 'Home' && terbuka) {
      e.preventDefault()
      setTerfokus(0)
    } else if (e.key === 'End' && terbuka) {
      e.preventDefault()
      setTerfokus(opsiTampil.length - 1)
    } else if (e.key === 'Enter') {
      if (!terbuka) return
      e.preventDefault()
      const opsi = opsiTampil[terfokus]
      if (opsi) pilih(opsi.value)
    } else if (e.key === 'Escape') {
      if (!terbuka) return
      e.preventDefault()
      tutup()
    } else if (e.key === 'Tab') {
      if (terbuka) tutup()
    }
  }

  const teksTampil = terbuka ? teks : labelTerpilih

  return (
    <div ref={triggerRef} className="relative w-full">
      <input
        ref={inputRef}
        id={inputId}
        type="text"
        role="combobox"
        autoComplete="off"
        aria-expanded={terbuka}
        aria-controls={terbuka ? listId : undefined}
        aria-autocomplete="list"
        aria-activedescendant={
          terbuka && terfokus >= 0 ? `${listId}-${terfokus}` : undefined
        }
        aria-label={ariaLabel}
        aria-haspopup="listbox"
        aria-invalid={invalid}
        disabled={disabled}
        value={teksTampil}
        placeholder={placeholder}
        onClick={() => (terbuka ? tutup() : buka())}
        onKeyDown={tekan}
        onChange={(e) => {
          if (!terbuka) setTerbuka(true)
          setTeks(e.target.value)
          setTerfokus(0)
        }}
        onBlur={(e) => {
          // Tutup hanya bila fokus keluar dari wrapper (bukan ke panel)
          if (!triggerRef.current?.contains(e.relatedTarget as Node | null)) {
            // beri kesempatan klik opsi di panel (panel berada di luar wrapper)
            if (!panelRef.current?.contains(e.relatedTarget as Node | null)) {
              tutup()
            }
          }
        }}
        className={[
          'w-full rounded-md border bg-white px-3 py-2 pr-9 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 disabled:pointer-events-none disabled:opacity-50',
          invalid
            ? 'border-red-500 focus:border-red-500 focus:ring-2 focus:ring-red-500/20'
            : 'border-slate-300 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20',
          className ?? '',
        ].join(' ')}
      />
      <ChevronDown
        aria-hidden="true"
        className={`pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-slate-400 transition-transform ${
          terbuka ? 'rotate-180' : ''
        }`}
      />

      {terbuka && (
        <div
          ref={panelRef}
          role="listbox"
          id={listId}
          aria-label={ariaLabel}
          className="fixed z-50 max-h-64 overflow-y-auto rounded-md border border-slate-200 bg-white py-1 shadow-lg"
          style={{ top, left, width }}
        >
          {opsiTampil.length === 0 && (
            <p className="px-3 py-2 text-xs text-slate-500">Tidak ditemukan</p>
          )}
          {opsiTampil.map((o, index) => {
            const aktif = index === terfokus
            const terpilih = o.value === value
            return (
              <button
                key={o.value === '' ? '__kosong' : o.value}
                type="button"
                role="option"
                id={`${listId}-${index}`}
                aria-selected={terpilih}
                tabIndex={-1}
                onMouseEnter={() => setTerfokus(index)}
                onMouseDown={(e) => {
                  // Cegah blur input sebelum klik diproses
                  e.preventDefault()
                  pilih(o.value)
                }}
                className={`flex w-full items-center gap-2 px-3 py-2 text-left text-sm ${
                  aktif ? 'bg-teal-50 text-teal-800' : 'text-slate-700'
                } ${terpilih ? 'font-medium' : ''}`}
              >
                <Check
                  aria-hidden="true"
                  className={`size-3.5 shrink-0 ${terpilih ? 'opacity-100' : 'opacity-0'}`}
                />
                <span className="min-w-0 truncate">{o.label}</span>
              </button>
            )
          })}
        </div>
      )}
    </div>
  )
}
