import { describe, it, expect } from 'vitest'
import { NAV_GROUPS } from '../navigation'
// Lê as rotas do router.ts como texto para não carregar todas as telas no teste
import routerSource from '../../../router.ts?raw'

const routes = new Set([...routerSource.matchAll(/path:\s*'([^']+)'/g)].map((m) => m[1]))

describe('NAV_GROUPS', () => {
  const items = NAV_GROUPS.flatMap((g) => g.items)

  it('cobre todas as telas internas do app, sem repetir', () => {
    const publicRoutes = ['/', '/signup', '/forgot-password']
    // Páginas de erro (/erro/:code e o 404 de rota desconhecida) não são telas do menu.
    const isErrorRoute = (r: string) => r.startsWith('/erro/') || r.startsWith('/:pathMatch')
    const expected = [...routes].filter((r) => !publicRoutes.includes(r) && !isErrorRoute(r)).sort()
    expect(items.map((i) => i.to).sort()).toEqual(expected)
  })

  it('só o primeiro grupo fica sem título', () => {
    expect(NAV_GROUPS[0].title).toBe('')
    expect(NAV_GROUPS.slice(1).every((g) => g.title.length > 0)).toBe(true)
  })
})
