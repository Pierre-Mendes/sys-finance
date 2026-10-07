<template>
  <div v-if="visible" class="mb-8 bg-brand-50 border border-brand-100 rounded-2xl p-5 shadow-sm">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h3 class="text-lg font-bold text-gray-800">Primeiros passos</h3>
        <p class="text-sm text-gray-600 mt-1">
          Não precisa cadastrar tudo antes: ao lançar uma despesa ou compra, digite o nome da conta ou categoria
          e nós criamos para você.
        </p>
      </div>
      <button @click="dismiss" class="text-gray-500 hover:text-gray-600 text-sm" aria-label="Dispensar">✕</button>
    </div>

    <ul class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
      <li v-for="step in steps" :key="step.key"
          class="flex items-center gap-3 bg-white rounded-xl border p-3"
          :class="step.done ? 'border-emerald-200' : 'border-gray-200'">
        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
              :class="step.done ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-500'">
          {{ step.done ? '✓' : step.index }}
        </span>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-gray-800">{{ step.title }}</p>
          <p class="text-xs text-gray-500 truncate">{{ step.done ? step.doneText : step.hint }}</p>
        </div>
        <button v-if="!step.done" @click="step.action()"
                class="text-xs font-semibold text-primary hover:underline shrink-0">{{ step.cta }}</button>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/data/api/HttpClient'

interface SetupStatus {
  accounts: number
  creditCards: number
  hasAccount: boolean
  hasCreditCard: boolean
  hasCategories: boolean
}

const emit = defineEmits<{ (e: 'new-transaction'): void }>()
const router = useRouter()

const DISMISS_KEY = 'setupChecklistDismissed'
const status = ref<SetupStatus | null>(null)
const dismissed = ref(false)

try { dismissed.value = localStorage.getItem(DISMISS_KEY) === '1' } catch { /* storage indisponível */ }

const steps = computed(() => {
  const s = status.value
  return [
    {
      key: 'transaction', index: 1, title: 'Registre um lançamento', done: !!s?.hasAccount && !!s?.hasCategories,
      hint: 'Conta e categoria são criadas na hora', doneText: `${s?.accounts ?? 0} conta(s) configurada(s)`,
      cta: 'Lançar', action: () => emit('new-transaction'),
    },
    {
      key: 'card', index: 2, title: 'Adicione um cartão', done: !!s?.hasCreditCard,
      hint: 'Opcional — vincule ou crie a conta junto', doneText: `${s?.creditCards ?? 0} cartão(ões)`,
      cta: 'Adicionar', action: () => router.push('/credit-cards'),
    },
    {
      key: 'import', index: 3, title: 'Importe um extrato', done: false,
      hint: 'PDF, CSV ou OFX do seu banco', doneText: '',
      cta: 'Importar', action: () => router.push('/transactions'),
    },
  ]
})

// Só aparece enquanto o workspace ainda está "vazio" (sem conta ou sem categoria).
const visible = computed(() => !dismissed.value && status.value !== null && !(status.value.hasAccount && status.value.hasCategories))

const refresh = async () => {
  try {
    const { data } = await api.get('/api/setup/status')
    status.value = data.data
  } catch {
    status.value = null
  }
}

const dismiss = () => {
  dismissed.value = true
  try { localStorage.setItem(DISMISS_KEY, '1') } catch { /* storage indisponível */ }
}

defineExpose({ refresh })
onMounted(refresh)
</script>
