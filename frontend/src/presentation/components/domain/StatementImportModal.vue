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
        <div class="flex items-center gap-3">
          <div class="bg-primary/10 p-2 rounded-xl">
            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-xl font-bold text-gray-800">Conciliação Bancária</h3>
              <span class="bg-amber-100 text-amber-700 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter shadow-sm border border-amber-200/50">BETA</span>
            </div>
            <p class="text-xs text-gray-400 mt-0.5 uppercase tracking-wider font-semibold">Importação Inteligente (PDF, CSV, OFX)</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
            <button @click="startHelp" class="p-2 hover:bg-gray-100 rounded-full text-primary transition" title="Ajuda">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </button>
            <button @click="close" class="p-2 hover:bg-gray-100 rounded-full text-gray-400 hover:text-gray-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
      </div>

      <!-- Onboarding Walkthrough Overlay -->
      <div v-if="helpStep > 0" class="absolute inset-0 z-50 pointer-events-none">
          <div class="absolute inset-0 bg-black/5 backdrop-blur-[1px]"></div>
          <div v-if="helpStep === 1" class="absolute top-[25%] left-1/2 -translate-x-1/2 w-[300px] bg-gray-900 text-white p-5 rounded-3xl shadow-2xl pointer-events-auto animate-bounce-subtle">
              <p class="text-sm font-bold mb-3">1. Suba seu arquivo 📂</p>
              <p class="text-xs text-gray-300 leading-relaxed italic">Arraste seu PDF, CSV ou OFX. Nossa IA detecta o banco e o formato automaticamente!</p>
              <button @click="helpStep++" class="mt-4 w-full bg-primary py-2 rounded-xl text-xs font-bold">Próximo</button>
          </div>
          <div v-if="helpStep === 2" class="absolute top-[40%] left-1/2 -translate-x-1/2 w-[300px] bg-gray-900 text-white p-5 rounded-3xl shadow-2xl pointer-events-auto">
              <p class="text-sm font-bold mb-3">2. Refine os dados ✏️</p>
              <p class="text-xs text-gray-300 leading-relaxed">Você pode editar datas, descrições e valores diretamente na tabela se algo não vier certinho.</p>
              <button @click="helpStep++" class="mt-4 w-full bg-primary py-2 rounded-xl text-xs font-bold">Entendi</button>
          </div>
          <div v-if="helpStep === 3" class="absolute top-[60%] left-1/2 -translate-x-1/2 w-[300px] bg-gray-900 text-white p-5 rounded-3xl shadow-2xl pointer-events-auto">
              <p class="text-sm font-bold mb-3">3. Classifique 🏷️</p>
              <p class="text-xs text-gray-300 leading-relaxed">Marque o que é transferência entre contas ou aporte em metas. O sistema cuidará da lógica!</p>
              <button @click="helpStep = 0" class="mt-4 w-full bg-green-500 py-2 rounded-xl text-xs font-bold">Vamos lá!</button>
          </div>
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
            <input type="file" ref="fileInput" class="hidden" accept="application/pdf,.csv,.ofx" @change="handleFileSelect" />
            <div class="bg-primary/10 w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-10 h-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
            </div>
            <p class="text-xl font-bold text-gray-800">Selecione seu arquivo</p>
            <p class="text-gray-500 mt-2 max-w-xs mx-auto">Solte PDF, CSV ou OFX para começar o processamento automático.</p>
          </div>

          <div v-if="uploading" class="mt-8 flex flex-col items-center gap-4 text-primary animate-pulse">
            <div class="flex gap-1">
              <div class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce"></div>
              <div class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce [animation-delay:-.3s]"></div>
              <div class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce [animation-delay:-.5s]"></div>
            </div>
            <span class="text-sm font-bold uppercase tracking-widest leading-relaxed">Decifrando Arquivo Bancário...</span>
          </div>
        </div>

        <!-- Step 2: Review -->
        <div v-if="step === 'review'" class="space-y-8 pb-32">
          <!-- Filters & Global Tools -->
          <div class="bg-white border border-gray-100 p-5 rounded-3xl space-y-4 shadow-sm">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Filtrar por Período</label>
                    <div class="flex items-center gap-2">
                        <input type="date" v-model="filterStartDate" class="flex-1 h-10 px-3 bg-gray-50 border-none rounded-xl text-xs focus:ring-2 focus:ring-primary/10 outline-none" />
                        <span class="text-gray-400">até</span>
                        <input type="date" v-model="filterEndDate" class="flex-1 h-10 px-3 bg-gray-50 border-none rounded-xl text-xs focus:ring-2 focus:ring-primary/10 outline-none" />
                    </div>
                </div>
                <div class="flex-1">
                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Configuração em Massa (Selecionados)</label>
                    <div class="flex gap-2">
                        <select v-model="bulkAccountId" class="flex-1 h-10 px-3 bg-gray-50 border-none rounded-xl text-xs focus:ring-2 focus:ring-primary/10 outline-none">
                            <option value="">Aplicar Conta...</option>
                            <option v-for="acc in accountStore.accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                        </select>
                        <button @click="applyBulk" class="px-5 bg-primary text-white rounded-xl text-xs font-bold hover:bg-blue-600 transition shadow-lg shadow-blue-500/20">Aplicar</button>
                    </div>
                </div>
            </div>
          </div>

          <!-- Transaction Table UI Refined -->
          <div class="space-y-4">
            <div class="flex items-center justify-between px-2">
                <div class="flex flex-col">
                    <h4 class="text-sm font-black text-gray-800 uppercase tracking-tight">Transações Encontradas</h4>
                    <span class="text-[10px] text-gray-400 font-bold uppercase">{{ filteredTransactions.length }} lançamentos detectados</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-gray-400">Selecionar Tudo</span>
                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="w-4 h-4 rounded-md border-gray-300 text-primary focus:ring-primary" />
                </div>
            </div>

            <div v-for="(tx, idx) in filteredTransactions" :key="idx" 
                 class="group bg-white border border-gray-100 p-5 rounded-3xl hover:border-primary/20 transition-all hover:shadow-xl hover:shadow-gray-200/50 flex flex-col gap-5">
              
              <div class="flex items-start gap-4">
                <div class="relative pt-1">
                    <input type="checkbox" v-model="tx.selected" class="w-5 h-5 rounded-lg border-gray-200 text-primary focus:ring-0 cursor-pointer" />
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-2">
                        <input type="date" v-model="tx.date" class="text-[10px] font-mono bg-gray-50 text-gray-500 px-2 py-1 rounded border-none focus:ring-1 focus:ring-primary/20" />
                        <span :class="tx.type === 'asset' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'" class="text-[9px] font-black uppercase px-2.5 py-1 rounded-full tracking-tighter shadow-sm">
                            {{ tx.type === 'asset' ? 'Crédito' : 'Débito' }}
                        </span>
                    </div>
                    <input v-model="tx.description" 
                        class="w-full bg-white border border-gray-100 rounded-lg px-2 py-1.5 font-bold text-sm text-gray-800 focus:ring-2 focus:ring-primary/10 transition-all placeholder-gray-300" />
                </div>

                <div class="text-right">
                    <div class="flex items-center justify-end gap-1 mb-1">
                        <span class="text-gray-400 text-xs font-bold">R$</span>
                        <input type="number" v-model.number="tx.amount" step="0.01"
                            :class="tx.type === 'asset' ? 'text-green-600' : 'text-red-500'" 
                            class="w-24 text-right bg-transparent border-none p-0 font-black text-sm focus:ring-0" />
                    </div>
                    <button @click="removeTransaction(tx)" class="text-[10px] font-black uppercase text-red-300 hover:text-red-500 transition-colors flex items-center gap-1 ml-auto mt-2">
                        Excluir
                    </button>
                </div>
              </div>

              <!-- Individual Controls & Advanced Classification -->
              <div class="p-4 bg-gray-50/50 rounded-2xl grid grid-cols-1 md:grid-cols-3 gap-4 border border-gray-100/50">
                  <div class="relative">
                      <label class="block text-[9px] font-black uppercase text-gray-400 mb-1 ml-1">Conta Financeira</label>
                      <select v-model="tx.accountId" class="w-full h-9 px-3 bg-white border border-gray-100 rounded-xl text-[11px] focus:ring-2 focus:ring-primary/10 appearance-none font-bold text-gray-700 shadow-sm transition-all shadow-gray-200/20">
                        <option value="">Onde foi?</option>
                        <option v-for="acc in accountStore.accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                      </select>
                  </div>
                  <div class="relative">
                      <label class="block text-[9px] font-black uppercase text-gray-400 mb-1 ml-1">Tipo de Lançamento</label>
                      <select v-model="tx.classification" class="w-full h-9 px-3 bg-white border border-gray-100 rounded-xl text-[11px] focus:ring-2 focus:ring-primary/10 appearance-none font-bold text-gray-700 shadow-sm transition-all shadow-gray-200/20">
                        <option value="standard">Lançamento Geral</option>
                        <option value="transfer">Transferência Bancária</option>
                        <option value="goal">Aporte em Meta</option>
                        <option value="investment">Investimento / Ativo</option>
                      </select>
                  </div>
                  <div class="relative">
                      <label class="block text-[9px] font-black uppercase text-gray-400 mb-1 ml-1">Categoria / Destino</label>
                      <!-- Conditional Category / Goal Selector -->
                      <select v-if="tx.classification === 'standard' || tx.classification === 'investment'" v-model="tx.categoryId" class="w-full h-9 px-3 bg-white border border-gray-100 rounded-xl text-[11px] focus:ring-2 focus:ring-primary/10 appearance-none font-bold text-gray-700 shadow-sm transition-all shadow-gray-200/20">
                        <option v-for="cat in categoryStore.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                      </select>
                      <select v-else-if="tx.classification === 'transfer'" v-model="tx.targetAccountId" class="w-full h-9 px-3 bg-white border border-gray-100 rounded-xl text-[11px] focus:ring-2 focus:ring-primary/10 appearance-none font-bold text-blue-700 shadow-sm transition-all shadow-gray-200/20">
                        <option value="">Conta Destino...</option>
                        <option v-for="acc in accountStore.accounts.filter(a => a.id != tx.accountId)" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                      </select>
                      <select v-else-if="tx.classification === 'goal'" v-model="tx.goalId" class="w-full h-9 px-3 bg-white border border-gray-100 rounded-xl text-[11px] focus:ring-2 focus:ring-primary/10 appearance-none font-bold text-green-700 shadow-sm transition-all shadow-gray-200/20">
                        <option value="">Meta de Destino...</option>
                        <option v-for="g in goalStore.goals" :key="g.id" :value="g.id">{{ g.title }}</option>
                      </select>
                  </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Fixed Footer -->
      <div v-if="step === 'review'" class="p-8 bg-white border-t border-gray-100 sticky bottom-0 flex flex-col gap-5 shadow-[0_-15px_40px_-20px_rgba(0,0,0,0.1)]">
        <div class="flex justify-between items-center text-sm">
            <div class="flex items-center gap-3">
                <div class="bg-gray-100 p-2.5 rounded-2xl text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                </div>
                <div>
                    <p class="font-black text-gray-800 text-base leading-none">{{ selectedCount }}</p>
                    <p class="text-[10px] uppercase font-black text-gray-400 tracking-wider">Prontos p/ Conciliar</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-[10px] uppercase font-black text-blue-400 mb-1">Impacto no Patrimônio</p>
                <div class="flex items-baseline justify-end gap-1">
                    <span class="text-xs font-bold text-primary">R$</span>
                    <p class="font-black text-2xl text-primary leading-none">{{ formatCurrency(totalSelectedAmount) }}</p>
                </div>
            </div>
        </div>

        <div class="flex gap-4">
          <button @click="step = 'upload'" class="flex-1 h-14 text-sm font-black uppercase tracking-widest text-gray-400 hover:text-gray-800 bg-gray-50 hover:bg-gray-100 rounded-3xl transition-all border border-gray-100/50">Anular</button>
          <button 
            @click="confirmImport" 
            :disabled="selectedCount === 0 || !allSelectedHaveAccount || saving"
            class="flex-[2] h-14 bg-primary hover:bg-blue-600 text-white font-black uppercase tracking-widest text-xs rounded-3xl shadow-2xl shadow-blue-500/30 transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-3"
          >
            <template v-if="saving">
              <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Processando Transações...
            </template>
            <template v-else>
              Finalizar Conciliação
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
import { useGoalStore } from '@/presentation/store/goalStore'
import { transactionRepository } from '@/data/repositories/TransactionRepositoryImpl'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits(['update:modelValue', 'imported'])

