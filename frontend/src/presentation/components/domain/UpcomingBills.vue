<template>
  <section v-if="bills.length > 0" class="mb-8 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <header class="px-5 py-4 flex items-center justify-between border-b border-gray-100">
      <div>
        <h3 class="font-bold text-gray-800">Contas a vencer</h3>
        <p class="text-xs text-gray-500">Próximos {{ DAYS_AHEAD }} dias · R$ {{ formatCurrency(total) }}</p>
      </div>
      <router-link :to="{ path: '/transactions', query: { status: 'PENDING' } }" class="text-sm font-medium text-primary whitespace-nowrap">Ver todas</router-link>
    </header>
    <ul class="divide-y divide-gray-100">
      <li v-for="bill in bills.slice(0, 5)" :key="bill.id" class="px-5 py-3 flex items-center gap-3">
        <div class="flex-1 min-w-0">
          <p class="font-medium text-gray-800 truncate">{{ bill.title }}</p>
          <p class="text-xs text-gray-500 flex items-center gap-2 mt-1 min-w-0">
            <span :class="['font-bold uppercase px-1.5 py-0.5 rounded whitespace-nowrap', badgeClass(bill.daysLeft)]">{{ dueLabel(bill.daysLeft) }}</span>
            <span class="truncate">{{ formatDate(bill.dueDate) }}<template v-if="bill.accountName"> · {{ bill.accountName }}</template></span>
          </p>
        </div>
        <p class="font-semibold text-gray-800 whitespace-nowrap">R$ {{ formatCurrency(bill.amount) }}</p>
        <button @click="pay(bill)" :disabled="payingId === bill.id" class="px-3 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg disabled:opacity-50">
          Pagar
        </button>
      </li>
    </ul>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import { useCelebrationStore } from '@/presentation/store/celebrationStore'
import Swal from 'sweetalert2'
import { transactionRepository } from '@/data/repositories/TransactionRepositoryImpl'
import { daysUntil, dueLabel } from '@/core/domain/dueDates'

const DAYS_AHEAD = 7
const emit = defineEmits<{ (e: 'paid'): void }>()

const bills = ref<any[]>([])
const payingId = ref<number | null>(null)

const total = computed(() => bills.value.reduce((sum, b) => sum + Number(b.amount), 0))

const load = async () => {
  try {
    const all = await transactionRepository.getTransactions()
    bills.value = (all as any[])
      .filter(t => t.type === 'bill' && t.status === 'PENDING' && t.dueDate)
      .map(t => ({ ...t, daysLeft: daysUntil(t.dueDate) }))
      .filter(t => t.daysLeft >= 0 && t.daysLeft <= DAYS_AHEAD)
      .sort((a, b) => a.daysLeft - b.daysLeft)
  } catch {
    bills.value = []
  }
}

const pay = async (bill: any) => {
  const result = await Swal.fire({
    title: 'Marcar como paga?',
    text: `${bill.title} · R$ ${formatCurrency(bill.amount)}`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#0E7A55',
    confirmButtonText: 'Sim, paguei',
    cancelButtonText: 'Cancelar',
  })
  if (!result.isConfirmed) return

  payingId.value = bill.id
  try {
    await transactionRepository.payTransaction(bill.id, true, 'bill')
    useCelebrationStore().celebrate('Conta paga!', `${bill.title} · R$ ${formatCurrency(bill.amount)}`)
    await load()
    emit('paid')
  } catch (e: any) {
    toast.error(e.response?.data?.error || 'Não foi possível dar baixa.')
  } finally {
    payingId.value = null
  }
}

const badgeClass = (days: number) =>
  days < 0 ? 'bg-red-100 text-red-700' : days === 0 ? 'bg-amber-100 text-amber-800' : days === 1 ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600'

const formatDate = (d: string) => d.slice(0, 10).split('-').reverse().slice(0, 2).join('/')
const formatCurrency = (v: number) => Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

defineExpose({ reload: load })
onMounted(load)
</script>
