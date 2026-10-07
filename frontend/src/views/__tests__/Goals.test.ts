import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import Goals from '../Goals.vue'

const goal = {
  id: 1, workspaceId: 1, sharedWithWorkspaceId: null, accountId: null, isFavorite: false,
  title: 'Viagem', targetAmount: 10000, accumulatedAmount: 6500, targetDate: '2026-12-31',
}

vi.mock('@/data/api/HttpClient', () => ({
  default: {
    get: vi.fn((url: string) =>
      Promise.resolve({ data: { success: true, data: url.includes('/api/goals') ? [goal] : [] } }),
    ),
    post: vi.fn(), put: vi.fn(), delete: vi.fn(),
  },
}))

describe('Goals', () => {
  beforeEach(() => setActivePinia(createPinia()))

  it('mostra valores e prazo no formato brasileiro, qualquer que seja o idioma do navegador', async () => {
    const wrapper = mount(Goals, {
      global: { stubs: { MainLayout: { template: '<div><slot /></div>' } } },
    })
    await flushPromises()
    const text = wrapper.text().replace(/ /g, ' ')
    expect(text).toContain('R$ 6.500,00')
    expect(text).toContain('R$ 10.000,00')
    expect(text).toContain('Até 31/12/2026')
    expect(text).not.toContain('6,500')
  })
})
