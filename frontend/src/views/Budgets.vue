<template>
  <MainLayout>
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h2 class="text-2xl sm:text-3xl font-semibold text-gray-800">Meus Orçamentos</h2>
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
          <button @click="openCreateModal" class="w-full sm:w-auto bg-primary hover:bg-blue-600 px-5 py-2.5 rounded-lg text-white font-medium shadow transition cursor-pointer flex items-center justify-center gap-2 whitespace-nowrap">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>Novo Orçamento
          </button>
        </div>
      </div>

      <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex gap-3 text-blue-800 text-sm mb-6 items-start shadow-sm">
         <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
         <div>
            <span class="font-bold block mb-1">Como funcionam as metas mensais?</span>
            Os orçamentos agem como limites de contenção. Estipule um limite máximo para uma Categoria e o sistema calculará em tempo real seu consumo dentro do mês corrente. O acompanhamento rigoroso ajuda a poupar!
         </div>
      </div>

      <!-- Predição de Economia -->
      <div v-if="!isLoading && totalIncome > 0" class="border rounded-2xl p-6 mb-6 flex flex-col sm:flex-row items-center justify-between shadow-sm transition" :class="predictedSavings >= 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'">
          <div>
              <h3 class="text-lg font-bold flex items-center gap-2" :class="predictedSavings >= 0 ? 'text-emerald-800' : 'text-red-800'">
                  <svg v-if="predictedSavings >= 0" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                  <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                  Projeção de Economia Mensal
              </h3>
              <p class="text-sm mt-1" :class="predictedSavings >= 0 ? 'text-emerald-700' : 'text-red-700'">
                 {{ predictedSavings >= 0 
                    ? 'Se você respeitar estritamente as metas orçamentárias estabelecidas, você terminará o mês sobrando este valor de suas receitas registradas.' 
                    : 'Atenção! A soma de seus limites de gastos está extrapolando as receitas que você teve entrada este mês.' }}
              </p>
          </div>
          <div class="mt-4 sm:mt-0 px-6 py-3 bg-white rounded-xl shadow-inner font-black text-2xl" :class="predictedSavings >= 0 ? 'text-emerald-600' : 'text-red-600'">
              R$ {{ formatCurrency(predictedSavings) }}
          </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden w-full">
        <table class="w-full text-left">
          <thead class="bg-gray-50 text-gray-600 text-sm font-semibold uppercase border-b border-gray-100">
            <tr>
              <th class="p-4 hidden sm:table-cell">Categoria</th>
              <th class="p-4">Desempenho</th>
              <th class="p-4 w-40 text-center">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in budgets" :key="b.id" class="border-b border-gray-50 hover:bg-gray-50 transition">
              <td class="p-4 text-gray-800 font-medium whitespace-nowrap hidden sm:table-cell">{{ b.categoryName }}</td>
              <td class="p-4 w-full">
                  <div class="flex justify-between text-sm mb-1">
                      <span class="font-semibold text-gray-700 sm:hidden">{{ b.categoryName }}</span>
                      <span class="text-gray-500 font-medium">R$ {{ formatCurrency(b.spent) }} de <span class="font-bold">R$ {{ formatCurrency(b.amount) }}</span></span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2.5">
                      <div :class="['h-2.5 rounded-full', getProgressColor(b.spent, b.amount)]" :style="`width: ${Math.min((b.spent / b.amount) * 100, 100)}%`"></div>
                  </div>
              </td>
              <td class="p-4 text-center whitespace-nowrap">
                <div class="flex gap-4 justify-end sm:justify-center items-center">
                    <button @click="openEditModal(b)" class="text-blue-500 hover:text-blue-700 transition cursor-pointer font-medium flex items-center gap-1.5" title="Editar Meta">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </button>
                    <button @click="deleteBudget(b)" class="text-red-500 hover:text-red-700 transition cursor-pointer font-medium flex items-center gap-1.5" title="Excluir">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>
              </td>
            </tr>
            <TableLoader v-if="isLoading" :columns="3" message="CARREGANDO ORÇAMENTOS..." />
            <tr v-if="budgets.length === 0 && !isLoading">
              <td colspan="3" class="p-8 text-center text-gray-500">Nenhum orçamento mensal definido.</td>
            </tr>
          </tbody>
        </table>
      </div>

    <!-- Modal Adicionar/Editar -->
    <div v-if="openModal" class="fixed inset-0 bg-black/50 flex flex-col items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl">
        <h3 class="text-xl font-bold text-gray-800 mb-4">{{ editingId ? 'Editar Orçamento' : 'Novo Orçamento' }}</h3>
        
        <form @submit.prevent="saveBudget">
          <div v-if="!editingId">
              <label class="block text-sm font-medium text-gray-600 mb-1">Categoria de Despesa</label>
              <select v-model="form.categoryId" required class="w-full border border-gray-300 rounded-lg p-2.5 mb-4 focus:outline-none focus:ring-2 focus:ring-primary bg-white text-gray-700">
                  <option value="" disabled>Selecione...</option>
                  <option v-for="cat in expenseCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
          </div>

          <label class="block text-sm font-medium text-gray-600 mb-1">Meta Máxima (R$)</label>
          <input v-model.number="form.amount" type="number" step="0.01" min="0" required class="w-full border border-gray-300 rounded-lg p-2.5 mb-5 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" placeholder="0.00" />
          
          <div class="flex justify-end gap-3">
            <button type="button" @click="openModal = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition cursor-pointer font-medium">Cancelar</button>
            <button type="submit" class="px-4 py-2 bg-primary hover:bg-blue-600 text-white font-medium rounded-lg shadow transition cursor-pointer">Salvar</button>
          </div>
        </form>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/data/api/HttpClient'
