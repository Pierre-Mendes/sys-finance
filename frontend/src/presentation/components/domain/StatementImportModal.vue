<template>
  <div v-if="modelValue" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 overflow-y-auto">
    <div class="bg-white rounded-2xl p-6 w-full max-w-4xl shadow-2xl relative my-8">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold text-gray-800">Importar Extrato PDF</h3>
        <button @click="close" class="text-gray-400 hover:text-gray-600 transition">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>

      <!-- Step 1: Upload -->
      <div v-if="step === 'upload'" class="space-y-6">
        <div 
          @dragover.prevent="dragOver = true" 
          @dragleave.prevent="dragOver = false" 
          @drop.prevent="handleDrop"
          :class="['border-2 border-dashed rounded-xl p-12 text-center transition cursor-pointer', dragOver ? 'border-primary bg-blue-50' : 'border-gray-300 hover:border-primary hover:bg-gray-50']"
          @click="fileInput?.click()"
        >
          <input type="file" ref="fileInput" class="hidden" accept="application/pdf" @change="handleFileSelect" />
          <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
          <p class="text-lg font-medium text-gray-700">Arraste seu PDF aqui ou clique para selecionar</p>
          <p class="text-sm text-gray-500 mt-2">Suporte atual: Itaú e Sicoob</p>
        </div>

        <div v-if="uploading" class="flex items-center justify-center gap-3 text-primary font-medium">
          <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Processando inteligência do extrato...
        </div>
      </div>

      <!-- Step 2: Review -->
      <div v-if="step === 'review'" class="space-y-6">
        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col md:flex-row gap-4 items-end">
          <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-700 mb-1">Conta de Destino</label>
            <select v-model="selectedAccountId" class="w-full border border-gray-300 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-primary bg-white">
              <option value="" disabled>Selecione a conta bancária...</option>
              <option v-for="acc in accountStore.accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
            </select>
          </div>
          <div class="flex-1 w-full">
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoria Padrão (Opcional)</label>
            <select v-model="defaultCategoryId" class="w-full border border-gray-300 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-primary bg-white">
              <option value="">Nenhuma (Vários)</option>
              <option v-for="cat in categoryStore.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto border border-gray-200 rounded-xl">
          <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 font-semibold">
              <tr>
                <th class="p-3 w-10"><input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="rounded text-primary" /></th>
                <th class="p-3">Data</th>
                <th class="p-3">Descrição</th>
                <th class="p-3">Valor</th>
                <th class="p-3 text-right">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(tx, idx) in transactions" :key="idx" class="border-b border-gray-100 hover:bg-gray-50 transition">
                <td class="p-3"><input type="checkbox" v-model="tx.selected" class="rounded text-primary" /></td>
                <td class="p-3 whitespace-nowrap">{{ formatDate(tx.date) }}</td>
                <td class="p-3">
                  <input v-model="tx.description" class="w-full bg-transparent border-b border-transparent focus:border-primary focus:outline-none" />
                </td>
                <td :class="['p-3 font-semibold', tx.type === 'asset' ? 'text-green-600' : 'text-red-500']">
                  {{ tx.type === 'asset' ? '+' : '-' }} R$ {{ tx.amount.toLocaleString('pt-BR', {minimumFractionDigits: 2}) }}
                </td>
                <td class="p-3 text-right">
                  <button @click="transactions.splice(idx, 1)" class="text-red-400 hover:text-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex justify-between items-center pt-4">
          <p class="text-sm text-gray-500">
            {{ selectedCount }} de {{ transactions.length }} transações selecionadas
          </p>
          <div class="flex gap-3">
            <button @click="step = 'upload'" class="px-6 py-2.5 text-gray-600 hover:bg-gray-100 rounded-lg transition font-medium">Voltar</button>
            <button 
              @click="confirmImport" 
              :disabled="selectedCount === 0 || !selectedAccountId || saving"
              class="px-6 py-2.5 bg-primary hover:bg-blue-600 text-white font-medium rounded-lg shadow transition disabled:opacity-50 flex items-center gap-2"
            >
              <template v-if="saving">
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Salvando...
              </template>
              <template v-else>
                Importar Selecionados
              </template>
            </button>
          </div>
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
