import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface Celebration {
  id: number
  title: string
  detail: string
}

/**
 * Comemorações da Capi (conta paga, meta concluída). Uma por vez: a mais recente substitui a anterior.
 * Exibidas por CelebrationToast, montado em App.vue.
 */
export const useCelebrationStore = defineStore('celebration', () => {
  const current = ref<Celebration | null>(null)
  let nextId = 1
  let timer: ReturnType<typeof setTimeout> | undefined

  const dismiss = () => {
    clearTimeout(timer)
    current.value = null
  }

  const celebrate = (title: string, detail = '', durationMs = 4000) => {
    clearTimeout(timer)
    current.value = { id: nextId++, title, detail }
    timer = setTimeout(dismiss, durationMs)
  }

  return { current, celebrate, dismiss }
})
