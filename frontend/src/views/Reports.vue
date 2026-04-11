<template>
  <MainLayout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Exportação de Relatórios</h2>
        <p class="text-gray-500 mt-1 sm:text-lg">Gere balanços e compilados financeiros para impressão ou análise profunda.</p>
    </div>

    <div class="mb-8 p-6 bg-white rounded-2xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
            Filtros do Relatório
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Data Início</label>
                <input v-model="filters.from_date" type="date" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none transition" />
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Data Fim</label>
                <input v-model="filters.to_date" type="date" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none transition" />
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Conta</label>
                <select v-model="filters.account_id" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none transition">
                    <option :value="null">Todas as Contas</option>
                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Categoria</label>
                <select v-model="filters.category_id" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none transition">
                    <option :value="null">Todas as Categorias</option>
                    <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">
                        {{ cat.name }} ({{ cat.type === 'income' ? 'Rec.' : 'Desp.' }})
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Tipo</label>
                <select v-model="filters.type" @change="filters.category_id = null" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none transition">
                    <option :value="null">Entradas e Saídas</option>
                    <option value="income">Apenas Entradas</option>
                    <option value="bill">Apenas Saídas</option>
                </select>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button @click="resetFilters" class="text-xs font-bold text-gray-400 hover:text-primary transition uppercase tracking-widest">Limpar Filtros</button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- PDF Card -->
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:shadow-md transition group">
            <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition duration-500">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Relatório Executivo (PDF)</h3>
            <p class="text-gray-500 text-sm mb-8 leading-relaxed">Gera um arquivo PDF bem formatado contendo as transações filtradas e o balanço consolidado final.</p>
            <button @click="generate('pdf')" :disabled="isGeneratingPdf" class="mt-auto w-full max-w-[250px] bg-red-500 hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg font-semibold flex items-center justify-center gap-2 transition shadow-lg shadow-red-100 overflow-hidden relative">
                <div v-if="isGeneratingPdf" class="absolute inset-0 bg-red-600 flex items-center justify-center z-10 gap-2">
                    <svg class="animate-spin w-5 h-5 text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Processando...
                </div>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span v-if="!isGeneratingPdf">Exportar PDF</span>
            </button>
        </div>

        <!-- CSV Card -->
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:shadow-md transition group">
            <div class="w-20 h-20 bg-green-50 text-green-500 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition duration-500">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Exportação Tabular (CSV)</h3>
            <p class="text-gray-500 text-sm mb-8 leading-relaxed">Estrutura tabular otimizada para Excel e programas de contabilidade, respeitando os filtros selecionados.</p>
            <button @click="generate('csv')" :disabled="isGeneratingCsv" class="mt-auto w-full max-w-[250px] bg-green-500 hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg font-semibold flex items-center justify-center gap-2 transition shadow-lg shadow-green-100 overflow-hidden relative">
                <div v-if="isGeneratingCsv" class="absolute inset-0 bg-green-600 flex items-center justify-center z-10 gap-2">
                    <svg class="animate-spin w-5 h-5 text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Gerando Planilha...
                </div>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span v-if="!isGeneratingCsv">Download CSV</span>
            </button>
        </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'
import MainLayout from '@/components/layout/MainLayout.vue'
import api from '@/data/api/HttpClient'

const isGeneratingPdf = ref(false)
const isGeneratingCsv = ref(false)

const accounts = ref<any[]>([])
const categories = ref<any[]>([])

const filters = reactive({
    from_date: '',
    to_date: '',
    account_id: null,
    category_id: null,
    type: null
})

const filteredCategories = computed(() => {
    if (!filters.type) return categories.value;
    return categories.value.filter(c => c.type === filters.type);
})

onMounted(async () => {
    try {
        const [accRes, catRes] = await Promise.all([
            api.get('/api/accounts'),
            api.get('/api/categories?type=all')
        ]);
        accounts.value = accRes.data.data || [];
        categories.value = catRes.data.data || [];
    } catch (e) {}
})

const resetFilters = () => {
    filters.from_date = '';
    filters.to_date = '';
    filters.account_id = null;
    filters.category_id = null;
    filters.type = null;
}

const generate = async (type: 'pdf' | 'csv') => {
    if (type === 'pdf') isGeneratingPdf.value = true;
    else isGeneratingCsv.value = true;

    try {
        const token = localStorage.getItem('token');
        
        // Clean filters to only send non-null values
        const params: any = {};
        if (filters.from_date) params.from_date = filters.from_date;
        if (filters.to_date) params.to_date = filters.to_date;
        if (filters.account_id) params.account_id = filters.account_id;
        if (filters.category_id) params.category_id = filters.category_id;
        if (filters.type) params.type = filters.type;

        const response = await axios.get(`/api/reports/${type}`, {
            headers: { Authorization: `Bearer ${token}` },
            params: params,
            responseType: 'blob' 
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `relatorio_${new Date().getTime()}.${type}`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        toast.success(`Relatório ${type.toUpperCase()} gerado com sucesso!`);
    } catch (e) {
        toast.error('Ocorreu um erro gerando o relatório. Verifique os filtros.');
    } finally {
        if (type === 'pdf') isGeneratingPdf.value = false;
        else isGeneratingCsv.value = false;
    }
}
</script>
