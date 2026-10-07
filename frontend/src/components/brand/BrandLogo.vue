<script setup lang="ts">
import { BRAND_NAME, BRAND_WORDMARK } from '@/core/domain/brand'

withDefaults(defineProps<{
  /** "dark" quando o logo fica sobre fundo escuro (sidebar, login). */
  tone?: 'light' | 'dark'
  /** Esconde o nome e mostra só o símbolo (sidebar recolhida). */
  compact?: boolean
  size?: number
  /** Linha desenha e moeda salta ao montar: só na abertura do app. */
  animated?: boolean
}>(), {
  tone: 'light',
  compact: false,
  size: 36,
  animated: false,
})
</script>

<template>
  <span class="inline-flex items-center gap-2.5" :class="{ 'brand-logo--animated': animated }">
    <svg
      :width="size"
      :height="size"
      viewBox="0 0 64 64"
      :role="compact ? 'img' : undefined"
      :aria-label="compact ? BRAND_NAME : undefined"
      :aria-hidden="compact ? undefined : 'true'"
      class="shrink-0"
    >
      <rect width="64" height="64" rx="18" :class="tone === 'dark' ? 'fill-white' : 'fill-brand-600'" />
      <path
        class="brand-logo__line"
        d="M15 42 L26 31 L34 37 L47 22"
        fill="none"
        stroke-width="6"
        stroke-linecap="round"
        stroke-linejoin="round"
        :class="tone === 'dark' ? 'stroke-brand-600' : 'stroke-white'"
      />
      <circle class="brand-logo__coin fill-capi-500" cx="48" cy="20" r="7" />
    </svg>
    <span
      v-if="!compact"
      class="font-display tracking-tight leading-none whitespace-nowrap"
      :style="{ fontSize: `${Math.round(size * 0.66)}px` }"
    >
      <span class="font-semibold" :class="tone === 'dark' ? 'text-[#9AA6BA]' : 'text-ink-500'">{{ BRAND_WORDMARK.light }}</span>
      <span class="font-extrabold" :class="tone === 'dark' ? 'text-white' : 'text-ink-900'">{{ BRAND_WORDMARK.strong }}</span>
    </span>
  </span>
</template>

<style scoped>
.brand-logo--animated .brand-logo__line {
  stroke-dasharray: 48;
  stroke-dashoffset: 48;
  animation: brand-draw 1s var(--ease-out) 0.2s forwards;
}
.brand-logo--animated .brand-logo__coin {
  transform-box: fill-box;
  transform-origin: center;
  animation: brand-coin 500ms var(--ease-spring) 1.1s both;
}
@keyframes brand-draw {
  to { stroke-dashoffset: 0; }
}
@keyframes brand-coin {
  0% { transform: scale(0); }
  70% { transform: scale(1.25); }
  100% { transform: scale(1); }
}
</style>
