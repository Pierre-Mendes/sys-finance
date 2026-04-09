<template>
  <MainLayout>
    <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
      <div>
        <h2 class="text-3xl font-bold text-gray-800">Visão Geral</h2>
        <p class="text-gray-500 mt-1 sm:text-lg">Aqui está o resumo da sua vida financeira.</p>
      </div>
      <div class="flex items-center gap-3">
          <label class="flex items-center cursor-pointer mr-2 border border-gray-200 bg-white px-3 py-1.5 rounded-lg shadow-sm">
              <div class="relative">
                  <input type="checkbox" v-model="view360" @change="fetchAnalytics" class="sr-only" />
                  <div class="block bg-gray-200 w-12 h-6 rounded-full transition-colors" :class="{'bg-indigo-500': view360}"></div>
                  <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform" :class="{'transform translate-x-6': view360}"></div>
              </div>
              <div class="ml-3 font-medium flex flex-col">
                  <span class="text-sm text-gray-800" :class="{'font-bold text-indigo-700': view360}">Visão 360</span>
              </div>
          </label>

          <button @click="fetchAnalytics" :disabled="isRefreshing" class="text-sm font-medium text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 py-2.5 px-5 rounded-lg flex items-center gap-2 cursor-pointer transition disabled:opacity-50 disabled:cursor-not-allowed">
            <svg :class="{'animate-spin': isRefreshing}" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            <span class="hidden sm:inline">{{ isRefreshing ? 'Atualizando...' : 'Atualizar Painel' }}</span>
          </button>
      </div>
    </div>

    <!-- Metricas Principais -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        
        <!-- Saldo Atual -->
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-6 shadow-lg shadow-blue-500/30 text-white flex flex-col justify-between transform transition hover:-translate-y-1">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-blue-100 font-medium text-sm lg:text-base uppercase tracking-wider mb-1 flex items-center gap-1">
                        Saldo Atual
                        <div class="group relative cursor-help flex outline-none" tabindex="0">
                            <svg class="w-4 h-4 text-blue-300 hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="absolute z-50 left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block group-focus:block w-max max-w-[250px] whitespace-normal bg-gray-900 font-normal text-white text-xs tracking-normal rounded px-3 py-2 text-center shadow-lg before:content-[''] before:absolute before:border-4 before:border-transparent before:border-t-gray-900 before:-bottom-2 before:left-1/2 before:-translate-x-1/2">
                                R$ {{ formatCurrency(analytics.balance) }}
                            </div>
                        </div>
                    </h3>
                    <p class="text-3xl lg:text-4xl font-bold truncate pr-2">R$ {{ formatCurrency(analytics.balance) }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-full backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="text-sm font-medium text-blue-100">Somatório de todas as suas contas bancárias cadastradas.</div>
        </div>

        <!-- Receitas Card -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between transform transition hover:-translate-y-1">
            <div class="flex items-start justify-between mb-4">
                <div class="w-full">
                    <h3 class="text-gray-500 font-medium text-sm uppercase tracking-wider mb-1 flex items-center gap-1">
                        Total Receitas
                        <div class="group relative cursor-help flex outline-none" tabindex="0">
                            <svg class="w-4 h-4 text-gray-400 hover:text-indigo-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="absolute z-50 left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block group-focus:block w-max max-w-[250px] whitespace-normal bg-gray-800 font-normal text-white text-xs tracking-normal rounded px-3 py-2 text-center shadow-lg before:content-[''] before:absolute before:border-4 before:border-transparent before:border-t-gray-800 before:-bottom-2 before:left-1/2 before:-translate-x-1/2">
                                R$ {{ formatCurrency(analytics.totalIncome) }}
                            </div>
                        </div>
                    </h3>
                    <p class="text-3xl font-bold text-gray-800 truncate pr-2">R$ {{ formatCurrency(analytics.totalIncome) }}</p>
                </div>
                <div class="bg-green-50 p-3 rounded-full text-green-500 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
            </div>
            <div class="text-sm font-medium text-green-500 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Todas as entradas registradas
            </div>
        </div>

        <!-- Despesas Card -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between transform transition hover:-translate-y-1">
            <div class="flex items-start justify-between mb-4">
                <div class="w-full">
                    <h3 class="text-gray-500 font-medium text-sm uppercase tracking-wider mb-1 flex items-center gap-1">
                        Total Despesas
                        <div class="group relative cursor-help flex outline-none" tabindex="0">
                            <svg class="w-4 h-4 text-gray-400 hover:text-indigo-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="absolute z-50 left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block group-focus:block w-max max-w-[250px] whitespace-normal bg-gray-800 font-normal text-white text-xs tracking-normal rounded px-3 py-2 text-center shadow-lg before:content-[''] before:absolute before:border-4 before:border-transparent before:border-t-gray-800 before:-bottom-2 before:left-1/2 before:-translate-x-1/2">
                                R$ {{ formatCurrency(analytics.totalExpense) }}
                            </div>
                        </div>
                    </h3>
                    <p class="text-3xl font-bold text-gray-800 truncate pr-2">R$ {{ formatCurrency(analytics.totalExpense) }}</p>
                </div>
                <div class="bg-red-50 p-3 rounded-full text-red-500 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                </div>
            </div>
            <div class="text-sm font-medium text-red-500 flex items-center gap-1">
               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
               Todas as saídas registradas
            </div>
        </div>

    </div>

    <!-- Seção Secundária -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Saldo por Contas -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-lg font-bold text-gray-800">Desempenho por Banco</h3>
            </div>
            <div class="p-6 flex-1">
                <ul v-if="analytics.accounts.length > 0" class="space-y-4">
                    <li v-for="acc in analytics.accounts" :key="acc.name" class="flex justify-between items-center p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition">
                        <div class="flex items-center gap-3 w-1/2">
                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg flex-shrink-0">
                                {{ acc.name.charAt(0).toUpperCase() }}
                            </div>
                            <span class="font-medium text-gray-800 truncate" :title="acc.name">{{ acc.name }}</span>
                            <div class="group relative cursor-help flex outline-none" tabindex="0">
                                <svg class="w-4 h-4 text-gray-400 hover:text-indigo-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div class="absolute z-50 left-1/2 -translate-x-1/2 bottom-full mb-2 hidden group-hover:block group-focus:block w-max max-w-[200px] whitespace-normal bg-gray-800 text-white text-xs rounded px-3 py-2 text-center shadow-lg before:content-[''] before:absolute before:border-4 before:border-transparent before:border-t-gray-800 before:-bottom-2 before:left-1/2 before:-translate-x-1/2">
                                    {{ acc.name }}
                                </div>
                            </div>
                        </div>
                        <span class="font-bold text-gray-800 truncate" :title="'R$ ' + formatCurrency(acc.balance)">R$ {{ formatCurrency(acc.balance) }}</span>
                    </li>
                </ul>
                <div v-else class="text-center py-6 text-gray-500">
                    Nenhuma conta bancária registrada ainda.
                </div>
            </div>
        </div>

        <!-- Atalhos Rápidos -->
         <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-lg font-bold text-gray-800">Ações Rápidas</h3>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4 flex-1">
                <button @click="openModal = true" class="flex flex-col items-center justify-center p-6 w-full bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-xl transition cursor-pointer text-center group">
                    <svg class="w-10 h-10 mb-3 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    <span class="font-semibold text-sm sm:text-base">Lançar Nova Transação</span>
                </button>
                <router-link to="/accounts" class="flex flex-col items-center justify-center p-6 bg-purple-50 hover:bg-purple-100 text-purple-600 rounded-xl transition cursor-pointer text-center group">
                    <svg class="w-10 h-10 mb-3 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    <span class="font-semibold text-sm sm:text-base">Gerenciar Carteiras</span>
                </router-link>
            </div>
         </div>
    </div>
    
    <!-- Modal Injetado no Dashboard -->
    <TransactionModal 
        v-model="openModal" 
        :transaction-to-edit="null" 
        @saved="fetchAnalytics" 
    />
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import MainLayout from '@/components/layout/MainLayout.vue'
import TransactionModal from '@/presentation/components/domain/TransactionModal.vue'
import api from '@/data/api/HttpClient'
import { useWorkspaceStore } from '@/presentation/store/workspaceStore'

const router = useRouter()
const workspaceStore = useWorkspaceStore()

const isRefreshing = ref(false)
const view360 = ref(workspaceStore.isView360)
const openModal = ref(false)

const analytics = ref({
    totalIncome: 0,
    totalExpense: 0,
    balance: 0,
    accounts: [] as any[]
})

const formatCurrency = (val: number) => {
    return Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const fetchAnalytics = async () => {
    isRefreshing.value = true;
    workspaceStore.setView360(view360.value) // Memoriza escolha
    try {
        const response = await api.get(`/api/dashboard?view360=${view360.value}`)
        analytics.value = response.data.data
    } catch (e: any) {
        if (e.response?.status === 401) { 
            toast.error('Sessão expirada.');
            router.push('/'); 
            return;
        }
        toast.error('Erro ao carregar dados do painel.')
    } finally {
        setTimeout(() => isRefreshing.value = false, 500);
    }
}

onMounted(() => {
    fetchAnalytics()
})
</script>
