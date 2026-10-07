<script setup lang="ts">
import { storeToRefs } from 'pinia'
import CapiMascot from './CapiMascot.vue'
import { useCelebrationStore } from '@/presentation/store/celebrationStore'

const store = useCelebrationStore()
const { current } = storeToRefs(store)
</script>

<template>
  <div class="fixed inset-x-0 bottom-24 lg:bottom-6 z-[70] flex justify-center px-4 pointer-events-none" role="status" aria-live="polite">
    <Transition name="celebration">
      <div
        v-if="current"
        :key="current.id"
        class="pointer-events-auto flex items-center gap-2 max-w-md w-full bg-ink-900 text-white rounded-2xl py-2 pl-1 pr-3 shadow-2xl ring-1 ring-white/10"
        data-testid="celebration"
      >
        <CapiMascot mood="celebrating" :size="76" />
        <div class="flex-1 min-w-0">
          <p class="font-display font-extrabold text-base leading-tight">{{ current.title }}</p>
          <p v-if="current.detail" class="text-sm text-ink-200 truncate">{{ current.detail }}</p>
        </div>
        <button
          type="button"
          class="size-9 shrink-0 inline-flex items-center justify-center rounded-lg text-ink-200 hover:text-white hover:bg-ink-800 transition"
          aria-label="Fechar"
          @click="store.dismiss()"
        >
          <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.celebration-enter-active { animation: celebration-in 600ms var(--ease-spring) both; }
.celebration-leave-active { transition: opacity 200ms var(--ease-out), transform 200ms var(--ease-out); }
.celebration-leave-to { opacity: 0; transform: translateY(12px); }
@keyframes celebration-in {
  0% { opacity: 0; transform: translateY(24px) scale(0.9); }
  70% { opacity: 1; transform: translateY(-4px) scale(1.02); }
  100% { opacity: 1; transform: none; }
}
</style>
