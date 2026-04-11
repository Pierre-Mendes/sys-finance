<template>
  <MainLayout>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
      <div>
        <h2 class="text-3xl font-black text-gray-900 tracking-tight">Extrato Bancário</h2>
        <p class="text-gray-500 mt-1 font-medium">Timeline detalhada de todas as movimentações da conta.</p>
      </div>
      
      <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
        <select v-model="selectedAccountId" @change="fetchStatement" class="w-full sm:w-64 p-3 bg-white border-2 border-gray-100 rounded-2xl outline-none focus:border-indigo-500 transition-all font-bold shadow-sm">
          <option :value="null" disabled>Selecione uma conta...</option>
          <option v-for="acc in accountStore.accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
        </select>
        
        <button @click="fetchStatement" class="p-3 bg-gray-900 text-white rounded-2xl hover:bg-gray-800 transition shadow-lg shadow-gray-200" :disabled="!selectedAccountId || isLoading">
            <svg v-if="!isLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            <svg v-else class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!selectedAccountId" class="bg-white rounded-[2.5rem] p-20 text-center border-2 border-dashed border-gray-100">
        <div class="w-24 h-24 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-12 h-12 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Selecione uma conta para começar</h3>
        <p class="text-gray-500 max-w-sm mx-auto">Escolha qualquer uma de suas contas bancárias para visualizar o histórico completo de transações e a evolução do saldo.</p>
    </div>

    <div v-else-if="isLoading" class="space-y-4">
        <div v-for="i in 5" :key="i" class="h-24 bg-gray-50 rounded-3xl animate-pulse"></div>
    </div>

    <div v-else-if="statement.length === 0" class="bg-white rounded-[2.5rem] p-20 text-center border border-gray-50">
        <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">Sem movimentações recentes</p>
    </div>

    <!-- Statement Table -->
    <div v-else class="bg-white rounded-[2.5rem] shadow-sm border border-gray-50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50/50 text-gray-400 text-[10px] font-black uppercase tracking-widest border-b border-gray-50">
                    <tr>
                        <th class="px-8 py-5">Data</th>
                        <th class="px-8 py-5">Movimentação</th>
                        <th class="px-8 py-5">Categoria</th>
                        <th class="px-8 py-5 text-right">Valor</th>
                        <th class="px-8 py-5 text-right bg-indigo-50/30 text-indigo-500">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="item in statement" :key="item.type + item.id" class="hover:bg-gray-50/50 transition-colors group">
                        <td class="px-8 py-6">
                            <span class="text-gray-900 font-bold">{{ formatDate(item.date) }}</span>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex flex-col">
                                <span class="text-gray-900 font-black text-sm">{{ item.title }}</span>
                                <span v-if="item.status === 'PENDING'" class="text-[9px] font-black text-amber-500 uppercase tracking-tighter">Pendente</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <span class="px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-[10px] font-black uppercase tracking-tight">
                                {{ item.categoryName }}
                            </span>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <span :class="['font-black text-lg', item.type === 'asset' ? 'text-emerald-500' : 'text-rose-500']">
                                {{ item.type === 'asset' ? '+' : '-' }} R$ {{ formatCurrency(item.amount) }}
                            </span>
                        </td>
                        <td class="px-8 py-6 text-right bg-indigo-50/10 group-hover:bg-indigo-50/30 transition-colors">
                            <span class="font-black text-indigo-600">R$ {{ formatCurrency(item.runningBalance) }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import MainLayout from '@/components/layout/MainLayout.vue'
import { useAccountStore } from '@/presentation/store/accountStore'
import api from '@/data/api/HttpClient'
import { toast } from 'vue3-toastify'

const route = useRoute()
const accountStore = useAccountStore()
const selectedAccountId = ref<number | null>(null)
const statement = ref<any[]>([])
const isLoading = ref(false)

const formatCurrency = (val: number) => {
    return Number(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (dateStr: string) => {
    if (!dateStr) return '-'
    const [year, month, day] = dateStr.split('T')[0].split('-')
    return `${day}/${month}/${year}`
}

const fetchStatement = async () => {
  if (!selectedAccountId.value) return
  isLoading.value = true
  try {
    const { data } = await api.get(`/api/accounts/${selectedAccountId.value}/statement`)
    if (data.success) {
      statement.value = data.data
    } else {
      toast.error(data.error || 'Erro ao carregar extrato.')
    }
  } catch (error) {
    console.error('Failed to fetch statement', error)
    toast.error('Falha na comunicação com o servidor.')
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  if (accountStore.accounts.length === 0) {
    await accountStore.fetchAccounts()
  }
  
  // Check if accountId came through query (from Goals history link)
  const queryId = route.query.accountId
  if (queryId) {
    selectedAccountId.value = Number(queryId)
    fetchStatement()
  }
})
</script>
