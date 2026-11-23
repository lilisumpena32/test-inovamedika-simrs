import type { ReactNode } from 'react'
import { useNavigate } from 'react-router-dom'

export function FormMaster({
  judul,
  onSubmit,
  pending,
  children,
}: {
  judul: string
  onSubmit: () => void
  pending: boolean
  children: ReactNode
}) {
  const navigate = useNavigate()

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">{judul}</h1>

      <form
        className="card max-w-xl space-y-4 p-5"
        onSubmit={(e) => {
          e.preventDefault()
          onSubmit()
        }}
        noValidate
      >
        {children}

        <div className="flex gap-2 pt-2">
          <button
            type="submit"
            disabled={pending}
            className="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
          >
            {pending ? 'Menyimpan...' : 'Simpan'}
          </button>
          <button
            type="button"
            onClick={() => navigate(-1)}
            className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Batal
          </button>
        </div>
      </form>
    </div>
  )
}