import { toast } from 'vue3-toastify'
import Swal from 'sweetalert2'
import MainLayout from '@/components/layout/MainLayout.vue'
import TableLoader from '@/components/ui/TableLoader.vue'

const router = useRouter()
const budgets = ref<any[]>([])
const expenseCategories = ref<any[]>([])
const isLoading = ref(true)

const openModal = ref(false)
const editingId = ref<number | null>(null)

const form = ref({
    categoryId: '',
    amount: null as number | null
})

const totalIncome = ref(0)
const predictedSavings = computed(() => {
    const sumBudgets = budgets.value.reduce((acc, b) => acc + Number(b.amount), 0)
    return totalIncome.value - sumBudgets
})

const formatCurrency = (val: number) => {
    return Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const getProgressColor = (spent: number, amount: number) => {
    const p = spent / amount;
    if (p < 0.6) return 'bg-green-500';
    if (p < 0.9) return 'bg-yellow-400';
    return 'bg-red-500';
}

const fetchData = async () => {
    isLoading.value = true;
    try {
        
        const [budRes, catRes, dashRes] = await Promise.all([
            api.get('/api/budgets'),
            api.get('/api/categories?type=expense'),
            api.get('/api/dashboard')
        ])

        budgets.value = budRes.data.data
        expenseCategories.value = catRes.data.data
        totalIncome.value = dashRes.data.data.totalIncome || 0

    } catch (e: any) {
        if (e.response?.status === 401) { 
            toast.error('Credenciais expiradas.');
            router.push('/'); 
            return;
        }
        toast.error('Erro ao ler orçamentos.')
    } finally {
        isLoading.value = false;
    }
}

onMounted(fetchData)

const openCreateModal = () => {
    editingId.value = null;
    form.value = { categoryId: '', amount: null };
    openModal.value = true;
}

const openEditModal = (b: any) => {
    editingId.value = b.id;
    form.value = { categoryId: b.categoryId, amount: b.amount };
    openModal.value = true;
}

const saveBudget = async () => {
    try {
        
        if (editingId.value) {
            await api.put(`/api/budgets/${editingId.value}`, { amount: form.value.amount })
            toast.success('Meta atualizada!')
        } else {
            await api.post('/api/budgets', form.value)
            toast.success('Orçamento criado!')
        }
        openModal.value = false
        fetchData()
    } catch (e: any) {
        toast.error(e.response?.data?.error || 'Erro ao processar.')
    }
}

const deleteBudget = async (b: any) => {
    const result = await Swal.fire({
        title: 'Você tem certeza?',
        text: `Deseja remover o limite de orçamento para "${b.categoryName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    });
    if (!result.isConfirmed) return;

    try {
        await api.delete(`/api/budgets/${b.id}`)
        toast.success('Orçamento removido.')
        fetchData()
    } catch (e: any) {
        toast.error('Erro ao excluir.')
    }
}
</script>
