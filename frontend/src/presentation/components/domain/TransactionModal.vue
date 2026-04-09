<template>
    <div v-if="modelValue" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 overflow-y-auto">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl relative my-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">{{ transactionToEdit ? 'Editar Lançamento' : 'Novo Lançamento' }}</h3>
        <form @submit.prevent="saveTransaction" class="space-y-4">
          
          <div class="flex gap-4">
              <label class="flex-1 border p-3 rounded-lg cursor-pointer transition flex items-center gap-2" :class="txForm.type === 'asset' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200 text-gray-500 hover:bg-gray-50'">
                  <input type="radio" v-model="txForm.type" value="asset" class="hidden" />
                  <span class="w-3 h-3 rounded-full bg-green-500 block"></span> Receita
              </label>
              <label class="flex-1 border p-3 rounded-lg cursor-pointer transition flex items-center gap-2" :class="txForm.type === 'bill' ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-200 text-gray-500 hover:bg-gray-50'">
                  <input type="radio" v-model="txForm.type" value="bill" class="hidden" />
                  <span class="w-3 h-3 rounded-full bg-red-500 block"></span> Despesa
              </label>
          </div>

          <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Título</label>
              <input v-model="txForm.title" type="text" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Ex: Salário Mensal, Conta de Luz..." />
          </div>

          <div class="flex gap-4">
              <div class="flex-1">
                  <label class="block text-sm font-medium text-gray-600 mb-1">Data (Competência)</label>
                  <input v-model="txForm.date" type="date" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-primary" />
              </div>
              <div class="flex-1">
                  <label class="block text-sm font-medium text-gray-600 mb-1">Valor (R$)</label>
                  <input v-model.number="txForm.amount" type="number" step="0.01" min="0" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="0.00" />
              </div>
          </div>

          <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 space-y-3">
              <div class="flex items-center justify-between">
                  <span class="text-sm font-semibold text-gray-700">Detalhes de Cobrança / Vencimento</span>
              </div>
              
              <div class="flex gap-3">
                  <div class="flex-1">
                      <label class="block text-xs font-medium text-gray-600 mb-1">Vencimento (Opcional)</label>
                      <input v-model="txForm.dueDate" type="date" class="w-full border border-gray-300 rounded p-1.5 focus:outline-none focus:ring-1 focus:ring-primary text-sm" />
                  </div>
                  <div class="flex-1">
                      <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                      <select v-model="txForm.status" class="w-full border border-gray-300 rounded p-1.5 focus:outline-none focus:ring-1 focus:ring-primary text-sm bg-white">
                          <option value="PAID">Já Pago (Em Conta)</option>
                          <option value="PENDING">Pendente (Aguardando)</option>
                      </select>
                  </div>
              </div>
              
              <div class="flex gap-3 pt-1">
                  <div class="flex-1">
                      <label class="block text-xs font-medium text-gray-600 mb-1">Recorrência</label>
                      <select v-model="txForm.recurrence_type" class="w-full border border-gray-300 rounded p-1.5 focus:outline-none focus:ring-1 focus:ring-primary text-sm bg-white">
                          <option value="NONE">Não Repete</option>
                          <option value="MONTHLY">Mensal</option>
                          <option value="YEARLY">Anual</option>
                      </select>
                      <p class="text-[10px] text-gray-500 mt-1 uppercase" v-if="txForm.recurrence_type !== 'NONE'">Cria novo mês ao dar baixa</p>
                  </div>
                  <div class="flex-1">
                      <label class="block text-xs font-medium text-gray-600 mb-1">Prioridade</label>
                      <select v-model="txForm.priority" class="w-full border border-gray-300 rounded p-1.5 focus:outline-none focus:ring-1 focus:ring-primary text-sm bg-white">
                          <option value="LOW">Baixa</option>
                          <option value="NORMAL">Normal</option>
                          <option value="HIGH">Alta (Urgente!)</option>
                      </select>
                  </div>
              </div>
          </div>

          <div>
              <label class="flex items-center gap-2 text-sm font-medium text-gray-600 mb-1">
                  Conta Bancária
              </label>
              <select v-model="txForm.accountId" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-primary bg-white text-gray-700 disabled:opacity-50">
                  <option value="" disabled>Selecione uma conta...</option>
                  <option v-for="acc in accountStore.accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
              </select>
          </div>

          <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Categoria</label>
              <select v-model="txForm.categoryId" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-primary bg-white text-gray-700 disabled:opacity-50">
                  <option value="" disabled>Selecione uma categoria...</option>
                  <option v-for="cat in availableCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
          </div>
          
          <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Descrição Opcional</label>
              <textarea v-model="txForm.description" rows="2" class="w-full border border-gray-300 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-primary text-gray-700" placeholder="Anotações adicionais..."></textarea>
          </div>

          <div v-if="txForm.type === 'bill' && !transactionToEdit" class="pt-2 border-t border-gray-100">
              <label class="flex items-center gap-2 cursor-pointer mb-2">
                  <input type="checkbox" v-model="hasSplits" class="rounded text-primary focus:ring-primary h-4 w-4" />
                  <span class="text-sm font-semibold text-gray-700">Dividir Despesa (Rateio com outros Espaços)</span>
              </label>
              
              <div v-if="hasSplits" class="bg-gray-50 border border-gray-200 rounded-lg p-3 space-y-3 mt-2">
                  <div v-for="(split, index) in txForm.splits" :key="index" class="flex flex-col gap-2 p-2 border border-gray-200 rounded bg-white shadow-sm">
                      <div class="flex items-center justify-between">
                          <span class="text-xs font-bold text-gray-500 uppercase">Rateio #{{ index + 1 }}</span>
                          <button type="button" @click="txForm.splits.splice(index, 1)" class="text-red-500 hover:text-red-700 text-xs font-semibold">Remover</button>
                      </div>
                      <div class="flex gap-2">
                          <div class="flex-1">
                              <label class="block text-xs font-medium text-gray-600 mb-1">Espaço Destino</label>
                              <select v-model="split.workspaceId" @change="fetchCrossAccounts(split.workspaceId)" class="w-full border border-gray-300 rounded p-1.5 focus:outline-none focus:ring-1 focus:ring-primary text-sm bg-white" required>
                                  <option value="" disabled>Selecione um Workspace...</option>
                                  <option v-for="w in workspaceStore.workspaces" :key="w.id" :value="w.id" :disabled="w.id == workspaceStore.activeWorkspaceId">{{ w.name }}</option>
                              </select>
                          </div>
                      </div>
                      <div class="flex gap-2" v-if="split.workspaceId && crossAccounts[split.workspaceId]">
                          <div class="flex-1">
                              <label class="block text-xs font-medium text-gray-600 mb-1">Conta (no Destino)</label>
                              <select v-model="split.accountId" class="w-full border border-gray-300 rounded p-1.5 focus:outline-none focus:ring-1 focus:ring-primary text-sm bg-white" required>
                                  <option value="" disabled>Conta deduzida...</option>
                                  <option v-for="acc in crossAccounts[split.workspaceId]" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                              </select>
                          </div>
                          <div class="w-1/3">
                              <label class="block text-xs font-medium text-gray-600 mb-1">Valor Lançado</label>
                              <input v-model.number="split.amount" type="number" step="0.01" min="0" class="w-full border border-gray-300 rounded p-1.5 focus:outline-none focus:ring-1 focus:ring-primary text-sm" required />
                          </div>
                      </div>
                  </div>
                  <button type="button" @click="txForm.splits.push({ workspaceId: '', accountId: '', amount: null })" class="w-full py-2 bg-white text-primary border border-primary border-dashed hover:bg-blue-50 text-sm font-semibold rounded transition text-center shadow-sm">
                      + Adicionar Conta de Espelho
                  </button>
              </div>
          </div>

          <div class="flex justify-end gap-3 pt-3">
            <button type="button" @click="close" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition cursor-pointer font-medium">Cancelar</button>
            <button type="submit" class="px-4 py-2 bg-primary hover:bg-blue-600 text-white font-medium rounded-lg shadow transition cursor-pointer">Salvar</button>
          </div>
        </form>
      </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { toast } from 'vue3-toastify'