const accountStore = useAccountStore()
const categoryStore = useCategoryStore()
const goalStore = useGoalStore()

const step = ref<'upload' | 'review'>('upload')
const helpStep = ref(0)
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
    helpStep.value = 0
    if (accountStore.accounts.length === 0) accountStore.fetchAccounts()
    if (categoryStore.categories.length === 0) categoryStore.fetchCategories()
    if (goalStore.goals.length === 0) goalStore.fetchGoals()
  }
})

const close = () => emit('update:modelValue', false)
const startHelp = () => helpStep.value = 1

const handleFileSelect = (e: any) => {
  const file = e.target.files[0]
  if (file) uploadFile(file)
}

const handleDrop = (e: any) => {
  dragOver.value = false
  const file = e.dataTransfer.files[0]
  if (file) {
    uploadFile(file)
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
      categoryId: 1,
      classification: 'standard',
      targetAccountId: '',
      goalId: ''
    }))
    step.value = 'review'
  } catch (e: any) {
    toast.error(e.response?.data?.error || 'Erro ao processar arquivo.')
  } finally {
    uploading.value = false
  }
}

const toggleSelectAll = () => {
  filteredTransactions.value.forEach(t => t.selected = selectAll.value)
}

const applyBulk = () => {
    if (!bulkAccountId.value) {
        toast.warning('Selecione uma conta.')
        return
    }
    filteredTransactions.value.forEach(t => {
        if (t.selected) t.accountId = bulkAccountId.value
    })
    toast.info('Conta aplicada em massa.')
}

