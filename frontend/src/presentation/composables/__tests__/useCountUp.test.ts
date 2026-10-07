import { describe, it, expect, vi, afterEach } from 'vitest'
import { defineComponent, h, ref, nextTick } from 'vue'
import { mount } from '@vue/test-utils'
import { useCountUp } from '../useCountUp'

const mountCounter = (target: ReturnType<typeof ref<number>>) => {
  let shown!: ReturnType<typeof useCountUp>
  mount(defineComponent({
    setup() {
      shown = useCountUp(target as any, 1000)
      return () => h('span', shown.value)
    },
  }))
  return () => shown.value
}

const setReducedMotion = (reduce: boolean) => {
  window.matchMedia = vi.fn().mockReturnValue({ matches: reduce }) as any
}

describe('useCountUp', () => {
  afterEach(() => {
    vi.useRealTimers()
    vi.unstubAllGlobals()
  })

  it('vai direto ao valor final com prefers-reduced-motion', () => {
    setReducedMotion(true)
    const shown = mountCounter(ref(1234.5))
    expect(shown()).toBe(1234.5)
  })

  it('anima até o alvo e acompanha mudanças do valor', async () => {
    setReducedMotion(false)
    let now = 0
    const callbacks: FrameRequestCallback[] = []
    vi.stubGlobal('performance', { now: () => now })
    vi.stubGlobal('requestAnimationFrame', (cb: FrameRequestCallback) => callbacks.push(cb))
    vi.stubGlobal('cancelAnimationFrame', () => {})
    const flush = (t: number) => { now = t; const cbs = callbacks.splice(0); cbs.forEach((cb) => cb(t)) }

    const target = ref(100)
    const shown = mountCounter(target)
    flush(500)
    expect(shown()).toBeGreaterThan(0)
    expect(shown()).toBeLessThan(100)
    flush(1000)
    expect(shown()).toBe(100)

    target.value = 40
    await nextTick()
    flush(2000)
    expect(shown()).toBe(40)
  })

  it('trata valor inválido como zero', () => {
    setReducedMotion(true)
    expect(mountCounter(ref(Number.NaN))()).toBe(0)
  })
})