import { transactionRepository } from '@/data/repositories/TransactionRepositoryImpl'
import { useCategoryStore } from '@/presentation/store/categoryStore'
import { useAccountStore } from '@/presentation/store/accountStore'
import { useWorkspaceStore } from '@/presentation/store/workspaceStore'
import type { TransactionType, TransactionStatus, TransactionPriority, TransactionRecurrence } from '@/core/domain/Transaction'
import api from '@/data/api/HttpClient'

const props = defineProps<{
    modelValue: boolean
    transactionToEdit: any | null
}>()

const emit = defineEmits(['update:modelValue', 'saved'])

const categoryStore = useCategoryStore()
const accountStore = useAccountStore()
const workspaceStore = useWorkspaceStore()

const crossAccounts = ref<{ [wsId: number]: any[] }>({})
const hasSplits = ref(false)

const txForm = ref<{
    id?: number,
    type: TransactionType,
    title: string,
    date: string,
    amount: number | null,
    categoryId: string | number,
    accountId: string | number,
    description: string,
    dueDate: string,
    status: TransactionStatus,
    priority: TransactionPriority,
    recurrence_type: TransactionRecurrence,
    splits: any[]
}>({
    type: 'bill', title: '', date: new Date().toISOString().split('T')[0],
    amount: null, categoryId: '', accountId: '', description: '',
    dueDate: '', status: 'PAID', priority: 'NORMAL', recurrence_type: 'NONE', splits: []
})

