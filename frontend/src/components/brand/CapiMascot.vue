<script setup lang="ts">
import { computed } from 'vue'

export type CapiMood = 'happy' | 'celebrating' | 'thinking' | 'alert' | 'sleeping'

const props = withDefaults(defineProps<{
  mood?: CapiMood
  size?: number
  animated?: boolean
  /** Texto alternativo; vazio = decorativa (aria-hidden), quando o texto ao lado já explica. */
  label?: string
}>(), {
  mood: 'happy',
  size: 120,
  animated: true,
  label: '',
})

const eyesOpen = computed(() => ['happy', 'thinking', 'alert'].includes(props.mood))
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 200 200"
    class="capi shrink-0"
    :class="[`capi--${mood}`, { 'capi--animated': animated }]"
    :role="label ? 'img' : undefined"
    :aria-label="label || undefined"
    :aria-hidden="label ? undefined : 'true'"
    data-testid="capi"
    :data-mood="mood"
  >
    <ellipse cx="100" cy="188" rx="56" ry="6" fill="#0B1324" opacity="0.1" />
    <g class="capi__body">
      <ellipse cx="72" cy="178" rx="16" ry="8" fill="#7E5235" />
      <ellipse cx="128" cy="178" rx="16" ry="8" fill="#7E5235" />
      <ellipse cx="100" cy="140" rx="66" ry="42" fill="#A8724A" />
      <ellipse cx="100" cy="152" rx="38" ry="24" fill="#D9A877" />
      <ellipse v-if="mood === 'celebrating'" class="capi__arm" cx="160" cy="116" rx="9" ry="18" fill="#A8724A" />
      <circle cx="58" cy="66" r="11" fill="#7E5235" />
      <circle cx="142" cy="66" r="11" fill="#7E5235" />
      <circle cx="58" cy="66" r="5" fill="#5A3A26" />
      <circle cx="142" cy="66" r="5" fill="#5A3A26" />
      <rect x="50" y="58" width="100" height="86" rx="42" fill="#A8724A" />
      <ellipse cx="64" cy="112" rx="8" ry="4.5" fill="#F09A86" opacity="0.55" />
      <ellipse cx="136" cy="112" rx="8" ry="4.5" fill="#F09A86" opacity="0.55" />

      <g v-if="eyesOpen" class="capi__eyes" :transform="mood === 'thinking' ? 'translate(2 -3)' : undefined">
        <ellipse cx="80" cy="88" rx="5" ry="6" fill="#1A1410" />
        <ellipse cx="120" cy="88" rx="5" ry="6" fill="#1A1410" />
        <circle cx="81.5" cy="86" r="1.6" fill="#FFF" />
        <circle cx="121.5" cy="86" r="1.6" fill="#FFF" />
      </g>
      <g v-else-if="mood === 'celebrating'" stroke="#1A1410" stroke-width="3.5" fill="none" stroke-linecap="round">
        <path d="M73 90 Q80 81 87 90" />
        <path d="M113 90 Q120 81 127 90" />
      </g>
      <g v-else stroke="#1A1410" stroke-width="3" fill="none" stroke-linecap="round">
        <path d="M73 88 Q80 93 87 88" />
        <path d="M113 88 Q120 93 127 88" />
      </g>

      <rect x="74" y="100" width="52" height="34" rx="17" fill="#7E5235" />
      <ellipse cx="91" cy="110" rx="3.5" ry="2.5" fill="#3A2518" />
      <ellipse cx="109" cy="110" rx="3.5" ry="2.5" fill="#3A2518" />
      <path v-if="mood === 'happy' || mood === 'sleeping'" d="M93 122 Q100 129 107 122" stroke="#2A1A10" stroke-width="2.5" fill="none" stroke-linecap="round" />
      <path v-else-if="mood === 'celebrating'" d="M92 120 Q100 133 108 120 Z" fill="#2A1A10" />
      <path v-else-if="mood === 'thinking'" d="M94 125 L106 125" stroke="#2A1A10" stroke-width="2.5" stroke-linecap="round" />
      <ellipse v-else cx="100" cy="124" rx="3.5" ry="4.5" fill="#2A1A10" />

      <g :transform="`translate(100 42) rotate(${mood === 'alert' ? -18 : mood === 'sleeping' ? 12 : 0})`">
        <g class="capi__coin">
          <circle r="16" fill="#F2A541" stroke="#C97C12" stroke-width="3" />
          <circle r="9" fill="none" stroke="#C97C12" stroke-width="2" opacity="0.7" />
        </g>
      </g>
    </g>

    <g v-if="mood === 'celebrating'" fill="#F2A541">
      <path class="capi__spark" d="M32 55 L34.5 61.5 L41 64 L34.5 66.5 L32 73 L29.5 66.5 L23 64 L29.5 61.5 Z" />
      <path class="capi__spark capi__spark--2" d="M168 29 L170 34 L175 36 L170 38 L168 43 L166 38 L161 36 L166 34 Z" />
      <path class="capi__spark capi__spark--3" d="M40 20 L41.8 24.2 L46 26 L41.8 27.8 L40 32 L38.2 27.8 L34 26 L38.2 24.2 Z" />
    </g>
    <g v-if="mood === 'thinking'" fill="#FFF" stroke="#C5CCD6" stroke-width="2">
      <circle cx="154" cy="62" r="4" />
      <circle cx="165" cy="46" r="6" />
      <circle cx="180" cy="24" r="10" />
    </g>
    <path v-if="mood === 'alert'" class="capi__sweat" d="M152 60 Q159 71 152 77 Q145 71 152 60 Z" fill="#6BB6FF" />
    <g v-if="mood === 'sleeping'" fill="#5B6B85" font-weight="700" font-family="sans-serif">
      <text class="capi__z" x="150" y="58" font-size="16">z</text>
      <text class="capi__z capi__z--2" x="162" y="40" font-size="20">z</text>
      <text class="capi__z capi__z--3" x="176" y="20" font-size="24">z</text>
    </g>
  </svg>
