<template>
  <section v-if="items.length > 0" ref="root" id="overdue-review"
           :class="['mb-8 bg-white rounded-2xl border shadow-sm overflow-hidden', highlight ? 'border-red-300 ring-2 ring-red-200' : 'border-red-100']">
    <header class="px-5 py-4 border-b border-gray-100 flex items-start gap-3">
      <span class="mt-0.5 w-8 h-8 rounded-lg bg-red-100 text-red-700 flex items-center justify-center font-bold" aria-hidden="true">!</span>
      <div class="flex-1">
        <h3 class="font-bold text-gray-800">Contas atrasadas</h3>
        <p class="text-xs text-gray-500">
          {{ items.length }} {{ items.length === 1 ? 'conta pendente já venceu' : 'contas pendentes já venceram' }}.
          Diga o que aconteceu com cada uma para o saldo e a previsão ficarem certos.
        </p>
      </div>
    </header>
    <ul class="divide-y divide-gray-100">
      <li v-for="t in items.slice(0, showAll ? items.length : 5)" :key="`${t.type}-${t.id}`" class="px-5 py-3">
        <div class="flex items-start gap-3">
          <div class="flex-1 min-w-0">
            <p class="font-medium text-gray-800 truncate">{{ t.title }}</p>
            <p class="text-xs text-gray-500 mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1">
              <span>Venceu em {{ formatDate(t.dueDate) }} · há {{ -t.daysLeft }} {{ -t.daysLeft === 1 ? 'dia' : 'dias' }}</span>
              <span v-if="-t.daysLeft > STALE_AFTER_DAYS" class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold">Esquecida? Fora da previsão</span>
              <span v-if="t.type === 'asset'" class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-semibold">A receber</span>
            </p>
          </div>
          <p :class="['font-semibold whitespace-nowrap', t.type === 'asset' ? 'text-emerald-700' : 'text-gray-900']">{{ formatBRL(t.amount) }}</p>
        </div>
        <div class="mt-2 flex flex-wrap gap-2">
          <button type="button" @click="markPaid(t)" :disabled="busy" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 disabled:opacity-50">
            {{ t.type === 'asset' ? 'Já recebi' : 'Já paguei' }}
          </button>
          <button type="button" @click="reschedule(t)" :disabled="busy" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 disabled:opacity-50">Reagendar</button>
          <button type="button" @click="cancel(t)" :disabled="busy" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 disabled:opacity-50">Desconsiderar</button>
        </div>
      </li>
    </ul>
    <button v-if="items.length > 5" type="button" @click="showAll = !showAll" class="w-full py-3 text-sm font-medium text-primary border-t border-gray-100">
      {{ showAll ? 'Mostrar menos' : `Ver todas (${items.length})` }}
    </button>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue3-toastify'
import Swal from 'sweetalert2'
import { transactionRepository } from '@/data/repositories/TransactionRepositoryImpl'
import { daysUntil } from '@/core/domain/dueDates'
import { formatBRL } from '@/core/domain/money'

/** Mesmo limite do backend (BillReminderService::STALE_AFTER_DAYS). */
const STALE_AFTER_DAYS = 30
const emit = defineEmits<{ (e: 'changed'): void }>()
const route = useRoute()

const all = ref<any[]>([])
const busy = ref(false)
const showAll = ref(false)
const highlight = ref(false)
const root = ref<HTMLElement | null>(null)

const items = computed(() => all.value)

const load = async () => {
  try {
    const list = await transactionRepository.getTransactions()
    all.value = (list as any[])
      .filter(t => t.status === 'PENDING')
      .map(t => ({ ...t, dueDate: t.dueDate || t.date, daysLeft: daysUntil(t.dueDate || t.date) }))
      .filter(t => t.daysLeft < 0)
      .sort((a, b) => a.daysLeft - b.daysLeft)
  } catch {
    all.value = []
  }
}

const done = async (message: string) => {
  toast.success(message)
  await load()
  emit('changed')
}

const run = async (action: () => Promise<unknown>, message: string) => {
  busy.value = true
  try {
    await action()
    await done(message)
  } catch (e: any) {
    toast.error(e.response?.data?.error || 'Não foi possível concluir.')
  } finally {
    busy.value = false
  }
}

const markPaid = (t: any) => run(() => transactionRepository.payTransaction(t.id, true, t.type), t.type === 'asset' ? 'Marcada como recebida.' : 'Marcada como paga.')

const reschedule = async (t: any) => {
  const tomorrow = new Date(Date.now() + 86_400_000)
  const min = `${tomorrow.getFullYear()}-${String(tomorrow.getMonth() + 1).padStart(2, '0')}-${String(tomorrow.getDate()).padStart(2, '0')}`
  const { value, isConfirmed } = await Swal.fire({
    title: 'Nova data de vencimento',
    text: t.title, // texto puro: o título é dado do usuário
    input: 'date',
    inputAttributes: { min },
    inputValue: min,
    showCancelButton: true,
    confirmButtonText: 'Reagendar',
    cancelButtonText: 'Cancelar',
    inputValidator: (v: string) => (!v ? 'Escolha uma data.' : undefined),
  })
  if (isConfirmed && value) await run(() => transactionRepository.rescheduleTransaction(t.id, t.type, value), 'Conta reagendada.')
}

const cancel = async (t: any) => {
  const recurring = t.recurrence_type && t.recurrence_type !== 'NONE'
  const { isConfirmed } = await Swal.fire({
    title: 'Desconsiderar esta conta?',
    text: `"${t.title}" fica no histórico como desconsiderada e deixa de contar no saldo previsto e nos lembretes.${recurring ? ' A próxima ocorrência continua.' : ''}`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Desconsiderar',
    cancelButtonText: 'Voltar',
  })
  if (isConfirmed) await run(() => transactionRepository.cancelTransaction(t.id, t.type), 'Conta desconsiderada.')
}

const formatDate = (d: string) => d.slice(0, 10).split('-').reverse().join('/')

onMounted(async () => {
  await load()
  // Link do aviso mensal: /dashboard?review=overdue
  if (route.query.review === 'overdue' && all.value.length) {
    highlight.value = true
    await nextTick()
    root.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
})
defineExpose({ reload: load })
</script>
