import type { MenuNode } from '../api/types'

export function findMenu(menus: MenuNode[], kode: string): MenuNode | null {
  for (const menu of menus) {
    if (menu.kode === kode) return menu

    const child = findMenu(menu.children, kode)
    if (child !== null) return child
  }

  return null
}
