<template>
  <main class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-12">
    <div class="w-full max-w-lg text-center">
      <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-3xl" :class="content.iconBg">
        <svg class="h-10 w-10" :class="content.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="content.icon" />
        </svg>
      </div>

      <p class="text-sm font-black uppercase tracking-widest text-gray-400">Erro {{ code }}</p>
      <h1 class="mt-2 text-3xl font-bold text-gray-900">{{ content.title }}</h1>
      <p class="mt-3 text-gray-600 leading-relaxed">{{ content.message }}</p>

      <p v-if="reference" class="mt-4 inline-block rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-500">
        Código para o suporte: <span class="font-mono font-semibold text-gray-700">{{ reference }}</span>
      </p>

      <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
        <button v-if="content.retry" type="button" @click="retry"
          class="rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-indigo-700">
          Tentar novamente
        </button>
        <button v-if="canGoBack" type="button" @click="goBack"
          class="rounded-xl border border-gray-200 bg-white px-5 py-3 font-semibold text-gray-700 transition hover:bg-gray-50">
          Voltar
        </button>
        <router-link v-if="code === 403" to="/workspaces" @click="clearError"
          class="rounded-xl border border-gray-200 bg-white px-5 py-3 font-semibold text-gray-700 transition hover:bg-gray-50">
          Trocar de espaço
        </router-link>
        <router-link :to="homePath" @click="clearError"
          class="rounded-xl px-5 py-3 font-semibold transition"
          :class="content.retry ? 'text-indigo-600 hover:bg-indigo-50' : 'bg-indigo-600 text-white hover:bg-indigo-700'">
          Ir para o início
        </router-link>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { clearError, type ErrorCode } from '@/core/errors/appError'

const props = withDefaults(defineProps<{ code?: ErrorCode; reference?: string }>(), { code: 404 })

const router = useRouter()

const PAGES: Record<ErrorCode, { title: string; message: string; icon: string; iconBg: string; iconColor: string; retry: boolean }> = {
  400: {
    title: 'Não foi possível abrir isto',
    message: 'O link ou os dados enviados estão incompletos ou em um formato inválido. Volte e tente pelo menu do app.',
    icon: 'M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z',
    iconBg: 'bg-amber-50', iconColor: 'text-amber-500', retry: false,
  },
  403: {
    title: 'Acesso não permitido',
    message: 'Você não tem permissão para ver isto neste espaço. Peça ao dono do workspace para liberar o acesso ou troque de espaço.',
    icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    iconBg: 'bg-rose-50', iconColor: 'text-rose-500', retry: false,
  },
  404: {
    title: 'Página não encontrada',
    message: 'O endereço que você abriu não existe ou foi movido.',
    icon: 'M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    iconBg: 'bg-indigo-50', iconColor: 'text-indigo-500', retry: false,
  },
  500: {
    title: 'Algo deu errado do nosso lado',
    message: 'O erro já foi registrado para análise e seus dados estão seguros. Tente novamente em alguns instantes.',
    icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    iconBg: 'bg-rose-50', iconColor: 'text-rose-500', retry: true,
  },
  503: {
    title: 'Sem conexão com o servidor',
    message: 'Não conseguimos falar com o servidor. Verifique sua internet; se ela estiver normal, o sistema pode estar em manutenção.',
    icon: 'M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3',
    iconBg: 'bg-gray-100', iconColor: 'text-gray-500', retry: true,
  },
}

const content = computed(() => PAGES[props.code] ?? PAGES[404])
const homePath = computed(() => (localStorage.getItem('user') ? '/dashboard' : '/'))
const canGoBack = computed(() => window.history.length > 1 && !content.value.retry)

function retry() {
  // Recarrega a rota atual do zero (a URL não muda quando a tela de erro é exibida).
  window.location.reload()
}

function goBack() {
  clearError()
  router.back()
}
</script>
