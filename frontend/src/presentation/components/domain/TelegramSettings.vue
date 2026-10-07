<template>
  <section class="mt-8 w-full max-w-4xl bg-white rounded-2xl shadow-lg shadow-gray-200 border border-gray-100 overflow-hidden text-gray-800">
    <header class="px-6 py-5 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
      <span class="w-9 h-9 rounded-xl bg-brand-600 text-white flex items-center justify-center shrink-0" aria-hidden="true">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
      </span>
      <div>
        <h3 class="font-semibold text-lg">Telegram</h3>
        <p class="text-sm text-gray-500">Lance gastos e consulte saldo conversando com o bot.</p>
      </div>
    </header>

    <div class="p-6 md:p-8">
      <div v-if="loading" class="text-sm text-gray-500">Carregando…</div>

      <p v-else-if="!status.enabled" class="text-sm text-gray-600">
        O bot ainda não foi configurado no servidor. Quem administra o sistema precisa seguir o guia
        <code class="bg-gray-100 px-1 rounded">docs/guides/telegram.md</code>.
      </p>

      <!-- Vinculado -->
      <div v-else-if="status.linked" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <p class="font-semibold text-income">✅ Conectado{{ status.telegramUsername ? ` como @${status.telegramUsername}` : '' }}</p>
          <p class="text-sm text-gray-500">Converse com <a :href="botUrl" target="_blank" rel="noopener" class="text-brand-600 font-medium hover:underline">@{{ status.botUsername }}</a>. Avisos de contas a vencer também chegam por lá.</p>
        </div>
        <GenericButton variant="danger" class="shrink-0" :loading="busy" @click="disconnect">Desconectar</GenericButton>
      </div>

      <!-- Código gerado, aguardando o /start -->
      <div v-else-if="link" class="space-y-4">
        <p class="text-sm text-gray-600">Toque no botão para abrir o bot e depois em <b>Iniciar</b>. Se abrir em outro aparelho, envie ao bot:</p>
        <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
          <a :href="link.deepLink" target="_blank" rel="noopener" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold px-5 py-3 rounded-lg text-center">Abrir no Telegram</a>
          <button type="button" @click="copyCode" class="font-mono text-lg tracking-widest bg-gray-100 hover:bg-gray-200 px-4 py-2.5 rounded-lg" title="Copiar" aria-label="Copiar comando com o código">/start {{ link.code }}</button>
        </div>
        <p class="text-xs text-ink-500" role="status">Aguardando a confirmação… o código vale por 15 minutos.</p>
      </div>

      <!-- Não vinculado -->
      <div v-else class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="text-sm text-gray-600 space-y-1">
          <p>Depois de conectar, é só mandar mensagens como:</p>
          <p><code class="bg-gray-100 px-1.5 py-0.5 rounded">mercado 120 nubank</code> · <code class="bg-gray-100 px-1.5 py-0.5 rounded">recebi 3000 salário</code> · <code class="bg-gray-100 px-1.5 py-0.5 rounded">/saldo</code></p>
        </div>
        <GenericButton class="shrink-0" :loading="busy" @click="connect">Conectar Telegram</GenericButton>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { toast } from 'vue3-toastify'
import api from '@/data/api/HttpClient'
import GenericButton from '@/components/ui/GenericButton.vue'

interface TelegramStatus {
  enabled: boolean
  botUsername: string | null
  linked: boolean
  telegramUsername: string | null
}

const status = ref<TelegramStatus>({ enabled: false, botUsername: null, linked: false, telegramUsername: null })
const link = ref<{ code: string; deepLink: string } | null>(null)
const loading = ref(true)
const busy = ref(false)
let poll: ReturnType<typeof setInterval> | null = null

const botUrl = computed(() => `https://t.me/${status.value.botUsername}`)

async function load() {
  const { data } = await api.get('/api/telegram', { skipErrorPage: true })
  status.value = data
}

function stopPolling() {
  if (poll) clearInterval(poll)
  poll = null
}

async function connect() {
  busy.value = true
  try {
    const { data } = await api.post('/api/telegram/link')
    link.value = data
    // Confere a cada 3 s se o /start chegou (para por si só quando o código expira).
    const startedAt = Date.now()
    stopPolling()
    poll = setInterval(async () => {
      if (Date.now() - startedAt > 15 * 60 * 1000) { stopPolling(); link.value = null; return }
      try {
        await load()
        if (status.value.linked) {
          stopPolling()
          link.value = null
          toast.success('Telegram conectado!')
        }
      } catch { /* tenta de novo no próximo ciclo */ }
    }, 3000)
  } catch (e: any) {
    toast.error(e.response?.data?.error || 'Não foi possível gerar o código.')
  } finally {
    busy.value = false
  }
}

async function disconnect() {
  busy.value = true
  try {
    await api.delete('/api/telegram/link')
    await load()
    toast.info('Telegram desconectado.')
  } catch (e: any) {
    toast.error(e.response?.data?.error || 'Não foi possível desconectar.')
  } finally {
    busy.value = false
  }
}

async function copyCode() {
  if (!link.value) return
  try {
    await navigator.clipboard.writeText(`/start ${link.value.code}`)
    toast.info('Copiado! Cole na conversa com o bot.')
  } catch {
    toast.info(`Envie ao bot: /start ${link.value.code}`)
  }
}

onMounted(async () => {
  try { await load() } catch { /* seção fica no estado "não configurado" */ } finally { loading.value = false }
})
onUnmounted(stopPolling)
</script>
