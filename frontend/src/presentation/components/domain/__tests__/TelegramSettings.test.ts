import { describe, it, expect, vi, beforeEach } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'

const api = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), delete: vi.fn() }))
vi.mock('@/data/api/HttpClient', () => ({ default: api }))
vi.mock('vue3-toastify', () => ({ toast: { success: vi.fn(), info: vi.fn(), error: vi.fn() } }))

import TelegramSettings from '../TelegramSettings.vue'

describe('TelegramSettings', () => {
  beforeEach(() => vi.clearAllMocks())

  it('avisa quando o bot não está configurado no servidor', async () => {
    api.get.mockResolvedValue({ data: { enabled: false, botUsername: null, linked: false, telegramUsername: null } })
    const w = mount(TelegramSettings)
    await flushPromises()
    expect(w.text()).toContain('ainda não foi configurado')
    expect(w.find('button').exists()).toBe(false)
  })

  it('gera o código e mostra o link para abrir o bot', async () => {
    api.get.mockResolvedValue({ data: { enabled: true, botUsername: 'meu_bot', linked: false, telegramUsername: null } })
    api.post.mockResolvedValue({ data: { code: 'ABCD234567', deepLink: 'https://t.me/meu_bot?start=ABCD234567' } })
    const w = mount(TelegramSettings)
    await flushPromises()

    await w.find('button').trigger('click')
    await flushPromises()

    expect(api.post).toHaveBeenCalledWith('/api/telegram/link')
    expect(w.find('a[href="https://t.me/meu_bot?start=ABCD234567"]').exists()).toBe(true)
    expect(w.text()).toContain('/start ABCD234567')
    w.unmount()
  })

  it('mostra o usuário vinculado e permite desconectar', async () => {
    api.get.mockResolvedValue({ data: { enabled: true, botUsername: 'meu_bot', linked: true, telegramUsername: 'ana' } })
    api.delete.mockResolvedValue({ data: { success: true } })
    const w = mount(TelegramSettings)
    await flushPromises()

    expect(w.text()).toContain('Conectado como @ana')
    await w.find('button').trigger('click')
    await flushPromises()
    expect(api.delete).toHaveBeenCalledWith('/api/telegram/link')
  })
})
