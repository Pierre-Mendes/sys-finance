<template>
  <div class="min-h-screen bg-ink-50 text-ink-900 font-sans antialiased text-base selection:bg-primary selection:text-white">
    <router-view></router-view>
    <CelebrationToast />
  </div>
</template>

<script setup lang="ts">
import CelebrationToast from '@/components/brand/CelebrationToast.vue'
</script>
