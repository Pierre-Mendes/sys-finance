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
            <div class="mt-8 flex flex-wrap justify-center gap-2">
              <span class="px-3 py-1 bg-white border border-gray-100 rounded-full text-[10px] uppercase font-bold text-gray-400 shadow-sm">Itaú</span>
              <span class="px-3 py-1 bg-white border border-gray-100 rounded-full text-[10px] uppercase font-bold text-gray-400 shadow-sm">Sicoob</span>
              <span class="px-3 py-1 bg-white border border-gray-100 rounded-full text-[10px] uppercase font-bold text-gray-400 shadow-sm">Nubank (Beta)</span>
            </div>
          </div>

          <div v-if="uploading" class="mt-8 flex flex-col items-center gap-4 text-primary animate-pulse">
            <div class="flex gap-1">
              <div class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce"></div>
              <div class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce [animation-delay:-.3s]"></div>
              <div class="w-1.5 h-1.5 bg-primary rounded-full animate-bounce [animation-delay:-.5s]"></div>
            </div>
            <span class="text-sm font-bold uppercase tracking-widest">Analisando Inteligência Financeira...</span>
          </div>
        </div>

        <!-- Step 2: Review -->
        <div v-if="step === 'review'" class="space-y-8 pb-32">
          <!-- Quick Config -->
          <div class="grid grid-cols-2 gap-4">
            <div class="relative group">
              <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Conta Destino</label>
              <select v-model="selectedAccountId" class="w-full h-12 px-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-primary/20 appearance-none font-medium text-gray-700 transition-all cursor-pointer">
                <option value="" disabled>Onde o dinheiro entrou?</option>
                <option v-for="acc in accountStore.accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
              </select>
              <div class="absolute right-4 top-9 pointer-events-none text-gray-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></div>
            </div>
            <div class="relative group">
              <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Categoria Sugerida</label>
              <select v-model="defaultCategoryId" class="w-full h-12 px-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-primary/20 appearance-none font-medium text-gray-700 transition-all cursor-pointer">
                <option value="">Automático</option>
                <option v-for="cat in categoryStore.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
              <div class="absolute right-4 top-9 pointer-events-none text-gray-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></div>
            </div>
          </div>

          <!-- Transaction Table UI Refined -->
          <div class="space-y-3">
            <div class="flex items-center justify-between px-2">
                <h4 class="text-sm font-bold text-gray-500 uppercase">Transações Detectadas</h4>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-gray-400">Selecionar Tudo</span>
                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="w-4 h-4 rounded-md border-gray-300 text-primary focus:ring-primary" />
                </div>
            </div>

            <div v-for="(tx, idx) in transactions" :key="idx" 
                 class="group bg-white border border-gray-100 p-4 rounded-2xl hover:border-primary/20 transition-all hover:shadow-lg hover:shadow-gray-200/50 flex items-center gap-4">
              
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
                <button @click="transactions.splice(idx, 1)" class="text-xs font-bold text-gray-300 hover:text-red-400 transition ml-auto flex items-center gap-1 mt-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Remover
                </button>
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
                    <p class="text-[10px] uppercase font-bold text-gray-400">Selecionados</p>
                </div>
            </div>
            <div class="flex items-center gap-2 bg-blue-50/50 px-4 py-2 rounded-2xl border border-blue-100/30">
                <div class="text-right">
                    <p class="text-[9px] uppercase font-black text-blue-400">Total no PDF</p>
                    <p class="font-black text-primary leading-none">{{ transactions.length }} Lançamentos</p>
                </div>
            </div>
        </div>

        <div class="flex gap-4">
          <button @click="step = 'upload'" class="flex-1 h-14 text-sm font-bold text-gray-500 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-3xl transition">Reiniciar</button>
          <button 
            @click="confirmImport" 
            :disabled="selectedCount === 0 || !selectedAccountId || saving"
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
const selectedAccountId = ref('')
const defaultCategoryId = ref('')
const selectAll = ref(true)

const selectedCount = computed(() => transactions.value.filter(t => t.selected).length)

watch(() => props.modelValue, (val) => {
  if (val) {
    step.value = 'upload'
    transactions.value = []
    uploading.value = false
    saving.value = false
    selectAll.value = true
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
      title: t.description // For creation
    }))
    step.value = 'review'
  } catch (e: any) {
    toast.error(e.response?.data?.error || 'Erro ao processar PDF.')
  } finally {
    uploading.value = false
  }
}

const toggleSelectAll = () => {
  transactions.value.forEach(t => t.selected = selectAll.value)
}

const formatDate = (dateStr: string) => {
  const [year, month, day] = dateStr.split('-')
  return `${day}/${month}/${year}`
}

const confirmImport = async () => {
  if (!selectedAccountId.value) return
  
  saving.value = true
  let successCount = 0
  let errorCount = 0

  const toImport = transactions.value.filter(t => t.selected)

  for (const tx of toImport) {
    try {
      await transactionRepository.createTransaction({
        type: tx.type,
        title: tx.description,
        amount: tx.amount,
        date: tx.date,
        accountId: Number(selectedAccountId.value),
        categoryId: defaultCategoryId.value ? Number(defaultCategoryId.value) : 1, // Fallback to 1 if no category
        status: 'PAID', // Imported items are usually already paid
        description: 'Importado via Extrato PDF'
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
