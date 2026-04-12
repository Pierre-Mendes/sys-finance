<!-- Painel de Importação Side Panel (Slide-over) -->
<template>
  <div 
    v-if="modelValue" 
    class="fixed inset-0 z-50 flex justify-end"
  >
    <!-- Background Overlay (Glassmorphism) -->
    <div 
      class="absolute inset-0 bg-black/30 backdrop-blur-sm transition-opacity" 
      @click="close"
    ></div>

    <!-- Slide-out Panel -->
    <div 
      class="relative w-full max-w-2xl h-full bg-white shadow-2xl flex flex-col animate-slideInLeft"
    >
      <!-- Fixed Header -->
      <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white/80 backdrop-blur-md sticky top-0 z-10">
        <div>
          <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Importar Extrato PDF
          </h3>
          <p class="text-xs text-gray-400 mt-1 uppercase tracking-wider font-semibold">Conciliação Automática</p>
        </div>
        <button @click="close" class="p-2 hover:bg-gray-100 rounded-full text-gray-400 hover:text-gray-600 transition">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>

      <!-- Scrollable Body -->
      <div class="flex-1 overflow-y-auto custom-scroll p-6 space-y-8">
        
        <!-- Step 1: Upload -->
        <div v-if="step === 'upload'" class="flex flex-col h-full justify-center">
          <div 
            @dragover.prevent="dragOver = true" 
            @dragleave.prevent="dragOver = false" 
            @drop.prevent="handleDrop"
            :class="['border-2 border-dashed rounded-2xl p-16 text-center transition-all cursor-pointer group', dragOver ? 'border-primary bg-blue-50/50 scale-[0.98]' : 'border-gray-200 hover:border-primary/50 hover:bg-gray-50']"
            @click="fileInput?.click()"
          >
            <input type="file" ref="fileInput" class="hidden" accept="application/pdf" @change="handleFileSelect" />
            <div class="bg-primary/10 w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-10 h-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
            </div>
            <p class="text-xl font-bold text-gray-800">Selecione seu arquivo PDF</p>
            <p class="text-gray-500 mt-2 max-w-xs mx-auto">Solte o arquivo aqui para começar o processamento inteligente.</p>
          </div>

          <div v-if="uploading" class="mt-8 flex flex-col items-center gap-4 text-primary animate-pulse">
            <div class="flex gap-1">
              <div class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce"></div>
              <div class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce [animation-delay:-.3s]"></div>
              <div class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce [animation-delay:-.5s]"></div>
            </div>
            <span class="text-sm font-bold uppercase tracking-widest">IA Analisando Estrutura do Banco...</span>
          </div>
        </div>

        <!-- Step 2: Review -->
        <div v-if="step === 'review'" class="space-y-8 pb-32">
          <!-- Filters & Global Tools -->
          <div class="bg-gray-50/50 border border-gray-100 p-5 rounded-3xl space-y-4">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Filtrar por Período</label>
                    <div class="flex items-center gap-2">
                        <input type="date" v-model="filterStartDate" class="flex-1 h-10 px-3 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-primary/20 outline-none" />
                        <span class="text-gray-400">até</span>
                        <input type="date" v-model="filterEndDate" class="flex-1 h-10 px-3 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-primary/20 outline-none" />
                    </div>
                </div>
                <div class="flex-1">
                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Ação em Massa (Selecionados)</label>
                    <div class="flex gap-2">
                        <select v-model="bulkAccountId" class="flex-1 h-10 px-3 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-primary/20 outline-none">
                            <option value="">Aplicar Conta...</option>
                            <option v-for="acc in accountStore.accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                        </select>
                        <button @click="applyBulk" class="px-4 bg-gray-800 text-white rounded-xl text-xs font-bold hover:bg-black transition">Aplicar</button>
                    </div>
                </div>
            </div>
          </div>

          <!-- Transaction Table UI Refined -->
          <div class="space-y-3">
            <div class="flex items-center justify-between px-2">
                <div class="flex flex-col">
                    <h4 class="text-sm font-bold text-gray-800 uppercase">Transações Detectadas</h4>
                    <span class="text-[10px] text-gray-400 font-medium">Exibindo {{ filteredTransactions.length }} lançamentos</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-gray-400">Selecionar Tudo</span>
                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="w-4 h-4 rounded-md border-gray-300 text-primary focus:ring-primary" />
                </div>
            </div>

            <div v-for="(tx, idx) in filteredTransactions" :key="idx" 
                 class="group bg-white border border-gray-100 p-4 rounded-2xl hover:border-primary/20 transition-all hover:shadow-lg hover:shadow-gray-200/50 flex flex-col gap-4">
              
              <div class="flex items-center gap-4">
                <div class="relative">
                    <input type="checkbox" v-model="tx.selected" class="w-5 h-5 rounded-lg border-gray-200 text-primary focus:ring-0 cursor-pointer" />
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[10px] font-mono bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded leading-none">{{ formatDate(tx.date) }}</span>
                        <span :class="tx.type === 'asset' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'" class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full tracking-tighter">
                            {{ tx.type === 'asset' ? 'Crédito' : 'Débito' }}
                        </span>
                    </div>
                    <input v-model="tx.description" 
                        class="w-full bg-transparent border-none p-0 font-bold text-sm text-gray-800 focus:ring-0 placeholder-gray-300 truncate" />
                </div>

                <div class="text-right">
                    <p :class="tx.type === 'asset' ? 'text-green-600' : 'text-red-500'" class="font-black text-sm">
                    {{ tx.type === 'asset' ? '+' : '-' }} R$ {{ tx.amount.toLocaleString('pt-BR', {minimumFractionDigits: 2}) }}
                    </p>
                    <button @click="removeTransaction(tx)" class="text-xs font-bold text-red-300 hover:text-red-500 transition flex items-center gap-1 ml-auto mt-1">
                        Remover
                    </button>
                </div>
              </div>

              <!-- Individual Controls -->
              <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-50">
                  <div class="relative">
                      <select v-model="tx.accountId" class="w-full h-8 px-2 bg-gray-50 border-none rounded-lg text-[11px] focus:ring-1 focus:ring-primary/20 appearance-none font-medium text-gray-600">
                        <option value="">Selecione a Conta...</option>
                        <option v-for="acc in accountStore.accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                      </select>
                  </div>
                  <div class="relative">
                      <select v-model="tx.categoryId" class="w-full h-8 px-2 bg-gray-50 border-none rounded-lg text-[11px] focus:ring-1 focus:ring-primary/20 appearance-none font-medium text-gray-600">
                        <option v-for="cat in categoryStore.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                      </select>
                  </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Fixed Footer -->
      <div v-if="step === 'review'" class="p-6 bg-white border-t border-gray-100 sticky bottom-0 flex flex-col gap-4 shadow-[0_-10px_30px_-15px_rgba(0,0,0,0.05)]">
        <div class="flex justify-between items-center text-sm">
            <div class="flex items-center gap-2">
                <div class="bg-gray-100 p-2 rounded-xl text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                </div>
                <div>
                    <p class="font-black text-gray-800 leading-none">{{ selectedCount }}</p>
                    <p class="text-[10px] uppercase font-bold text-gray-400">Prontos p/ Importar</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-[9px] uppercase font-black text-blue-400">Total Selecionado</p>
                <p class="font-black text-primary leading-none">R$ {{ formatCurrency(totalSelectedAmount) }}</p>
            </div>
        </div>

        <div class="flex gap-4">
          <button @click="step = 'upload'" class="flex-1 h-14 text-sm font-bold text-gray-500 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-3xl transition">Reiniciar</button>
          <button 
            @click="confirmImport" 
            :disabled="selectedCount === 0 || !allSelectedHaveAccount || saving"
            class="flex-[2] h-14 bg-primary hover:bg-blue-600 text-white font-black text-sm rounded-3xl shadow-xl shadow-blue-500/20 transition disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <template v-if="saving">
              <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Sincronizando...
            </template>
            <template v-else>
              Finalizar Importação
            </template>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { toast } from 'vue3-toastify'
