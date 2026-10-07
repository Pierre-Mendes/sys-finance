import { ref, watch, onBeforeUnmount, type Ref } from 'vue'

const prefersReducedMotion = () =>
  typeof window !== 'undefined' && !!window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

/**
 * Anima um número do valor exibido até o alvo (ease-out cúbico).
 * Com prefers-reduced-motion, ou sem requestAnimationFrame, vai direto ao valor final.
 */
export function useCountUp(target: Ref<number>, durationMs = 1200) {
  const display = ref(0)
  let frame = 0

  const run = (to: number) => {
    cancelAnimationFrame(frame)
    const from = display.value
    if (!Number.isFinite(to)) {
      display.value = 0
      return
    }
    if (prefersReducedMotion() || typeof requestAnimationFrame === 'undefined' || from === to) {
      display.value = to
      return
    }
    const start = performance.now()
    const step = (now: number) => {
      const p = Math.min(1, (now - start) / durationMs)
      display.value = from + (to - from) * (1 - Math.pow(1 - p, 3))
      if (p < 1) frame = requestAnimationFrame(step)
    }
    frame = requestAnimationFrame(step)
  }

  watch(target, (to) => run(to), { immediate: true })
  onBeforeUnmount(() => cancelAnimationFrame(frame))

  return display
}
