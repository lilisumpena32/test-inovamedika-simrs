import { useQuery, useQueryClient } from '@tanstack/react-query'
import { useCallback, useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { useNavigate } from 'react-router-dom'
import { fetchSession, login } from '../api/auth'
import { TOKEN_KEY, UNAUTHORIZED_EVENT } from '../api/client'
import type { LoginResult } from '../api/types'
import { findMenu } from './menu'
import { SessionContext } from './session'
import type { SessionValue } from './session'

export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const [token, setToken] = useState<string | null>(() => localStorage.getItem(TOKEN_KEY))

  const session = useQuery({
    queryKey: ['session', token],
    queryFn: fetchSession,
    enabled: token !== null,
    retry: false,
    staleTime: 5 * 60 * 1000,
  })

  const signOut = useCallback(() => {
    localStorage.removeItem(TOKEN_KEY)
    setToken(null)
    queryClient.clear()
    navigate('/login', { replace: true })
  }, [navigate, queryClient])

  useEffect(() => {
    window.addEventListener(UNAUTHORIZED_EVENT, signOut)
    return () => window.removeEventListener(UNAUTHORIZED_EVENT, signOut)
  }, [signOut])

  const signIn = useCallback(
    async (username: string, password: string): Promise<LoginResult> => {
      const result = await login(username, password)
      localStorage.setItem(TOKEN_KEY, result.token)
      queryClient.setQueryData(['session', result.token], {
        user: result.user,
        menus: result.menus,
      })
      setToken(result.token)
      return result
    },
    [queryClient],
  )

  const refresh = useCallback(() => {
    void session.refetch()
  }, [session])

  const value = useMemo<SessionValue>(() => {
    const menus = session.data?.menus ?? []

    return {
      token,
      user: session.data?.user ?? null,
      menus,
      ready: token === null || session.isSuccess || session.isError,
      failed: session.isError,
      signIn,
      signOut,
      refresh,
      can: (kode, action) => {
        const menu = findMenu(menus, kode)
        return menu !== null && menu.can[action]
      },
    }
  }, [token, session.data, session.isSuccess, session.isError, signIn, signOut, refresh])

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>
}
