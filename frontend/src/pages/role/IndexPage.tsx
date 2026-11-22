import { useSession } from '../../auth/session'

export function IndexPage() {
  const { user } = useSession()

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">List Role</h1>

      <section className="card">
        <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3">
          <h2 className="text-sm font-semibold">Daftar role</h2>
          <p className="text-xs text-slate-500">1 role</p>
        </div>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Role</th>
              <th className="px-4 py-3 font-medium">Kode</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td className="px-4 py-3">{user?.role.name}</td>
              <td className="px-4 py-3 font-mono">{user?.role.code}</td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>
  )
}
