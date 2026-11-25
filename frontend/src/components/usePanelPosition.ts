import { useCallback, useEffect, useLayoutEffect, useState } from 'react'
import type { RefObject } from 'react'

/**
 * Hitung posisi panel (dropdown) relatif viewport agar tidak terpotong
 * oleh container dengan overflow. Default posisi di bawah trigger,
 * diubah ke atas jika ruang di bawah tidak cukup.
 *
 * `aktif` dipakai agar posisi dihitung ulang tepat saat panel muncul
 * (sebelum paint), bukan hanya saat komponen pertama kali di-mount.
 *
 * Dipakai oleh SearchableSelect dan AksiMenu yang dirender lewat portal
 * (position: fixed), sehingga panel tidak ter-clip oleh overflow-x-auto
 * di dalam tabel atau modal.
 */
export function usePanelPosition(
  triggerRef: RefObject<HTMLElement | null>,
  panelRef: RefObject<HTMLElement | null>,
  aktif = true,
): { top: number; left: number; width: number } {
  const [pos, setPos] = useState({ top: 0, left: 0, width: 0 })

  const reposition = useCallback(() => {
    const trigger = triggerRef.current
    const panel = panelRef.current
    if (!trigger) return

    const rect = trigger.getBoundingClientRect()
    const vh = window.innerHeight
    const vw = window.innerWidth
    const width = Math.max(rect.width, 200)

    const panelHeight = panel?.offsetHeight || 260
    const ruangBawah = vh - rect.bottom
    const ruangAtas = rect.top

    // Default di bawah trigger; balik ke atas bila tidak cukup ruang.
    let top = rect.bottom
    if (ruangBawah < panelHeight && ruangAtas > ruangBawah) {
      top = Math.max(0, rect.top - panelHeight)
    }

    let left = rect.left
    if (left + width > vw) left = Math.max(0, vw - width - 8)

    setPos({ top, left, width })
  }, [triggerRef, panelRef])

  // Hitung sebelum paint saat panel dibuka.
  useLayoutEffect(() => {
    if (!aktif) return
    reposition()
    // Ulangi setelah panel ter-render agar tinggi panel terukur.
    const id = requestAnimationFrame(reposition)
    return () => cancelAnimationFrame(id)
  }, [aktif, reposition])

  useEffect(() => {
    if (!aktif) return
    window.addEventListener('scroll', reposition, true)
    window.addEventListener('resize', reposition)
    return () => {
      window.removeEventListener('scroll', reposition, true)
      window.removeEventListener('resize', reposition)
    }
  }, [aktif, reposition])

  return pos
}
