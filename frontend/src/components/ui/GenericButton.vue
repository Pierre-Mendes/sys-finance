<script setup lang="ts">
defineProps<{
  variant?: 'primary' | 'secondary' | 'danger'
  disabled?: boolean
}>()
</script>

<template>
  <button
    :disabled="disabled"
    class="px-4 py-2 rounded-xl font-bold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
    :class="{
      'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm hover:shadow-md': variant === 'primary' || !variant,
      'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white': variant === 'secondary',
      'bg-red-500 hover:bg-red-600 text-white': variant === 'danger'
    }"
  >
    <slot />
  </button>
</template>
