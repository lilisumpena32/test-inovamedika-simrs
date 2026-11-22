import { useState } from 'react'
import { useSession } from '../../auth/session'

export function IndexPage() {
  const { user } = useSession()

  const today = useState(() =>
    new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(new Date()),
  )[0]

  return (
    <header>
      <p className="text-sm text-slate-500">{today}</p>
      <h1 className="mt-1 text-2xl font-semibold">Selamat datang, {user?.full_name}</h1>
    </header>
  )
}
