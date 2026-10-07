<script setup lang="ts">
withDefaults(defineProps<{
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
  size?: 'md' | 'sm'
  disabled?: boolean
  loading?: boolean
  type?: 'button' | 'submit'
}>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
  type: 'button',
})
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :aria-busy="loading || undefined"
    class="inline-flex items-center justify-center gap-2 rounded-lg font-bold cursor-pointer transition duration-150 ease-(--ease-out) active:scale-[0.97] disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
    :class="[
      size === 'sm' ? 'min-h-9 px-3.5 text-sm' : 'min-h-11 px-4.5 text-[15px]',
      {
        'bg-brand-600 hover:bg-brand-800 text-white': variant === 'primary',
        'bg-white hover:bg-ink-50 text-ink-900 ring-[1.5px] ring-inset ring-ink-200': variant === 'secondary',
        'bg-transparent hover:bg-brand-50 text-brand-600': variant === 'ghost',
        'bg-expense hover:bg-[#9A3412] text-white': variant === 'danger',
      },
    ]"
  >
    <span v-if="loading" class="size-4 rounded-full border-[2.5px] border-current border-t-transparent animate-spin" aria-hidden="true"></span>
    <slot />
  </button>
</template>