const availableCategories = computed(() => {
    return categoryStore.categories.filter(c => 
        (txForm.value.type === 'asset' || txForm.value.type === 'income' ? c.type === 'asset' || c.type === 'income' : c.type === 'expense' || c.type === 'bill')
    )
})

watch(() => txForm.value.type, (newType) => {
    const currentCat = categoryStore.categories.find(c => c.id == txForm.value.categoryId)
    if (currentCat) {
        if ((newType === 'asset' && currentCat.type !== 'asset' && currentCat.type !== 'income') || 
            (newType === 'bill' && currentCat.type !== 'expense' && currentCat.type !== 'bill')) {
            txForm.value.categoryId = ''
        }
    }
})

watch(() => props.modelValue, async (val) => {
    if (val) {
        if (categoryStore.categories.length === 0) await categoryStore.fetchCategories()
        if (accountStore.accounts.length === 0) await accountStore.fetchAccounts()
        if (workspaceStore.workspaces.length === 0) await workspaceStore.fetchWorkspaces()
        
        hasSplits.value = false
        if (props.transactionToEdit) {
            const t = props.transactionToEdit
            txForm.value = {
                id: t.id,
                type: t.type, title: t.title, date: t.date, amount: t.amount,
                categoryId: t.categoryId, accountId: t.accountId, description: t.description || '',
                dueDate: t.dueDate || t.due_date || '', status: t.status || 'PAID', 
                priority: t.priority || 'NORMAL', recurrence_type: t.recurrence_type || t.recurrenceType || 'NONE',
                splits: []
            }
        } else {
            txForm.value = {
                type: 'bill', title: '', date: new Date().toISOString().split('T')[0],
                amount: null, categoryId: '', accountId: '', description: '',
                dueDate: '', status: 'PAID', priority: 'NORMAL', recurrence_type: 'NONE', splits: []
            }
        }
    }
})

const fetchCrossAccounts = async (wsId: number) => {
    if (!wsId || crossAccounts.value[wsId]) return;
    try {
        const { data } = await api.get('/api/accounts', { headers: { 'X-Workspace-Id': wsId }});
        crossAccounts.value[wsId] = data.data;
    } catch(e) {}
}

const close = () => {
    emit('update:modelValue', false)
}

const saveTransaction = async () => {
    try {
        const payload: any = { ...txForm.value }
        if (!hasSplits.value || payload.type !== 'bill') {
            payload.splits = [];
        }
        
        // Formata dueDate para snake_case pro backend de legado onde precisar (dto)
        payload.due_date = payload.dueDate;
        
        if (payload.id) {
            await transactionRepository.updateTransaction(payload.id, payload)
            toast.success('Lançamento atualizado!')
        } else {
            await transactionRepository.createTransaction(payload)
            toast.success('Lançamento inserido!')
        }
        emit('saved')
        close()
    } catch (e: any) {
        toast.error(e.response?.data?.error || 'Erro ao processar.')
    }
}
</script>
