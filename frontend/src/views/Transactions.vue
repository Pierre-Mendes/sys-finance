<template>
  <MainLayout>
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h2 class="text-2xl sm:text-3xl font-semibold text-gray-800">Lançamentos</h2>
        <div class="grid grid-cols-2 md:flex md:flex-row gap-3 w-full md:w-auto items-center">
          <button @click="showFilters = !showFilters" class="w-full md:w-auto px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary shadow-sm flex items-center justify-center gap-2 transition">
             <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
             Filtros {{ (filterType || filterStatus || searchQuery) ? '(Ativos)' : '' }}
          </button>
          
          <button @click="openImportModal" class="w-full sm:w-auto bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-5 py-2.5 rounded-lg font-medium shadow-sm transition cursor-pointer flex items-center justify-center gap-2 whitespace-nowrap">
            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>Importar PDF
          </button>

          <!-- No celular o "+" da barra inferior faz o mesmo -->
          <button @click="openCreateModal" class="hidden md:flex w-full sm:w-auto bg-primary hover:bg-blue-600 px-5 py-2.5 rounded-lg text-white font-medium shadow transition cursor-pointer items-center justify-center gap-2 whitespace-nowrap">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>Novo Lançamento
          </button>
        </div>
      </div>
      
      <!-- Expandable Filters Panel -->
      <div v-show="showFilters" class="bg-white border border-gray-200 rounded-xl p-4 mb-6 shadow-sm flex flex-col md:flex-row gap-4 items-end animate-fadeIn">
          <div class="flex-1 w-full">
              <label class="block text-xs font-medium text-gray-500 mb-1">Buscar por Texto</label>
              <div class="relative w-full">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                     <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                  </div>
                  <input v-model="searchQuery" type="text" placeholder="Nome, conta ou categoria..." class="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-700 bg-white shadow-sm transition" />
              </div>
          </div>
          <div class="flex-1 w-full">
              <label class="block text-xs font-medium text-gray-500 mb-1">Tipo de Lançamento</label>
              <select v-model="filterType" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary shadow-sm text-gray-700 cursor-pointer">
                 <option value="">Todos (Receitas/Despesas)</option>
                 <option value="asset">Apenas Receitas</option>
                 <option value="bill">Apenas Despesas</option>
              </select>
          </div>
          <div class="flex-1 w-full">
              <label class="block text-xs font-medium text-gray-500 mb-1">Status de Pagamento</label>
              <select v-model="filterStatus" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary shadow-sm text-gray-700 cursor-pointer">
                 <option value="">Todos os Status</option>
                 <option value="PAID">Já Pagos</option>
                 <option value="PENDING">Pendentes</option>
              </select>
          </div>
          <div class="w-full md:w-auto">
              <button @click="clearFilters" class="w-full px-4 py-2 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg font-medium transition flex items-center justify-center gap-1.5 border border-transparent">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                  Limpar Filtros
              </button>
          </div>
      </div>

      <div class="hidden sm:flex bg-blue-50 border border-blue-200 rounded-xl p-4 gap-3 text-blue-800 text-sm mb-6 items-start shadow-sm">
         <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
         <div>
            <span class="font-bold block mb-1">O coração do fluxo financeiro</span>
            Adicione Receitas (dinheiro entrando) ou Despesas (gastos efetuados). Emita registros detalhando a Categoria afetada e a Conta bancária movimentada, gerando um histórico vital pro seu Dashboard.
         </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden w-full">
        <!-- Celular: cartões (a tabela cortava status e valor em telas estreitas) -->
        <ul class="md:hidden divide-y divide-gray-100">
          <li v-for="t in paginatedTransactions" :key="`m-${t.type}-${t.id}`" class="p-4 flex gap-3">
            <span :class="['mt-1.5 w-2.5 h-2.5 rounded-full flex-shrink-0', t.type === 'asset' ? 'bg-green-500' : 'bg-red-500']"></span>
            <div class="flex-1 min-w-0">
              <div class="flex justify-between items-start gap-3">
                <p class="font-medium text-gray-800 truncate">{{ t.title }}</p>
                <p :class="['font-semibold whitespace-nowrap', t.type === 'asset' ? 'text-green-600' : 'text-red-500']">
                  {{ t.type === 'asset' ? '+' : '-' }} R$ {{ formatCurrency(t.amount) }}
                </p>
              </div>
              <p class="text-xs text-gray-500 truncate mt-0.5">
                {{ formatDate(t.date) }}<template v-if="t.accountName"> · {{ t.accountName }}</template><template v-if="t.categoryName"> · {{ t.categoryName }}</template>
              </p>
              <div class="flex items-center justify-between gap-2 mt-2">
                <div class="flex items-center gap-2 min-w-0">
                  <span v-if="t.status === 'PAID'" class="px-2 py-0.5 text-[10px] font-bold bg-green-100 text-green-700 rounded-full uppercase">Pago</span>
                  <span v-else class="px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-700 rounded-full uppercase">Pendente</span>
                  <span v-if="t.dueDate && t.status !== 'PAID'" class="text-[11px] whitespace-nowrap" :class="isOverdue(t.dueDate) ? 'text-red-600 font-semibold' : 'text-gray-500'">
                    Vence {{ formatDate(t.dueDate).slice(0, 5) }}
                  </span>
                </div>
                <div class="flex items-center gap-1 flex-shrink-0">
                  <button v-if="t.status === 'PENDING'" @click="payTransaction(t)" class="px-3 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 rounded-lg active:bg-emerald-100">Pagar</button>
                  <button @click="openEditModal(t)" class="p-2 text-blue-500 rounded-lg active:bg-blue-50" aria-label="Editar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                  </button>
                  <button @click="deleteTransaction(t)" class="p-2 text-red-500 rounded-lg active:bg-red-50" aria-label="Excluir">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                  </button>
                </div>
              </div>
            </div>
          </li>
          <li v-if="isLoading" class="p-8 text-center text-sm text-gray-400">Carregando lançamentos...</li>
          <li v-else-if="paginatedTransactions.length === 0" class="p-8 text-center text-gray-500">Nenhum lançamento encontrado.</li>
        </ul>

        <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left min-w-[700px]">
          <thead class="bg-gray-50 text-gray-600 text-sm font-semibold uppercase border-b border-gray-100">
            <tr>
              <th class="p-4 cursor-pointer hover:bg-gray-100 transition group select-none" @click="toggleSort">
                  <div class="flex items-center gap-2">
                      Data
                      <svg v-if="sortOrder === 'desc'" class="w-4 h-4 text-gray-400 group-hover:text-primary transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                      <svg v-else class="w-4 h-4 text-gray-400 group-hover:text-primary transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                  </div>
              </th>
              <th class="p-4">Título</th>
              <th class="p-4">Status & Venc.</th>
              <th class="p-4">Conta</th>
              <th class="p-4">Categoria</th>
              <th class="p-4 text-right">Valor</th>
              <th class="p-4 w-40 text-center">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in paginatedTransactions" :key="`${t.type}-${t.id}`" class="border-b border-gray-50 hover:bg-gray-50 transition">
              <td class="p-4 text-gray-600 whitespace-nowrap">{{ formatDate(t.date) }}</td>
              <td class="p-4 text-gray-800 font-medium">
                  <div class="flex items-center gap-2">
                       <span :class="['w-2 h-2 rounded-full flex-shrink-0', t.type === 'asset' ? 'bg-green-500' : 'bg-red-500']"></span>
                      <div class="flex flex-col">
                          <span class="truncate max-w-[200px]" :title="t.title">{{ t.title }}</span>
                          <span v-if="t.priority && t.priority !== 'NORMAL'" class="text-[9px] uppercase tracking-wider mt-0.5" :class="t.priority === 'HIGH' ? 'text-red-500 font-bold' : 'text-gray-400'">Prioridade: {{ t.priority }}</span>
                      </div>
                  </div>
              </td>
              <td class="p-4">
                  <div class="flex flex-col gap-1 items-start">
                      <span v-if="t.status === 'PAID'" class="px-2 py-0.5 text-[10px] font-bold bg-green-100 text-green-700 rounded-full uppercase border border-green-200">Pago</span>
                      <span v-else class="px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-700 rounded-full uppercase border border-amber-200 shadow-sm animate-pulse-slow">Pendente</span>
                      
                      <span v-if="t.dueDate" class="text-[11px] font-medium whitespace-nowrap mt-0.5" :class="{'text-red-600 font-semibold': t.status !== 'PAID' && isOverdue(t.dueDate), 'text-gray-500': t.status === 'PAID' || !isOverdue(t.dueDate)}">
                          Vence: {{ formatDate(t.dueDate) }}
                      </span>
                  </div>
              </td>
              <td class="p-4 text-gray-600 truncate max-w-[150px]">{{ t.accountName }}</td>
              <td class="p-4 text-gray-600 truncate max-w-[150px]">{{ t.categoryName }}</td>
              <td :class="['p-4 text-right font-semibold whitespace-nowrap', t.type === 'asset' ? 'text-green-600' : 'text-red-500']">
                  {{ t.type === 'asset' ? '+' : '-' }} R$ {{ formatCurrency(t.amount) }}
              </td>
              <td class="p-4 text-center whitespace-nowrap">
                <div class="flex gap-4 justify-end sm:justify-center items-center">
                    <button v-if="t.status === 'PENDING'" @click="payTransaction(t)" class="text-emerald-600 hover:text-emerald-800 transition cursor-pointer font-medium flex items-center gap-1.5" title="Dar Baixa">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="hidden sm:inline text-sm">Baixa</span>
                    </button>
                    <button @click="openEditModal(t)" class="text-blue-500 hover:text-blue-700 transition cursor-pointer font-medium flex items-center gap-1.5" title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        <span class="hidden sm:inline text-sm">Editar</span>
                    </button>
                    <button @click="deleteTransaction(t)" class="text-red-500 hover:text-red-700 transition cursor-pointer font-medium flex items-center gap-1.5" title="Excluir">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        <span class="hidden sm:inline text-sm">Excluir</span>
                    </button>
                </div>
              </td>
            </tr>
            <TableLoader v-if="isLoading" :columns="6" message="CARREGANDO LANÇAMENTOS..." />
            <tr v-if="paginatedTransactions.length === 0 && !isLoading">
              <td colspan="6" class="p-8 text-center text-gray-500 flex-col items-center justify-center">
                  Nenhum lançamento encontrado.
              </td>
            </tr>
          </tbody>
        </table>
        </div>

        <!-- Pagination Controls -->
        <div class="p-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between text-sm text-gray-500 bg-gray-50/50 gap-4">
          <div class="flex flex-col sm:flex-row items-center gap-3">
            <span>Mostrando {{ filteredTransactions.length === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1 }} a {{ Math.min(currentPage * itemsPerPage, filteredTransactions.length) }} de {{ filteredTransactions.length }} registros</span>
            <div class="h-4 w-px bg-gray-300 hidden sm:block"></div>
            <select v-model="itemsPerPage" class="border border-gray-200 rounded px-2 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer text-gray-600 shadow-sm">
               <option :value="5">5 / pág</option>
               <option :value="10">10 / pág</option>
               <option :value="15">15 / pág</option>
               <option :value="50">50 / pág</option>
            </select>
          </div>
          <div class="flex items-center gap-4">
            <button @click="prevPage" :disabled="currentPage === 1" class="px-4 py-1.5 rounded-md border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition font-medium text-gray-600 shadow-sm">Anterior</button>
            <span class="font-medium text-gray-700">Pág {{ currentPage }} de {{ totalPages }}</span>
            <button @click="nextPage" :disabled="currentPage === totalPages" class="px-4 py-1.5 rounded-md border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition font-medium text-gray-600 shadow-sm">Próxima</button>
          </div>
        </div>
      </div>

    <!-- Modal de Importação PDF -->
    <StatementImportModal 
      v-model="openImportModalState"
      @imported="fetchData"
    />

    <!-- Modal Modularizado (Clean Arch) -->
    <TransactionModal 
      v-model="openModal" 
      :transaction-to-edit="transactionToEdit" 
      @saved="fetchData" 
    />
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import Swal from 'sweetalert2'
import MainLayout from '@/components/layout/MainLayout.vue'
import TableLoader from '@/components/ui/TableLoader.vue'
import TransactionModal from '@/presentation/components/domain/TransactionModal.vue'
import StatementImportModal from '@/presentation/components/domain/StatementImportModal.vue'
import { transactionRepository } from '@/data/repositories/TransactionRepositoryImpl'

