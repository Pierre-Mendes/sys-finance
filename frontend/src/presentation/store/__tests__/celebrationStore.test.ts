import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { useCelebrationStore } from '../celebrationStore'
import CelebrationToast from '@/components/brand/CelebrationToast.vue'

describe('celebrationStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.useFakeTimers()
  })
  afterEach(() => vi.useRealTimers())

  it('mostra a comemoração e some sozinha depois do tempo', () => {
    const store = useCelebrationStore()
    store.celebrate('Conta paga!', 'Luz', 1000)
    expect(store.current?.title).toBe('Conta paga!')
    vi.advanceTimersByTime(1000)
    expect(store.current).toBeNull()
  })

  it('a mais recente substitui a anterior e reinicia o tempo', () => {
    const store = useCelebrationStore()
    store.celebrate('Primeira', '', 1000)
    vi.advanceTimersByTime(800)
    store.celebrate('Segunda', '', 1000)
    vi.advanceTimersByTime(800)
    expect(store.current?.title).toBe('Segunda')
    vi.advanceTimersByTime(200)
    expect(store.current).toBeNull()
  })

  it('CelebrationToast exibe a Capi comemorando e fecha no botão', async () => {
    const store = useCelebrationStore()
    const w = mount(CelebrationToast)
    expect(w.find('[data-testid="celebration"]').exists()).toBe(false)
    store.celebrate('Meta concluída!', 'Viagem')
    await nextTick()
    expect(w.text()).toContain('Meta concluída!')
    expect(w.find('[data-testid="capi"]').attributes('data-mood')).toBe('celebrating')
    await w.find('button[aria-label="Fechar"]').trigger('click')
    expect(store.current).toBeNull()
  })
})
