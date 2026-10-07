<script setup lang="ts">
import { computed } from 'vue'
import { useTheme } from '@/presentation/composables/useTheme'

defineProps<{ compact?: boolean }>()

const { preference, cycle } = useTheme()

const LABELS = { light: 'Claro', dark: 'Escuro', system: 'Sistema' } as const
const ICONS = {
  light: 'M12 4V2m0 20v-2m8-8h2M2 12h2m13.66-5.66l1.41-1.41M4.93 19.07l1.41-1.41m0-11.32L4.93 4.93m14.14 14.14l-1.41-1.41M12 8a4 4 0 100 8 4 4 0 000-8z',
  dark: 'M20.35 15.35A8 8 0 018.65 3.65a8 8 0 1011.7 11.7z',
  system: 'M4 5h16v11H4zM8 20h8M12 16v4',
} as const

const label = computed(() => LABELS[preference.value])
const icon = computed(() => ICONS[preference.value])
</script>

<template>
  <button
    type="button"
    class="flex w-full items-center justify-center gap-2 min-h-10 px-3 rounded-lg bg-ink-800 hover:bg-ink-700 text-ink-200 hover:text-white text-sm font-semibold transition cursor-pointer"
    :aria-label="`Tema: ${label}. Clique para alternar`"
    :title="compact ? `Tema: ${label}` : undefined"
    @click="cycle"
  >
    <svg class="size-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" :d="icon" /></svg>
    <span v-if="!compact">Tema: {{ label }}</span>
  </button>
</template>