</template>

<style scoped>
/* Animações em CSS (não SMIL) para respeitar prefers-reduced-motion do style.css global */
.capi * { transform-box: fill-box; transform-origin: center; }
.capi__body { transform-box: view-box; transform-origin: 100px 180px; }

.capi--animated .capi__body { animation: capi-bob 3s ease-in-out infinite; }
.capi--animated.capi--celebrating .capi__body { animation: capi-jump 0.8s ease-in-out infinite; }
.capi--animated.capi--alert .capi__body { animation: capi-shake 0.4s ease-in-out infinite; }
.capi--animated.capi--sleeping .capi__body { animation: capi-breathe 4s ease-in-out infinite; }
.capi--animated .capi__eyes ellipse { animation: capi-blink 4s infinite; }
.capi--animated:not(.capi--sleeping) .capi__coin { animation: capi-coin 3s ease-in-out infinite; }
.capi--animated .capi__arm { transform-origin: 30% 90%; animation: capi-wave 0.8s ease-in-out infinite; }
.capi--animated .capi__spark { animation: capi-twinkle 1.2s ease-in-out infinite; }
.capi--animated .capi__spark--2 { animation-delay: 0.4s; }
.capi--animated .capi__spark--3 { animation-delay: 0.8s; }
.capi--animated .capi__sweat { animation: capi-drip 1.6s ease-in-out infinite; }
.capi--animated .capi__z { animation: capi-twinkle 2.4s ease-in-out infinite; }
.capi--animated .capi__z--2 { animation-delay: 0.6s; }
.capi--animated .capi__z--3 { animation-delay: 1.2s; }

@keyframes capi-bob { 50% { transform: translateY(-5px); } }
@keyframes capi-jump { 50% { transform: translateY(-16px); } }
@keyframes capi-shake { 25% { transform: translateX(-2px); } 75% { transform: translateX(2px); } }
@keyframes capi-breathe { 50% { transform: scaleY(0.98); } }
@keyframes capi-blink { 0%, 92%, 100% { transform: scaleY(1); } 96% { transform: scaleY(0.1); } }
@keyframes capi-coin { 0%, 70%, 90%, 100% { transform: scaleX(1); } 80% { transform: scaleX(0.15); } }
@keyframes capi-wave { 0%, 100% { transform: rotate(-25deg); } 50% { transform: rotate(15deg); } }
@keyframes capi-twinkle { 0%, 100% { opacity: 0; } 50% { opacity: 1; } }
@keyframes capi-drip { 50% { transform: translateY(6px); } }
</style>
