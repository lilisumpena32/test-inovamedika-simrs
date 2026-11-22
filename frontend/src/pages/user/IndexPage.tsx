import { useSession } from '../../auth/session'

export function IndexPage() {
  const { user } = useSession()
  const rows = user === null ? [] : [user]

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">List User</h1>

      <section className="card">
        <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3">
          <h2 className="text-sm font-semibold">Daftar user</h2>
          <p className="text-xs text-slate-500">{rows.length} akun</p>
        </div>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
              <th className="px-4 py-3 font-medium">Nama</th>
              <th className="px-4 py-3 font-medium">Username</th>
              <th className="px-4 py-3 font-medium">Email</th>
              <th className="px-4 py-3 font-medium">Role</th>
              <th className="px-4 py-3 font-medium">Status</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((account) => (
              <tr key={account.id}>
                <td className="px-4 py-3">{account.full_name}</td>
                <td className="px-4 py-3 font-mono">{account.username}</td>
                <td className="px-4 py-3">{account.email ?? '-'}</td>
                <td className="px-4 py-3">{account.role.name}</td>
                <td className="px-4 py-3">
                  <span className="rounded bg-emerald-50 px-1.5 py-0.5 text-[11px] font-medium text-emerald-700">
                    Aktif
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>
    </div>
  )
}
