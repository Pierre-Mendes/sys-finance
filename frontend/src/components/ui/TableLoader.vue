<template>
  <tr>
    <td :colspan="columns" class="p-6" role="status" aria-live="polite">
        <div class="flex items-center gap-4 max-w-xl mx-auto">
            <CapiMascot mood="thinking" :size="72" />
            <div class="flex-1 flex flex-col gap-2.5" aria-hidden="true">
                <div class="skeleton h-3.5 w-2/3"></div>
                <div class="skeleton h-6 w-full"></div>
                <div class="skeleton h-3.5 w-1/2"></div>
            </div>
        </div>
        <span class="sr-only">{{ message || 'Carregando...' }}</span>
    </td>
  </tr>
</template>

<script setup lang="ts">
import CapiMascot from '@/components/brand/CapiMascot.vue'

defineProps<{
    columns: number,
    message?: string
}>()
</script>
