import { describe, it, expect, beforeEach } from 'vitest'
import api, { readCookie } from '../HttpClient'

/** Roda os interceptors de requisição sem fazer chamada de rede. */
async function prepared(method: string) {
  const handlers = (api.interceptors.request as any).handlers.filter(Boolean)
  let config: any = { method, url: '/api/x', headers: {} }
  for (const h of handlers) config = await h.fulfilled(config)
  return config
}

describe('HttpClient', () => {
  beforeEach(() => {
    document.cookie = 'sf_csrf=; Max-Age=0; Path=/'
    localStorage.clear()
  })

  it('lê cookies pelo nome', () => {
    expect(readCookie('sf_csrf', 'a=1; sf_csrf=abc%3D; b=2')).toBe('abc=')
    expect(readCookie('nada', 'a=1')).toBeNull()
  })

  it('envia o cookie de sessão e nunca um Bearer do localStorage', async () => {
    localStorage.setItem('token', 'antigo')
    const config = await prepared('get')
    expect(api.defaults.withCredentials).toBe(true)
    expect(config.headers.Authorization).toBeUndefined()
  })

  it('repete o token CSRF só em requisições de escrita', async () => {
    document.cookie = 'sf_csrf=tok123; Path=/'
    localStorage.setItem('workspaceId', '7')

    expect((await prepared('post')).headers['X-CSRF-Token']).toBe('tok123')
    expect((await prepared('delete')).headers['X-CSRF-Token']).toBe('tok123')
    const get = await prepared('get')
    expect(get.headers['X-CSRF-Token']).toBeUndefined()
    expect(get.headers['X-Workspace-Id']).toBe('7')
  })
})
