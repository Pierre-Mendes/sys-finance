<template>
  <div class="min-h-screen bg-gray-50 text-gray-900 font-sans antialiased text-base selection:bg-primary selection:text-white">
    <!-- Falha ao carregar a tela (API 500/403/400 ou sem conexão): mostra o erro mantendo a URL, para "Tentar novamente" recarregar. -->
    <ErrorPage v-if="currentError" :code="currentError.code" :reference="currentError.reference" />
    <router-view v-else></router-view>
  </div>
</template>

<script setup lang="ts">
import ErrorPage from './views/ErrorPage.vue'
import { currentError } from './core/errors/appError'
</script>
