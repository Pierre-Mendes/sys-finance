import { ref, computed, readonly } from 'vue'

export type ThemePreference = 'light' | 'dark' | 'system'

export const THEME_STORAGE_KEY = 'sysfinance:theme'
// Cor da barra do navegador no celular: acompanha o cabeçalho (branco no claro, superfície no escuro)
const THEME_COLORS = { light: '#FFFFFF', dark: '#17223A' } as const
const ORDER: ThemePreference[] = ['light', 'dark', 'system']

const readStored = (): ThemePreference => {
  try {
    const v = localStorage.getItem(THEME_STORAGE_KEY)
    return v === 'light' || v === 'dark' || v === 'system' ? v : 'system'
  } catch {
    return 'system'
  }
}

const systemQuery = () =>
  typeof window !== 'undefined' && window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null

const preference = ref<ThemePreference>('system')
const systemDark = ref(false)
const isDark = computed(() => preference.value === 'dark' || (preference.value === 'system' && systemDark.value))

const apply = () => {
  const dark = isDark.value
  const root = document.documentElement
  root.classList.toggle('dark', dark)
  root.style.colorScheme = dark ? 'dark' : 'light'
  document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? THEME_COLORS.dark : THEME_COLORS.light)
}

let started = false

/** Lê a preferência salva, aplica a classe .dark no <html> e acompanha o tema do sistema. Chamar antes do mount. */
export function initTheme() {
  preference.value = readStored()
  const query = systemQuery()
  systemDark.value = !!query?.matches
  if (!started) {
    query?.addEventListener?.('change', (e) => {
      systemDark.value = e.matches
      apply()
    })
    started = true
  }
  apply()
}

export function setTheme(next: ThemePreference) {
  preference.value = next
  try {
    localStorage.setItem(THEME_STORAGE_KEY, next)
  } catch {
    // modo privado/armazenamento bloqueado: vale só nesta sessão
  }
  apply()
}

export function useTheme() {
  const cycle = () => setTheme(ORDER[(ORDER.indexOf(preference.value) + 1) % ORDER.length])
  return { preference: readonly(preference), isDark, setTheme, cycle }
}
