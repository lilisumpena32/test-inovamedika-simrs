import { Ellipsis } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { usePanelPosition } from './usePanelPosition'

export type AksiItem = {
  label: string
  onClick: () => void
}

/**
 * Dropdown aksi tabel. Panel dirender lewat portal + position: fixed
 * agar tidak terpotong oleh overflow-x-auto pada wrapper tabel.
 */
export function AksiMenu({ items }: { items: AksiItem[] }) {
  const [terbuka, setTerbuka] = useState(false)

  const triggerRef = useRef<HTMLDivElement>(null)
  const panelRef = useRef<HTMLDivElement>(null)
  const tombolRef = useRef<HTMLButtonElement>(null)

  const { top, left, width } = usePanelPosition(triggerRef, panelRef, terbuka)

  useEffect(() => {
    if (!terbuka) return
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        setTerbuka(false)
        tombolRef.current?.focus()
      }
    }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [terbuka])

  if (items.length === 0) {
    return null
  }

  const lebarMenu = 144
  const kiri = Math.max(8, left + width - lebarMenu)

  return (
    <div ref={triggerRef} className="relative flex justify-end">
      <button
        ref={tombolRef}
        type="button"
        onClick={() => setTerbuka((nilai) => !nilai)}
        aria-haspopup="menu"
        aria-expanded={terbuka}
        aria-label="Aksi"
        title="Aksi"
        className="rounded border border-slate-300 p-1.5 text-slate-700 hover:bg-slate-50"
      >
        <Ellipsis aria-hidden="true" className="size-4" />
      </button>

      {terbuka &&
        createPortal(
          <>
            <div
              className="fixed inset-0 z-40"
              onClick={() => setTerbuka(false)}
              aria-hidden="true"
            />
            <div
              ref={panelRef}
              role="menu"
              className="fixed z-50 rounded-md border border-slate-200 bg-white py-1 shadow-lg"
              style={{ top, left: kiri, width: lebarMenu }}
            >
              {items.map((item) => (
                <button
                  key={item.label}
                  type="button"
                  role="menuitem"
                  onClick={() => {
                    setTerbuka(false)
                    item.onClick()
                  }}
                  className="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                >
                  {item.label}
                </button>
              ))}
            </div>
          </>,
          document.body,
        )}
    </div>
  )
}
