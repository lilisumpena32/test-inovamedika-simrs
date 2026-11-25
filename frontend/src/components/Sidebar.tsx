import {
  BarChart3,
  Briefcase,
  CalendarDays,
  Circle,
  ClipboardList,
  FlaskConical,
  FileText,
  Folder,
  LayoutDashboard,
  ListOrdered,
  LogOut,
  MapPin,
  Pill,
  Receipt,
  Settings,
  Shield,
  Stethoscope,
  Syringe,
  UserRound,
  Users,
  X,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { NavLink } from 'react-router-dom'
import type { MenuNode } from '../api/types'
import { useSession } from '../auth/session'

const IKONS: Record<string, LucideIcon> = {
  'layout-dashboard': LayoutDashboard,
  folder: Folder,
  'user-round': UserRound,
  'map-pin': MapPin,
  briefcase: Briefcase,
  'bar-chart-3': BarChart3,
  'calendar-days': CalendarDays,
  'clipboard-list': ClipboardList,
  'flask-conical': FlaskConical,
  'list-ordered': ListOrdered,
  pill: Pill,
  prescription: FileText,
  receipt: Receipt,
  'stethoscope': Stethoscope,
  'syringe': Syringe,
  settings: Settings,
  shield: Shield,
  users: Users,
}

function MenuIcon({ nama }: { nama: string | null }) {
  const Ikon = (nama !== null ? IKONS[nama] : undefined) ?? Circle

  return <Ikon aria-hidden="true" className="size-4 shrink-0" strokeWidth={1.8} />
}

function initials(name: string): string {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word[0]?.toUpperCase() ?? '')
    .join('')
}

function MenuRow({ menu, onClose }: { menu: MenuNode; onClose: () => void }) {
  if (menu.children.length > 0) {
    return (
      <div>
        <p className="px-3 pb-1 pt-5 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
          {menu.label}
        </p>
        <div className="space-y-0.5">
          {menu.children.map((child) => (
            <MenuRow key={child.kode} menu={child} onClose={onClose} />
          ))}
        </div>
      </div>
    )
  }

  if (menu.path === null) return null

  return (
    <NavLink
      to={menu.path}
      end={menu.path === '/'}
      onClick={onClose}
      className={({ isActive }) =>
        [
          'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition',
          isActive
            ? 'bg-teal-500/15 text-teal-200'
            : 'text-slate-300 hover:bg-slate-800 hover:text-white',
        ].join(' ')
      }
    >
      <MenuIcon nama={menu.icon} />
      {menu.label}
    </NavLink>
  )
}

export function Sidebar({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { user, menus, signOut } = useSession()

  return (
    <>
      {open && (
        <div
          className="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"
          onClick={onClose}
          aria-hidden="true"
        />
      )}

      <aside
        className={[
          'fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-slate-900 transition-transform lg:translate-x-0',
          open ? 'translate-x-0' : '-translate-x-full',
        ].join(' ')}
      >
        <div className="flex h-16 items-center gap-3 border-b border-slate-800 px-5">
          <div className="grid size-8 place-items-center rounded-md bg-teal-500 text-sm font-bold text-slate-950">
            S
          </div>
          <div className="min-w-0">
            <p className="text-sm font-semibold text-white">SIMRS</p>
            <p className="truncate text-[11px] text-slate-400">Inova Medika</p>
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label="Tutup menu"
            className="ml-auto rounded-md p-1 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden"
          >
            <X className="size-5" />
          </button>
        </div>

        <nav className="flex-1 overflow-y-auto px-3 py-3">
          {menus.map((menu) => (
            <MenuRow key={menu.kode} menu={menu} onClose={onClose} />
          ))}
        </nav>

        <div className="border-t border-slate-800 p-3">
          <div className="flex items-center gap-3 rounded-md px-2 py-2">
            <div className="grid size-8 shrink-0 place-items-center rounded-full bg-slate-800 text-xs font-semibold text-slate-200">
              {initials(user?.full_name ?? '')}
            </div>
            <div className="min-w-0 flex-1">
              <p className="truncate text-sm font-medium text-white">{user?.full_name}</p>
              <p className="truncate text-[11px] text-slate-400">
                {user?.role.name === user?.full_name ? user?.username : user?.role.name}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={signOut}
            className="mt-1 flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white"
          >
            <LogOut aria-hidden="true" className="size-4" strokeWidth={1.8} />
            Keluar
          </button>
        </div>
      </aside>
    </>
  )
}