import api from '@/data/api/HttpClient'
import { useAccountStore } from '@/presentation/store/accountStore'
import { useCategoryStore } from '@/presentation/store/categoryStore'
import { transactionRepository } from '@/data/repositories/TransactionRepositoryImpl'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits(['update:modelValue', 'imported'])

const accountStore = useAccountStore()
const categoryStore = useCategoryStore()

const step = ref<'upload' | 'review'>('upload')
const dragOver = ref(false)
const uploading = ref(false)
const saving = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const transactions = ref<any[]>([])

// Filter States
const filterStartDate = ref('')
const filterEndDate = ref('')
const bulkAccountId = ref('')

const selectAll = ref(true)

const filteredTransactions = computed(() => {
  return transactions.value.filter(t => {
    if (filterStartDate.value && t.date < filterStartDate.value) return false
    if (filterEndDate.value && t.date > filterEndDate.value) return false
    return true
  })
})

const selectedCount = computed(() => filteredTransactions.value.filter(t => t.selected).length)

const totalSelectedAmount = computed(() => {
    return filteredTransactions.value
        .filter(t => t.selected)
        .reduce((acc, t) => acc + (t.type === 'asset' ? t.amount : -t.amount), 0)
})

const allSelectedHaveAccount = computed(() => {
    return filteredTransactions.value
        .filter(t => t.selected)
        .every(t => t.accountId !== '')
})

