import { useContext } from 'react'
import { ToastContext } from '../components/toast-context'
import type { ToastApi } from '../components/toast-context'

export function useToast(): ToastApi {
  const toast = useContext(ToastContext)

  if (toast === null) {
    throw new Error('useToast dipakai di luar ToastProvider')
  }

  return toast
}
