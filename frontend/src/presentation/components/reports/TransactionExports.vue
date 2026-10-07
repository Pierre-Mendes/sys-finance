<template>
  <details class="bg-white rounded-2xl border border-gray-100 shadow-sm group">
    <summary class="cursor-pointer select-none p-5 sm:p-6 flex items-center justify-between">
      <span>
        <span class="block text-lg font-bold text-gray-800">Exportar lançamentos</span>
        <span class="block text-xs text-gray-500">Lista de lançamentos em PDF ou CSV, com filtros próprios.</span>
      </span>
      <svg class="w-5 h-5 text-gray-500 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
    </summary>
    <div class="px-5 sm:px-6 pb-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <label class="block">
          <span class="block text-xs font-bold text-gray-500 uppercase mb-1">Data início</span>
          <input v-model="filters.from_date" type="date" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm" />
        </label>
        <label class="block">
          <span class="block text-xs font-bold text-gray-500 uppercase mb-1">Data fim</span>
          <input v-model="filters.to_date" type="date" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm" />
        </label>
        <label class="block">
          <span class="block text-xs font-bold text-gray-500 uppercase mb-1">Conta</span>
          <select v-model="filters.account_id" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm">
            <option :value="null">Todas as contas</option>
            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
          </select>
        </label>
        <label class="block">
          <span class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipo</span>
          <select v-model="filters.type" @change="filters.category_id = null" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm">
            <option :value="null">Entradas e saídas</option>
            <option value="income">Apenas entradas</option>
            <option value="bill">Apenas saídas</option>
          </select>
        </label>
        <label class="block">
          <span class="block text-xs font-bold text-gray-500 uppercase mb-1">Categoria</span>
          <select v-model="filters.category_id" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm">
            <option :value="null">Todas as categorias</option>
            <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </label>
      </div>
      <div class="mt-4 flex flex-col sm:flex-row gap-3 sm:justify-end">
        <button type="button" @click="resetFilters" class="text-sm font-medium text-gray-500 hover:text-gray-800 px-3 py-2">Limpar filtros</button>
        <button type="button" @click="generate('pdf')" :disabled="busy !== null" class="border border-gray-200 hover:bg-gray-50 font-semibold px-4 py-2.5 rounded-lg disabled:opacity-50">
          {{ busy === 'pdf' ? 'Gerando PDF...' : 'Baixar PDF' }}
        </button>
        <button type="button" @click="generate('csv')" :disabled="busy !== null" class="border border-gray-200 hover:bg-gray-50 font-semibold px-4 py-2.5 rounded-lg disabled:opacity-50">
          {{ busy === 'csv' ? 'Gerando CSV...' : 'Baixar CSV' }}
        </button>
      </div>
    </div>
  </details>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import api from '@/data/api/HttpClient'
import { downloadReport } from '@/data/api/reportApi'

const accounts = ref<any[]>([])
const categories = ref<any[]>([])
const busy = ref<'pdf' | 'csv' | null>(null)
const filters = reactive<{ from_date: string; to_date: string; account_id: number | null; category_id: number | null; type: string | null }>({
  from_date: '', to_date: '', account_id: null, category_id: null, type: null,
})

const filteredCategories = computed(() => (filters.type ? categories.value.filter(c => c.type === filters.type) : categories.value))

const resetFilters = () => Object.assign(filters, { from_date: '', to_date: '', account_id: null, category_id: null, type: null })

const generate = async (format: 'pdf' | 'csv') => {
  busy.value = format
  try {
    const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== null && v !== ''))
    await downloadReport(`/api/reports/${format}`, params, `lancamentos_${new Date().toISOString().slice(0, 10)}.${format}`)
    toast.success(`${format.toUpperCase()} gerado.`)
  } catch {
    toast.error('Não foi possível gerar o arquivo. Verifique os filtros.')
  } finally {
    busy.value = null
  }
}

onMounted(async () => {
  try {
    const [acc, cat] = await Promise.all([api.get('/api/accounts'), api.get('/api/categories?type=all')])
    accounts.value = acc.data.data || []
    categories.value = cat.data.data || []
  } catch { /* os filtros continuam vazios */ }
})
</script>
