<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useCreditCardStore } from '@/presentation/store/creditCardStore'
import { useAccountStore } from '@/presentation/store/accountStore'
import { useCategoryStore } from '@/presentation/store/categoryStore'
import CreatableSelect from '@/components/ui/CreatableSelect.vue'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoaderSpinner from '@/components/ui/LoaderSpinner.vue'
import { toast } from 'vue3-toastify'
import Swal from 'sweetalert2'
import EmptyState from '@/components/ui/EmptyState.vue'

const store = useCreditCardStore()
const accountStore = useAccountStore()
const categoryStore = useCategoryStore()
const expenseCategories = computed(() => categoryStore.categories.filter(c => c.type === 'bill' || c.type === 'expense'))

const showModal = ref(false)
const newCard = ref({
  name: '',
  brand: 'Visa',
  limitAmount: 0,
  closingDay: 1,
  dueDay: 10,
  accountId: 0 as number | string,
  accountName: '',
  color: '#8a05be' // Default Purple
})

const presetColors = [
  { name: 'Roxo (Nubank)', value: '#8a05be' },
  { name: 'Laranja (Inter)', value: '#ff7a00' },
  { name: 'Azul (Itaú)', value: '#003399' },
  { name: 'Vermelho (Santander)', value: '#ec0000' },
  { name: 'Dourado (BB)', value: '#d4af37' },
  { name: 'Grafite', value: '#333333' }
]

const showTxModal = ref(false)
const txCardId = ref(0)
const newTx = ref({
  title: '',
  amount: 0,
  installments: 1,
  date: new Date().toISOString().substring(0, 10),
  categoryId: '' as number | string,
  categoryName: '',
  description: ''
})

const showEditModal = ref(false)
const editCard = ref({
  id: 0,
  name: '',
  brand: 'Visa',
  limitAmount: 0,
  closingDay: 1,
  dueDay: 10,
  accountId: 0 as number | string,
  accountName: '',
  color: '#8a05be'
})

const showEditTxModal = ref(false)
const editTxData = ref({
  id: 0,
  title: '',
  amount: 0,
  date: '',
  categoryId: '' as number | string,
  categoryName: '',
  description: '',
  installments: 1,
  currentInstallment: 1
})

// Transaction filtering & sorting
const txSearchQuery = ref('')
const txSortBy = ref('date') // 'date', 'title', 'amount'
const txSortOrder = ref('desc')

const filteredTransactions = computed(() => {
  let result = [...store.cardTransactions]
  
  if (txSearchQuery.value) {
    const query = txSearchQuery.value.toLowerCase()
    result = result.filter(tx => 
      tx.title.toLowerCase().includes(query) || 
      (tx.description && tx.description.toLowerCase().includes(query))
    )
  }
  
  result.sort((a, b) => {
    let valA, valB
    if (txSortBy.value === 'date') {
      valA = new Date(a.date).getTime()
      valB = new Date(b.date).getTime()
    } else if (txSortBy.value === 'amount') {
      valA = Number(a.amount)
      valB = Number(b.amount)
    } else {
      valA = a.title.toLowerCase()
      valB = b.title.toLowerCase()
    }
    
    if (txSortOrder.value === 'asc') return valA > valB ? 1 : -1
    return valA < valB ? 1 : -1
  })
  
  return result
})

const toggleTxSort = (field: string) => {
  if (txSortBy.value === field) {
    txSortOrder.value = txSortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    txSortBy.value = field
    txSortOrder.value = 'desc'
  }
}

onMounted(async () => {
  await store.fetchCards()
  if (accountStore.accounts.length === 0) {
    await accountStore.fetchAccounts()
  }
  if (categoryStore.categories.length === 0) {
    await categoryStore.fetchCategories()
  }
  
  if (store.selectedCardId) {
    await store.fetchCardTransactions(store.selectedCardId)
  }
})

watch(() => store.selectedCardId, async (newId) => {
  if (newId) {
    await store.fetchCardTransactions(newId)
  }
})

const addCard = async () => {
  if (!newCard.value.name || (!newCard.value.accountId && !newCard.value.accountName)) {
    toast.warning('Preencha os dados obrigatórios.')
    return
  }
  
  try {
    const createsAccount = !!newCard.value.accountName
    await store.createCard(newCard.value)
    if (createsAccount) await accountStore.forceRefreshAccounts()
    toast.success('Cartão adicionado com sucesso!')
    showModal.value = false
    newCard.value = { name: '', brand: 'Visa', limitAmount: 0, closingDay: 1, dueDay: 10, accountId: 0, accountName: '', color: '#8a05be' }
  } catch (e) {
    // Error handled in store
  }
}