const router = useRouter()
const route = useRoute()
const transactions = ref<any[]>([])

const isLoading = ref(true)

const openModal = ref(false)
const openImportModalState = ref(false)
const transactionToEdit = ref<any>(null)

const showFilters = ref(false)
const searchQuery = ref('')
const filterType = ref('')
const filterStatus = ref('')
const currentPage = ref(1)
const itemsPerPage = ref(10)
const sortOrder = ref('desc')

const filteredTransactions = computed(() => {
    let result = transactions.value.slice()
    
    if (filterType.value) {
        result = result.filter(t => t.type === filterType.value)
    }
    
    if (filterStatus.value) {
        result = result.filter(t => t.status === filterStatus.value)
    }

    if (searchQuery.value) {
        const lower = searchQuery.value.toLowerCase()
        result = result.filter(t => 
            t.title.toLowerCase().includes(lower) || 
            (t.accountName && t.accountName.toLowerCase().includes(lower)) ||
            (t.categoryName && t.categoryName.toLowerCase().includes(lower))
        )
    }
    result.sort((a, b) => {
        const dateA = new Date(a.date).getTime()
        const dateB = new Date(b.date).getTime()
        if (sortOrder.value === 'desc') return dateB - dateA
        return dateA - dateB
    })
    return result
})

