import { Ellipsis } from 'lucide-react'
import { useState } from 'react'

export type AksiItem = {
  label: string
  onClick: () => void
}

export function AksiMenu({ items }: { items: AksiItem[] }) {
  const [terbuka, setTerbuka] = useState(false)

  if (items.length === 0) {
    return null
  }

  return (
    <div className="relative flex justify-end">
      <button
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

      {terbuka && (
        <>
          <div
            className="fixed inset-0 z-10"
            onClick={() => setTerbuka(false)}
            aria-hidden="true"
          />
          <div
            role="menu"
            className="absolute right-0 z-20 mt-1 w-36 rounded-md border border-slate-200 bg-white py-1 shadow-lg"
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
                className="block w-full px-3 py-1.5 text-left text-sm text-slate-700 hover:bg-slate-50"
              >
                {item.label}
              </button>
            ))}
          </div>
        </>
      )}
    </div>
  )
}