const openTxModal = (id: number) => {
  txCardId.value = id
  showTxModal.value = true
}

const showCardDetailModal = ref(false)

const selectCard = (id: number) => {
  store.selectedCardId = id
}

const currentMonthTotal = computed(() => {
  const now = new Date()
  const month = now.getMonth() + 1
  const year = now.getFullYear()
  
  return store.cardTransactions
    .filter(tx => {
      const d = new Date(tx.date)
      return (d.getMonth() + 1) === month && d.getFullYear() === year
    })
    .reduce((sum, tx) => sum + Number(tx.amount), 0)
})

const nextMonthTotal = computed(() => {
  const now = new Date()
  let month = now.getMonth() + 2
  let year = now.getFullYear()
  if (month > 12) {
    month = 1
    year++
  }
  
  return store.cardTransactions
    .filter(tx => {
      const d = new Date(tx.date)
      return (d.getMonth() + 1) === month && d.getFullYear() === year
    })
    .reduce((sum, tx) => sum + Number(tx.amount), 0)
})

const recentTransactions = computed(() => {
  return [...store.cardTransactions]
    .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
    .slice(0, 5)
})

const submitTx = async () => {
  if (txCardId.value === 0 || newTx.value.amount <= 0) return
  if (!newTx.value.categoryId && !newTx.value.categoryName) {
    toast.warning('Selecione uma categoria ou digite o nome de uma nova.')
    return
  }
  try {
    const createsCategory = !!newTx.value.categoryName
    await store.addTransaction(txCardId.value, newTx.value)
    if (createsCategory) await categoryStore.forceRefreshCategories()
    showTxModal.value = false
    newTx.value = { title: '', amount: 0, installments: 1, date: new Date().toISOString().substring(0, 10), categoryId: '', categoryName: '', description: '' }
  } catch (e) {
    // Error handled in store
  }
}

const openEditModal = (card: any) => {
  editCard.value = {
    id: card.id,
    name: card.name,
    brand: card.brand || 'Visa',
    limitAmount: card.limitAmount,
    closingDay: card.closingDay,
    dueDay: card.dueDay,
    accountId: card.accountId,
    accountName: '',
    color: card.color || '#8a05be'
  }
  showEditModal.value = true
}

const saveEdit = async () => {
  if (!editCard.value.name || (!editCard.value.accountId && !editCard.value.accountName)) {
    toast.warning('Preencha os dados obrigatórios.')
    return
  }
  const createsAccount = !!editCard.value.accountName
  await store.updateCard(editCard.value.id, editCard.value)
  if (createsAccount) await accountStore.forceRefreshAccounts()
  showEditModal.value = false
}

const openEditTxModal = (tx: any) => {
  editTxData.value = {
    id: tx.id,
    title: tx.title,
    amount: tx.amount,
    date: tx.date.split('T')[0],
    categoryId: tx.categoryId,
    categoryName: '',
    description: tx.description || '',
    installments: tx.installments,
    currentInstallment: tx.currentInstallment
  }
  showEditTxModal.value = true
}

const saveEditTx = async () => {
  if (!editTxData.value.title || editTxData.value.amount <= 0) {
    toast.warning('Preencha os dados obrigatórios.')
    return
  }
  const createsCategory = !!editTxData.value.categoryName
  const res = await store.updateTransaction(editTxData.value.id, editTxData.value)
  if (res && createsCategory) await categoryStore.forceRefreshCategories()
  if (res) showEditTxModal.value = false
}

const deleteTx = async (tx: any) => {
  const result = await Swal.fire({
    title: 'Excluir Compra?',
    text: `Deseja realmente excluir "${tx.title}"? Esta ação não pode ser desfeita.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#C2410C',
    cancelButtonColor: '#9ca3af',
    confirmButtonText: 'Sim, excluir!',
    cancelButtonText: 'Cancelar'
  })
  if (result.isConfirmed) {
    await store.deleteTransaction(tx.id)
  }
}

const deleteCard = async (card: any) => {
  const result = await Swal.fire({
    title: 'Excluir Cartão?',
    text: `Deseja realmente excluir o cartão "${card.name}"? Todas as compras registradas serão perdidas.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#C2410C',
    cancelButtonColor: '#9ca3af',
    confirmButtonText: 'Sim, excluir!',
    cancelButtonText: 'Cancelar'
  })
  if (!result.isConfirmed) return
  await store.deleteCard(card.id)
}