const removeTransaction = (tx: any) => {
    const idx = transactions.value.indexOf(tx)
    if (idx !== -1) transactions.value.splice(idx, 1)
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
      if (tx.classification === 'transfer' && tx.targetAccountId) {
          // Double transaction for transfer
          // 1. Withdrawal from Source
          await transactionRepository.createTransaction({
            type: 'bill',
            title: `[TRANSFER] ${tx.description}`,
            amount: tx.amount,
            date: tx.date,
            accountId: Number(tx.accountId),
            categoryId: 1, // Transfer category
            status: 'PAID',
            description: `Transferência para ${tx.targetAccountId}`
          })
          // 2. Deposit to Target
          await transactionRepository.createTransaction({
            type: 'asset',
            title: `[TRANSFER] ${tx.description}`,
            amount: tx.amount,
            date: tx.date,
            accountId: Number(tx.targetAccountId),
            categoryId: 1,
            status: 'PAID',
            description: `Transferência de ${tx.accountId}`
          })
      } else if (tx.classification === 'goal' && tx.goalId) {
          // Create transaction and goal contribution
          await transactionRepository.createTransaction({
            type: 'bill',
            title: `[GOAL] ${tx.description}`,
            amount: tx.amount,
            date: tx.date,
            accountId: Number(tx.accountId),
            categoryId: 1,
            status: 'PAID',
            description: `Aporte na meta ID ${tx.goalId}`
          })
          await goalStore.addContribution(
              Number(tx.goalId),
              tx.amount,
              Number(tx.accountId),
              tx.description,
              tx.date
          )
      } else {
          // Standard transaction
          await transactionRepository.createTransaction({
            type: tx.type,
            title: tx.description,
            amount: tx.amount,
            date: tx.date,
            accountId: Number(tx.accountId),
            categoryId: Number(tx.categoryId),
            status: 'PAID',
            description: 'Importação Avançada (BETA)'
          })
      }
      successCount++
    } catch (e) {
      console.error(e)
      errorCount++
    }
  }

  if (successCount > 0) {
    toast.success(`${successCount} lançamentos conciliados!`)
    emit('imported')
    close()
  }
  if (errorCount > 0) {
    toast.error(`${errorCount} erros na conciliação.`)
  }
  saving.value = false
}
</script>

<style scoped>
@keyframes slideInLeft {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}

.animate-slideInLeft {
  animation: slideInLeft 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.animate-bounce-subtle {
  animation: bounce-subtle 2s infinite;
}

@keyframes bounce-subtle {
  0%, 100% { transform: translate(-50%, -10%); }
  50% { transform: translate(-50%, 0); }
}

.custom-scroll::-webkit-scrollbar {
  width: 5px;
}
.custom-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scroll::-webkit-scrollbar-thumb {
  background: #e5e7eb;
  border-radius: 10px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
  background: #d1d5db;
}
</style>

