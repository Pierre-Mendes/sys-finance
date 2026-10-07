<template>
  <main class="min-h-screen flex flex-col items-center justify-center bg-ink-50 px-4 py-12">
    <div class="w-full max-w-lg text-center animate-fade-up">
      <BrandLogo class="mx-auto mb-8 justify-center" :size="32" />

      <CapiMascot class="mx-auto" :mood="content.mood" :size="140" />

      <p class="mt-4 text-xs font-bold uppercase tracking-widest text-ink-500">Erro {{ code }}</p>
      <h1 class="mt-1 text-3xl font-extrabold text-ink-900">{{ content.title }}</h1>
      <p class="mt-3 text-ink-500 leading-relaxed">{{ content.message }}</p>

      <p v-if="reference" class="mt-4 inline-block rounded-lg bg-white border border-gray-200 px-3 py-1.5 text-xs text-ink-500">
        Código para o suporte: <span class="font-mono font-semibold text-ink-900">{{ reference }}</span>
      </p>

      <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
        <GenericButton v-if="content.retry" @click="retry">Tentar novamente</GenericButton>
        <GenericButton v-if="canGoBack" variant="secondary" @click="goBack">Voltar</GenericButton>
        <GenericButton v-if="code === 403" variant="secondary" @click="go('/workspaces')">Trocar de espaço</GenericButton>
        <GenericButton :variant="content.retry ? 'ghost' : 'primary'" @click="go(homePath)">Ir para o início</GenericButton>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import BrandLogo from '@/components/brand/BrandLogo.vue'
import CapiMascot, { type CapiMood } from '@/components/brand/CapiMascot.vue'
import GenericButton from '@/components/ui/GenericButton.vue'
import { clearError, type ErrorCode } from '@/core/errors/appError'

const props = withDefaults(defineProps<{ code?: ErrorCode; reference?: string }>(), { code: 404 })

const router = useRouter()

const PAGES: Record<ErrorCode, { title: string; message: string; mood: CapiMood; retry: boolean }> = {
  400: {
    title: 'Não foi possível abrir isto',
    message: 'O link ou os dados enviados estão incompletos ou em um formato inválido. Volte e tente pelo menu do app.',
    mood: 'thinking', retry: false,
  },
  403: {
    title: 'Acesso não permitido',
    message: 'Você não tem permissão para ver isto neste espaço. Peça ao dono do workspace para liberar o acesso ou troque de espaço.',
    mood: 'alert', retry: false,
  },
  404: {
    title: 'Página não encontrada',
    message: 'O endereço que você abriu não existe ou foi movido.',
    mood: 'thinking', retry: false,
  },
  500: {
    title: 'Algo deu errado do nosso lado',
    message: 'O erro já foi registrado para análise e seus dados estão seguros. Tente novamente em alguns instantes.',
    mood: 'alert', retry: true,
  },
  503: {
    title: 'Sem conexão com o servidor',
    message: 'Não conseguimos falar com o servidor. Verifique sua internet; se ela estiver normal, o sistema pode estar em manutenção.',
    mood: 'sleeping', retry: true,
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

function go(path: string) {
  clearError()
  router.push(path)
}
</script>
