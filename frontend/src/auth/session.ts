import { createContext, useContext } from 'react'
import type { LoginResult, MenuNode, Profile } from '../api/types'

export type SessionValue = {
  token: string | null
  user: Profile | null
  menus: MenuNode[]
  ready: boolean
  failed: boolean
  signIn: (username: string, password: string) => Promise<LoginResult>
  signOut: () => void
  refresh: () => void
  can: (kode: string, action: keyof MenuNode['can']) => boolean
}

export const SessionContext = createContext<SessionValue | null>(null)

export function useSession(): SessionValue {
  const session = useContext(SessionContext)

  if (session === null) {
    throw new Error('useSession dipakai di luar AuthProvider')
  }

  return session
}