const generateBill = async (id: number) => {
  const confirm = await Swal.fire({
    title: 'Gerar fatura fechada?',
    text: 'Cria a conta a pagar com as compras da última fatura que já fechou (pelo dia de fechamento do cartão).',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Gerar',
    cancelButtonText: 'Cancelar',
  })
  if (!confirm.isConfirmed) return
  try {
    const res = await store.generateInvoice(id)
    if (res && res.success) {
      toast.success(res.message)
    } else {
      toast.error(res?.error || 'Nenhuma compra na última fatura fechada.')
    }
  } catch (e) {}
}


const getUsagePercentage = (used: number, limit: number) => {
  if (limit <= 0) return 0
  return Math.min(Math.round((used / limit) * 100), 100)
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  const [year, month, day] = dateStr.split('T')[0].split('-')
  return `${day}/${month}/${year}`
}
</script>

<template>
  <MainLayout>
    <div class="px-4 py-6 max-w-7xl mx-auto">

      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
          <h2 class="text-2xl sm:text-3xl font-semibold text-gray-800">Meus cartões de crédito</h2>
          <p class="text-gray-500 mt-1">Gerencie seus plásticos, acompanhe faturas e compras parceladas.</p>
        </div>
        <button @click="showModal = true" class="w-full sm:w-auto bg-primary hover:bg-brand-800 px-5 py-2.5 rounded-lg text-white font-medium shadow transition cursor-pointer flex items-center justify-center gap-2 whitespace-nowrap">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
          Adicionar Cartão
        </button>
      </div>

      <!-- Loading -->
      <div v-if="store.isLoading" class="flex justify-center my-12">
        <LoaderSpinner size="w-8 h-8" class="text-indigo-600" />
      </div>

      <template v-else>
        <!-- Empty State -->
        <EmptyState v-if="store.cards.length === 0" class="py-12" title="Nenhum cartão ainda" description="Clique em &quot;Adicionar Cartão&quot; para acompanhar faturas e limite." :size="140" />

        <!-- Card Gallery — horizontal scroll with snap -->
        <div v-else>
          <div class="flex gap-4 sm:gap-6 overflow-x-auto pb-6 snap-x snap-mandatory scroll-smooth scrollbar-hide -mx-4 px-4 lg:grid lg:grid-cols-2 xl:grid-cols-3 lg:overflow-x-visible lg:mx-0 lg:px-0">
            <div
              v-for="card in store.cards"
              :key="card.id"
              @click="selectCard(card.id)"
              :style="{ backgroundColor: card.color || '#2346D8' }"
              class="snap-start shrink-0 w-[290px] sm:w-[350px] lg:w-auto rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 text-white overflow-hidden relative min-h-[220px] cursor-pointer select-none border-2"
              :class="store.selectedCardId === card.id ? 'border-primary ring-4 ring-primary/20 scale-[1.02]' : 'border-transparent'"
            >
              <!-- Background decoration -->
              <div class="absolute -right-12 -top-12 w-52 h-52 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
              <div class="absolute -left-12 -bottom-12 w-40 h-40 bg-black/10 rounded-full blur-2xl pointer-events-none"></div>

              <!-- Card chip -->
              <div class="absolute top-6 left-6">
                <svg width="38" height="30" viewBox="0 0 36 28" fill="none" class="opacity-90">
                  <rect x="0.5" y="0.5" width="35" height="27" rx="4" fill="#E5C14B" stroke="#B8960C"/>
                  <rect x="13" y="0.5" width="10" height="27" rx="0" fill="#D4AF37" stroke="none"/>
                  <rect x="0.5" y="9" width="35" height="10" rx="0" fill="#D4AF37" stroke="none"/>
                </svg>
              </div>

              <!-- Actions -->
              <div class="absolute top-5 right-5 flex gap-2 z-20">
                <button @click.stop="openEditModal(card)" title="Editar" class="text-white hover:bg-white/20 bg-white/10 p-2 rounded-xl transition-all backdrop-blur-md">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                </button>
                <button @click.stop="deleteCard(card)" title="Excluir" class="text-white hover:bg-red-500/40 bg-white/10 p-2 rounded-xl transition-all backdrop-blur-md">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
              </div>

              <!-- Card Info -->
              <div class="px-6 pt-16 pb-6 relative z-10">
                <h3 class="text-2xl font-bold tracking-tight truncate">{{ card.name }}</h3>
                <p class="text-xs opacity-80 mt-1 font-medium italic">Disponível: R$ {{ (card.limitAmount - card.usedAmount).toFixed(2) }}</p>

                <!-- Usage bar -->
                <div class="mt-6">
                  <div class="flex justify-between text-xs mb-1.5 font-bold uppercase tracking-wider opacity-90">
                    <span>R$ {{ Number(card.usedAmount).toFixed(2) }} usado</span>
                    <span>{{ getUsagePercentage(card.usedAmount, card.limitAmount) }}%</span>
                  </div>
                  <div class="w-full bg-black/40 rounded-full h-2 overflow-hidden shadow-inner">
                    <div class="bg-white rounded-full h-full transition-all duration-700 shadow-[0_0_10px_rgba(255,255,255,0.5)]" :style="{ width: getUsagePercentage(card.usedAmount, card.limitAmount) + '%' }"></div>
                  </div>
                </div>

                <div v-if="card.openInvoice" class="mt-4 grid grid-cols-2 gap-2 text-xs leading-tight">
                  <div class="bg-black/20 rounded-lg px-2.5 py-1.5">
                    <span class="block opacity-75">Fatura aberta · vence {{ formatDate(card.openInvoice.dueDate).slice(0, 5) }}</span>
                    <span class="block font-bold text-sm">R$ {{ Number(card.openInvoice.total).toFixed(2) }}</span>
                  </div>
                  <div class="bg-black/20 rounded-lg px-2.5 py-1.5">
                    <span class="block opacity-75">Melhor dia de compra</span>
                    <span class="block font-bold text-sm">Dia {{ card.bestPurchaseDay }} <span class="font-normal opacity-75">· fecha {{ formatDate(card.openInvoice.closingDate).slice(0, 5) }}</span></span>
                  </div>
                </div>

                <div class="mt-5 flex justify-between items-center text-[12px]">
                  <span class="opacity-80 font-medium">LIMITE: R$ {{ card.limitAmount }}</span>
                  <span class="font-black italic tracking-tighter text-base opacity-90 uppercase">{{ card.brand }}</span>
                </div>
              </div>

              <!-- Card footer actions with high contrast -->
              <div class="bg-black/30 px-4 py-3 relative z-10 flex gap-2 backdrop-blur-lg border-t border-white/10">
                <button @click.stop="openTxModal(card.id)" class="flex-1 text-xs bg-white text-gray-900 hover:bg-gray-100 py-2.5 rounded-xl font-bold transition-all shadow-lg uppercase tracking-wider">Nova Compra</button>
                <button @click.stop="generateBill(card.id)" class="flex-1 text-xs bg-primary hover:bg-brand-800 text-white py-2.5 rounded-xl font-bold transition-all shadow-lg uppercase tracking-wider">Gerar Fatura</button>
                <button @click.stop="showCardDetailModal = true" class="lg:hidden flex-1 text-xs bg-white/20 text-white hover:bg-white/30 py-2.5 rounded-xl font-bold transition-all backdrop-blur-md uppercase tracking-wider leading-tight">Compras</button>
              </div>
            </div>
          </div>

          <!-- Mobile Space Optimization: Summary & Recent Transactions (lg:hidden) -->
          <div v-if="store.selectedCardId" class="lg:hidden mt-8 space-y-6">
            <!-- Invoice Summary Card -->
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-black text-gray-500 uppercase tracking-widest mb-1">Fatura Atual</p>
                <p class="text-xl font-black text-gray-900 dark:text-white">R$ {{ currentMonthTotal.toFixed(2) }}</p>
              </div>
              <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-black text-gray-500 uppercase tracking-widest mb-1">Próxima Fatura</p>
                <p class="text-xl font-black text-gray-900 dark:text-white">R$ {{ nextMonthTotal.toFixed(2) }}</p>
              </div>
            </div>

            <!-- Recent Transactions List -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
              <div class="p-6 border-b border-gray-50 dark:border-gray-700 flex justify-between items-center">
                <h3 class="font-black text-gray-900 dark:text-white uppercase tracking-tight text-sm">Compras Recentes</h3>
                <button @click="showCardDetailModal = true" class="text-xs font-bold text-primary hover:underline">Ver Todas</button>
              </div>
              
              <div class="divide-y divide-gray-50 dark:divide-gray-700">
                <div v-for="tx in recentTransactions" :key="tx.id" class="p-4 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <div class="min-w-0 pr-4">
                    <div class="flex items-center gap-2 mb-0.5">
                      <span class="text-xs font-black text-primary uppercase">{{ formatDate(tx.date) }}</span>
                    </div>
                    <h4 class="font-bold text-gray-900 dark:text-white text-sm truncate">{{ tx.title }}</h4>
                  </div>
                  <div class="text-right shrink-0">
                    <p class="font-black text-gray-900 dark:text-white text-base">R$ {{ Number(tx.amount).toFixed(2) }}</p>
                  </div>
                </div>

                <div v-if="recentTransactions.length === 0" class="p-8 text-center">
                  <p class="text-sm text-gray-500 italic">Nenhuma compra recente.</p>
                </div>
              </div>
              
              <div v-if="recentTransactions.length > 0" class="p-4 bg-gray-50/50 dark:bg-gray-900/20 text-center border-t border-gray-50 dark:border-gray-700">
                <button @click="showCardDetailModal = true" class="text-xs font-black text-gray-500 uppercase tracking-widest hover:text-primary transition-colors">
                  Abrir Histórico Completo
                </button>
              </div>
            </div>
          </div>

          <!-- Transaction List UI (Desktop) -->
          <div v-if="store.selectedCardId" class="hidden lg:block mt-10">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
              <div class="p-8 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/10">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                  <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                      <div class="w-2 h-6 bg-primary rounded-full"></div>
                      Histórico de Compras: {{ store.cards.find(c => c.id === store.selectedCardId)?.name }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Gerencie e acompanhe os lançamentos deste cartão.</p>
                  </div>
                  
                  <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                    <!-- Search -->
                    <div class="relative group">
                      <input 
                        v-model="txSearchQuery"
                        type="text" 
                        placeholder="Buscar compra..." 
                        class="w-full sm:w-64 pl-10 pr-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all shadow-sm dark:text-white"
                      />
                      <svg class="w-5 h-5 text-gray-500 absolute left-3 top-3 group-focus-within:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                      </svg>
                    </div>

                    <div v-if="store.isTransactionsLoading" class="flex items-center px-4">
                      <LoaderSpinner size="w-5 h-5" class="text-primary" />
                    </div>
                  </div>
                </div>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full text-left">
                  <thead class="bg-gray-50 dark:bg-gray-900 text-gray-500 text-xs font-bold uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                    <tr>
                      <th class="px-8 py-5 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group" @click="toggleTxSort('date')">
                        <div class="flex items-center gap-1">
                          Data
                          <svg v-if="txSortBy === 'date'" class="w-3 h-3 transition-transform" :class="txSortOrder === 'asc' ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        </div>
                      </th>
                      <th class="px-8 py-5 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group" @click="toggleTxSort('title')">
                         <div class="flex items-center gap-1">
                          Título
                          <svg v-if="txSortBy === 'title'" class="w-3 h-3 transition-transform" :class="txSortOrder === 'asc' ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        </div>
                      </th>
                      <th class="px-8 py-5">Parcelas</th>
                      <th class="px-8 py-5 text-right cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group" @click="toggleTxSort('amount')">
                        <div class="flex items-center justify-end gap-1">
                          Valor
                          <svg v-if="txSortBy === 'amount'" class="w-3 h-3 transition-transform" :class="txSortOrder === 'asc' ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        </div>
                      </th>
                      <th class="px-8 py-5 text-center">Ações</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                    <tr v-for="tx in filteredTransactions" :key="tx.id" class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-all group">
                      <td class="px-8 py-5 text-sm text-gray-500 dark:text-gray-400 font-medium whitespace-nowrap">{{ formatDate(tx.date) }}</td>
                      <td class="px-8 py-5">
                        <div class="font-bold text-gray-900 dark:text-white group-hover:text-primary transition-colors">{{ tx.title }}</div>
                        <div v-if="tx.description" class="text-xs text-gray-500 mt-0.5">{{ tx.description }}</div>
                      </td>
                      <td class="px-8 py-5">
                        <span class="px-2.5 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg text-xs font-bold uppercase ring-1 ring-blue-100 dark:ring-blue-800">
                          {{ tx.installments > 1 ? `${tx.currentInstallment}/${tx.installments}` : 'À Vista' }}
                        </span>
                      </td>
                      <td class="px-8 py-5 text-right font-black text-gray-900 dark:text-white whitespace-nowrap text-lg">
                        R$ {{ Number(tx.amount).toFixed(2) }}
                      </td>
                      <td class="px-8 py-5 text-center">
                        <div class="flex justify-center gap-2">
                          <button @click="openEditTxModal(tx)" title="Editar" class="p-1.5 text-gray-500 hover:text-primary hover:bg-primary/10 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                          </button>
                          <button @click="deleteTx(tx)" title="Excluir" class="p-1.5 text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                          </button>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="filteredTransactions.length === 0 && !store.isTransactionsLoading">
                      <td colspan="4" class="px-8 py-20 text-center">
                        <div class="flex flex-col items-center">
                          <svg class="w-12 h-12 text-gray-200 dark:text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                          <p class="text-gray-500 font-medium italic">Nenhuma compra encontrada para este filtro.</p>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </template>

    </div>

    <!-- Modal Novo Cartao -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl p-6 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="modal-title">Novo Cartão de Crédito</h3>
            <p class="text-sm text-gray-500 mb-6">Configure seu plástico e limite disponível.</p>
            
            <div class="grid grid-cols-2 gap-5">
              <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome/Apelido</label>
                <input v-model="newCard.name" type="text" placeholder="Ex: Nubank Pessoal" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
              </div>

              <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Cor do Cartão</label>
                <div class="flex flex-wrap gap-3">
                  <button v-for="color in presetColors" :key="color.value" 
                          @click="newCard.color = color.value"
                          class="w-10 h-10 rounded-full border-2 transition-transform hover:scale-110 flex items-center justify-center"
                          :class="newCard.color === color.value ? 'border-primary ring-2 ring-primary/20' : 'border-transparent'"
                          :style="{ backgroundColor: color.value }"
                          :title="color.name">
                    <svg v-if="newCard.color === color.value" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                  </button>
                  <input type="color" v-model="newCard.color" class="w-10 h-10 rounded-full border-none p-0 bg-transparent cursor-pointer overflow-hidden mt-0" />
                </div>
              </div>

              <div class="col-span-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Bandeira</label>
                <select v-model="newCard.brand" class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 focus:ring-2 focus:ring-primary/20 outline-none transition">
                  <option value="Visa">Visa</option>
                  <option value="Mastercard">Mastercard</option>
                  <option value="Elo">Elo</option>
                  <option value="Amex">American Express</option>
                </select>
              </div>
              <div class="col-span-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Conta Bancária</label>
                <CreatableSelect
                  v-model="newCard.accountId"
                  v-model:new-name="newCard.accountName"
                  :options="accountStore.accounts"
                  placeholder="Escolha ou digite (ex: Nubank)"
                  create-label="Criar conta"
                  input-class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 focus:ring-2 focus:ring-primary/20 outline-none transition"
                />
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Limite R$</label>
                <input v-model.number="newCard.limitAmount" type="number" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
              </div>
              <div class="col-span-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Fechamento (Dia)</label>
                <input v-model.number="newCard.closingDay" type="number" min="1" max="31" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
              </div>
              <div class="col-span-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Vencimento (Dia)</label>
                <input v-model.number="newCard.dueDay" type="number" min="1" max="31" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
                <p v-if="newCard.closingDay" class="text-xs text-gray-500 dark:text-gray-400 mt-1">Melhor dia de compra: dia {{ newCard.closingDay }}. Compras a partir dele vão para a fatura seguinte.</p>
              </div>
            </div>
          </div>
          <div class="mt-8 flex flex-col sm:flex-row-reverse gap-3">
            <button @click="addCard" class="w-full bg-primary hover:bg-brand-800 text-white font-bold py-3 rounded-xl shadow-lg transition duration-300">
              Criar Cartão
            </button>
            <button @click="showModal = false" class="w-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-bold py-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-300">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Nova Compra (Transação) -->
    <div v-if="showTxModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showTxModal = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl p-6 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="modal-title">Registrar Nova Compra</h3>
            <p class="text-sm text-gray-500 mb-6">Insira os detalhes do lançamento no crédito.</p>
            
            <div class="grid grid-cols-2 gap-5">
              <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">O que você comprou?</label>
                <input v-model="newTx.title" type="text" placeholder="Ex: Mercado Livre" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
              </div>
              <div class="col-span-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Valor Total R$</label>
                <input v-model.number="newTx.amount" type="number" inputmode="decimal" step="0.01" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
              </div>
              <div class="col-span-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nº de Parcelas</label>
                <input v-model.number="newTx.installments" type="number" min="1" max="48" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Data da Compra</label>
                <input v-model="newTx.date" type="date" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Categoria</label>
                <CreatableSelect
                  v-model="newTx.categoryId"
                  v-model:new-name="newTx.categoryName"
                  :options="expenseCategories"
                  placeholder="Escolha ou digite (ex: Assinaturas)"
                  create-label="Criar categoria"
                  input-class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 focus:ring-2 focus:ring-primary/20 outline-none transition"
                />
              </div>
            </div>
          </div>
          <div class="mt-8 flex flex-col sm:flex-row-reverse gap-3">
            <button @click="submitTx" class="w-full bg-primary hover:bg-brand-800 text-white font-bold py-3 rounded-xl shadow-lg transition duration-300">
              Lançar Compra
            </button>
            <button @click="showTxModal = false" class="w-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-bold py-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-300">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Editar Cartão -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showEditModal = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl p-6 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Editar Cartão</h3>
            <p class="text-sm text-gray-500 mb-6">Atualize as informações do seu cartão.</p>
            
            <div class="grid grid-cols-2 gap-5">
              <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nome/Apelido</label>
                <input v-model="editCard.name" type="text" placeholder="Ex: Nubank Pessoal" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
              </div>

              <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Cor do Cartão</label>
                <div class="flex flex-wrap gap-3">
                  <button v-for="color in presetColors" :key="color.value" 
                          @click="editCard.color = color.value"
                          class="w-10 h-10 rounded-full border-2 transition-transform hover:scale-110 flex items-center justify-center"
                          :class="editCard.color === color.value ? 'border-primary ring-2 ring-primary/20' : 'border-transparent'"
                          :style="{ backgroundColor: color.value }"
                          :title="color.name">
                    <svg v-if="editCard.color === color.value" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                  </button>
                  <input type="color" v-model="editCard.color" class="w-10 h-10 rounded-full border-none p-0 bg-transparent cursor-pointer overflow-hidden" />
                </div>
              </div>

              <div class="col-span-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Bandeira</label>
                <select v-model="editCard.brand" class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 focus:ring-2 focus:ring-primary/20 outline-none transition">
                  <option value="Visa">Visa</option>
                  <option value="Mastercard">Mastercard</option>
                  <option value="Elo">Elo</option>
                  <option value="Amex">American Express</option>
                </select>
              </div>
              <div class="col-span-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Conta Bancária</label>
                <CreatableSelect
                  v-model="editCard.accountId"
                  v-model:new-name="editCard.accountName"
                  :options="accountStore.accounts"
                  placeholder="Escolha ou digite (ex: Nubank)"
                  create-label="Criar conta"
                  input-class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 focus:ring-2 focus:ring-primary/20 outline-none transition"
                />
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Limite R$</label>
                <input v-model.number="editCard.limitAmount" type="number" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
              </div>
              <div class="col-span-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Fechamento (Dia)</label>
                <input v-model.number="editCard.closingDay" type="number" min="1" max="31" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
              </div>
              <div class="col-span-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Vencimento (Dia)</label>
                <input v-model.number="editCard.dueDay" type="number" min="1" max="31" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
                <p v-if="editCard.closingDay" class="text-xs text-gray-500 dark:text-gray-400 mt-1">Melhor dia de compra: dia {{ editCard.closingDay }}. Compras a partir dele vão para a fatura seguinte.</p>
              </div>
            </div>
          </div>
          <div class="mt-8 flex flex-col sm:flex-row-reverse gap-3">
            <button @click="saveEdit" class="w-full bg-primary hover:bg-brand-800 text-white font-bold py-3 rounded-xl shadow-lg transition duration-300">
              Salvar Alterações
            </button>
            <button @click="showEditModal = false" class="w-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-bold py-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-300">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile Transaction History (Bottom Sheet) -->
    <div v-if="showCardDetailModal" class="fixed inset-0 z-[60] lg:hidden" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showCardDetailModal = false"></div>
      <div class="fixed inset-x-0 bottom-0 max-h-[90vh] bg-white dark:bg-gray-800 rounded-t-[32px] overflow-hidden flex flex-col shadow-2xl transition-transform duration-300">
        <!-- Handle for dragging (visual only) -->
        <div class="w-12 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full mx-auto my-4 shrink-0"></div>
        
        <div class="px-6 pb-6 overflow-y-auto">
          <div class="flex justify-between items-center mb-6">
            <div>
              <h3 class="text-xl font-black text-gray-900 dark:text-white">Compras do Cartão</h3>
              <p class="text-xs text-primary font-bold uppercase tracking-wider">{{ store.cards.find(c => c.id === store.selectedCardId)?.name }}</p>
            </div>
            <button @click="showCardDetailModal = false" class="p-2 bg-gray-100 dark:bg-gray-700 rounded-full">
              <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
          </div>

          <!-- Search & Sort in Mobile Modal -->
          <div class="space-y-4 mb-6">
            <div class="relative">
              <input 
                v-model="txSearchQuery"
                type="text" 
                placeholder="Buscar compra..." 
                class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-none rounded-2xl focus:ring-2 focus:ring-primary/20 outline-none transition-all dark:text-white"
              />
              <svg class="w-5 h-5 text-gray-500 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            
            <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
              <button 
                @click="toggleTxSort('date')"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0"
                :class="txSortBy === 'date' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'"
              >
                DATA {{ txSortBy === 'date' ? (txSortOrder === 'asc' ? '↑' : '↓') : '' }}
              </button>
              <button 
                @click="toggleTxSort('amount')"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0"
                :class="txSortBy === 'amount' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'"
              >
                VALOR {{ txSortBy === 'amount' ? (txSortOrder === 'asc' ? '↑' : '↓') : '' }}
              </button>
              <button 
                @click="toggleTxSort('title')"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0"
                :class="txSortBy === 'title' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'"
              >
                TÍTULO {{ txSortBy === 'title' ? (txSortOrder === 'asc' ? '↑' : '↓') : '' }}
              </button>
            </div>
          </div>

          <div v-if="store.isTransactionsLoading" class="flex flex-col items-center py-12">
            <LoaderSpinner class="text-primary mb-3" />
            <p class="text-sm text-gray-500">Carregando compras...</p>
          </div>

          <div v-else class="space-y-3">
            <div v-for="tx in filteredTransactions" :key="tx.id" class="p-4 bg-gray-50 dark:bg-gray-700/30 rounded-2xl flex justify-between items-center border border-gray-100 dark:border-gray-700/50">
              <div class="flex-1 min-w-0 pr-4">
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-xs font-black text-primary bg-primary/10 px-2 py-0.5 rounded-full uppercase">{{ formatDate(tx.date) }}</span>
                  <span v-if="tx.installments > 1" class="text-xs font-bold text-blue-500 bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded-full uppercase">{{ tx.currentInstallment }}/{{ tx.installments }}</span>
                </div>
                <h4 class="font-bold text-gray-900 dark:text-white truncate">{{ tx.title }}</h4>
                <p v-if="tx.description" class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ tx.description }}</p>
              </div>
              <div class="text-right shrink-0 flex flex-col items-end gap-2">
                <p class="text-lg font-black text-gray-900 dark:text-white whitespace-nowrap">R$ {{ Number(tx.amount).toFixed(2) }}</p>
                <div class="flex gap-2">
                  <button @click="openEditTxModal(tx)" class="p-2 bg-white dark:bg-gray-600 rounded-lg shadow-sm">
                    <svg class="w-3.5 h-3.5 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                  </button>
                  <button @click="deleteTx(tx)" class="p-2 bg-white dark:bg-gray-600 rounded-lg shadow-sm">
                    <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                  </button>
                </div>
              </div>
            </div>

            <div v-if="filteredTransactions.length === 0" class="py-12 text-center">
              <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
              </div>
              <p class="text-gray-500 italic">Nenhuma compra encontrada.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Editar Compra -->
    <div v-if="showEditTxModal" class="fixed inset-0 z-[70] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showEditTxModal = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl p-6 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Editar Compra</h3>
            <p class="text-sm text-gray-500 mb-6">Atualize os detalhes deste lançamento.</p>
            
            <div class="grid grid-cols-2 gap-5">
              <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Título</label>
                <input v-model="editTxData.title" type="text" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
              </div>
              <div class="col-span-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Valor R$</label>
                <input v-model.number="editTxData.amount" type="number" step="0.01" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
              </div>
              <div class="col-span-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Data</label>
                <input v-model="editTxData.date" type="date" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition" />
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Categoria</label>
                <CreatableSelect
                  v-model="editTxData.categoryId"
                  v-model:new-name="editTxData.categoryName"
                  :options="expenseCategories"
                  create-label="Criar categoria"
                  input-class="w-full rounded-xl border border-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 focus:ring-2 focus:ring-primary/20 outline-none transition"
                />
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Descrição</label>
                <textarea v-model="editTxData.description" rows="2" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2.5 border focus:ring-2 focus:ring-primary/20 outline-none transition"></textarea>
              </div>
            </div>
          </div>
          <div class="mt-8 flex flex-col sm:flex-row-reverse gap-3">
            <button @click="saveEditTx" class="w-full bg-primary hover:bg-brand-800 text-white font-bold py-3 rounded-xl shadow-lg transition duration-300">
              Salvar
            </button>
            <button @click="showEditTxModal = false" class="w-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-bold py-3 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-300">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>