const clearFilters = () => {
    searchQuery.value = ''
    filterType.value = ''
    filterStatus.value = ''
}

const totalPages = computed(() => {
    return Math.ceil(filteredTransactions.value.length / itemsPerPage.value) || 1
})

const paginatedTransactions = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value
    const end = start + itemsPerPage.value
    return filteredTransactions.value.slice(start, end)
})

watch([searchQuery, filterType, filterStatus, itemsPerPage, sortOrder], () => {
    currentPage.value = 1
})

const toggleSort = () => {
    sortOrder.value = sortOrder.value === 'desc' ? 'asc' : 'desc'
}

const nextPage = () => {
    if (currentPage.value < totalPages.value) currentPage.value++
}

const prevPage = () => {
    if (currentPage.value > 1) currentPage.value--
}

const isOverdue = (dateStr: string) => {
    if (!dateStr) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const due = new Date(dateStr);
    due.setHours(23, 59, 59, 999);
    return due < today;
}

const formatDate = (dateStr: string) => {
    if (!dateStr) return '-'
    const [year, month, day] = dateStr.split('T')[0].split('-')
    return `${day}/${month}/${year}`
}

const formatCurrency = (val: number) => {
    return Number(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const fetchData = async () => {
    isLoading.value = true;
    try {
        transactions.value = await transactionRepository.getTransactions()
    } catch (e: any) {
        if (e.response?.status === 401) { 
            toast.error('Sessão expirada.');
            router.push('/'); 
            return;
        }
        toast.error('Erro ao carregar lançamentos.')
    } finally {
        isLoading.value = false;
    }
}

// Links vindos de notificações (?status=PENDING) e do botão "+" da barra inferior (?new=1).
const applyRouteQuery = () => {
    const status = route.query.status
    if (status === 'PENDING' || status === 'PAID') {
        filterStatus.value = status
        showFilters.value = true
    }
    if (route.query.new === '1') {
        openCreateModal()
        const { new: _new, ...rest } = route.query
        router.replace({ query: rest })
    }
}

onMounted(() => {
    fetchData()
    applyRouteQuery()
})
watch(() => route.query, applyRouteQuery)

const openCreateModal = () => {
    transactionToEdit.value = null;
    openModal.value = true;
}

const openImportModal = () => {
    openImportModalState.value = true;
}

const openEditModal = (t: any) => {
    transactionToEdit.value = t;
    openModal.value = true;
}

const deleteTransaction = async (t: any) => {
    const result = await Swal.fire({
        title: 'Excluir Lançamento?',
        text: `Deseja realmente excluir o lançamento "${t.title}" no valor de R$ ${formatCurrency(t.amount)}? O saldo da conta será afetado reversamente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    });
    if (!result.isConfirmed) return;

    try {
        await transactionRepository.deleteTransaction(t.id, t.type)
        toast.success('Lançamento removido.')
        fetchData()
    } catch (e: any) {
        toast.error('Erro ao excluir.')
    }
}

const payTransaction = async (t: any) => {
    try {
        const result = await Swal.fire({
            title: 'Marcar como Pago?',
            text: `Confirma a baixa de "${t.title}"? Isso afetará sua conta e possivelmente criará o próximo ciclo.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: 'Sim, dar baixa!',
            cancelButtonText: 'Cancelar'
        });
        if (!result.isConfirmed) return;

        await transactionRepository.payTransaction(t.id, true, t.type)
        toast.success(`Lançamento baixado com sucesso!`)
        fetchData()
    } catch(e: any) {
        toast.error(e.response?.data?.error || 'Falha ao processar baixa.')
    }
}
</script>
<style>
.animate-pulse-slow {
   animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
