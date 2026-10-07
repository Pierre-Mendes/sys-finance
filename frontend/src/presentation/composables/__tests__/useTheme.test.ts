import { describe, it, expect, beforeEach, vi } from 'vitest'
import { initTheme, setTheme, useTheme, THEME_STORAGE_KEY } from '../useTheme'

const mockSystem = (dark: boolean) => {
  window.matchMedia = vi.fn().mockReturnValue({ matches: dark, addEventListener: vi.fn() }) as any
}

describe('useTheme', () => {
  beforeEach(() => {
    localStorage.clear()
    document.documentElement.className = ''
  })

  it('sem preferência salva segue o sistema', () => {
    mockSystem(true)
    initTheme()
    expect(useTheme().preference.value).toBe('system')
    expect(document.documentElement.classList.contains('dark')).toBe(true)
  })

  it('preferência salva vence o sistema', () => {
    mockSystem(true)
    localStorage.setItem(THEME_STORAGE_KEY, 'light')
    initTheme()
    expect(document.documentElement.classList.contains('dark')).toBe(false)
  })

  it('setTheme aplica e salva; cycle percorre claro, escuro e sistema', () => {
    mockSystem(false)
    initTheme()
    setTheme('dark')
    expect(document.documentElement.classList.contains('dark')).toBe(true)
    expect(localStorage.getItem(THEME_STORAGE_KEY)).toBe('dark')

    const { cycle, preference } = useTheme()
    cycle()
    expect(preference.value).toBe('system')
    expect(document.documentElement.classList.contains('dark')).toBe(false)
    cycle()
    expect(preference.value).toBe('light')
  })

  it('isDark acompanha a troca (os gráficos recalculam as cores a partir dele)', () => {
    mockSystem(false)
    initTheme()
    const { isDark } = useTheme()
    setTheme('dark')
    expect(isDark.value).toBe(true)
    setTheme('light')
    expect(isDark.value).toBe(false)
  })
})
