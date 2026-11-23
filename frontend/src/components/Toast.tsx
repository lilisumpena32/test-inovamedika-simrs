import { useCallback, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { ToastContext } from './toast-context'
import type { ToastApi } from './toast-context'

type Toast = {
  id: number
  message: string
  jenis: 'sukses' | 'gagal'
}

export function ToastProvider({ children }: { children: ReactNode }) {
  const [items, setItems] = useState<Toast[]>([])

  const show = useCallback((message: string, jenis: Toast['jenis']) => {
    const id = Date.now() + Math.random()
    setItems((lama) => [...lama, { id, message, jenis }])
    setTimeout(() => {
      setItems((lama) => lama.filter((toast) => toast.id !== id))
    }, 4000)
  }, [])

  const api = useMemo<ToastApi>(
    () => ({
      success: (message) => show(message, 'sukses'),
      error: (message) => show(message, 'gagal'),
    }),
    [show],
  )

  return (
    <ToastContext.Provider value={api}>
      {children}
      <div className="fixed bottom-4 right-4 z-50 flex w-80 flex-col gap-2" aria-live="polite">
        {items.map((toast) => (
          <div
            key={toast.id}
            role="status"
            className={[
              'rounded-md border px-3 py-2 text-sm shadow-lg',
              toast.jenis === 'gagal'
                ? 'border-red-200 bg-red-50 text-red-700'
                : 'border-emerald-200 bg-emerald-50 text-emerald-700',
            ].join(' ')}
          >
            {toast.message}
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  )
}