watch(() => props.modelValue, (val) => {
  if (val) {
    step.value = 'upload'
    transactions.value = []
    uploading.value = false
    saving.value = false
    selectAll.value = true
    filterStartDate.value = ''
    filterEndDate.value = ''
    bulkAccountId.value = ''
    if (accountStore.accounts.length === 0) accountStore.fetchAccounts()
    if (categoryStore.categories.length === 0) categoryStore.fetchCategories()
  }
})

const close = () => emit('update:modelValue', false)

const handleFileSelect = (e: any) => {
  const file = e.target.files[0]
  if (file) uploadFile(file)
}

const handleDrop = (e: any) => {
  dragOver.value = false
  const file = e.dataTransfer.files[0]
  if (file && file.type === 'application/pdf') {
    uploadFile(file)
  } else {
    toast.error('Por favor, selecione um arquivo PDF.')
  }
}

const uploadFile = async (file: File) => {
  uploading.value = true
  const formData = new FormData()
  formData.append('file', file)

  try {
    const { data } = await api.post('/api/statements/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    transactions.value = data.transactions.map((t: any) => ({
      ...t,
      selected: true,
      title: t.description,
      accountId: '',
      categoryId: 1 // General category by default
    }))
    step.value = 'review'
  } catch (e: any) {
    toast.error(e.response?.data?.error || 'Erro ao processar PDF.')
  } finally {
    uploading.value = false
  }
}

const toggleSelectAll = () => {
  filteredTransactions.value.forEach(t => t.selected = selectAll.value)
}

const applyBulk = () => {
    if (!bulkAccountId.value) {
        toast.warning('Selecione uma conta para aplicar.')
        return
    }
    filteredTransactions.value.forEach(t => {
        if (t.selected) t.accountId = bulkAccountId.value
    })
    toast.info('Conta aplicada aos selecionados.')
}

const removeTransaction = (tx: any) => {
    const idx = transactions.value.indexOf(tx)
    if (idx !== -1) transactions.value.splice(idx, 1)
}

const formatDate = (dateStr: string) => {
  const [year, month, day] = dateStr.split('-')
  return `${day}/${month}/${year}`
}

const formatCurrency = (val: number) => {
    return Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const confirmImport = async () => {
  saving.value = true
  let successCount = 0
  let errorCount = 0

  const toImport = filteredTransactions.value.filter(t => t.selected)

  for (const tx of toImport) {
    try {
      await transactionRepository.createTransaction({
        type: tx.type,
        title: tx.description,
        amount: tx.amount,
        date: tx.date,
        accountId: Number(tx.accountId),
        categoryId: Number(tx.categoryId),
        status: 'PAID',
        description: 'Importado via Extrato PDF (IA Hybrid Engine)'
      })
      successCount++
    } catch (e) {
      errorCount++
    }
  }

  if (successCount > 0) {
    toast.success(`${successCount} lançamentos importados com sucesso!`)
    emit('imported')
    close()
  }
  if (errorCount > 0) {
    toast.error(`${errorCount} erros durante a importação.`)
  }
  saving.value = false
}
</script>

