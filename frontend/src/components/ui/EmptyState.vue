<script setup lang="ts">
import CapiMascot, { type CapiMood } from '@/components/brand/CapiMascot.vue'

withDefaults(defineProps<{
  title: string
  description?: string
  mood?: CapiMood
  size?: number
}>(), {
  description: '',
  mood: 'sleeping',
  size: 120,
})
</script>

<template>
  <div class="flex flex-col items-center text-center gap-1.5 px-6 py-8">
    <CapiMascot :mood="mood" :size="size" />
    <h3 class="text-lg font-bold text-ink-900">{{ title }}</h3>
    <p v-if="description" class="text-sm text-ink-500 max-w-xs leading-relaxed">{{ description }}</p>
    <div v-if="$slots.default" class="mt-3">
      <slot />
    </div>
  </div>
</template>
