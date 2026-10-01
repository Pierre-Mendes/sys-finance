<template>
  <MainLayout>
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">Calendário Financeiro</h2>
            <p class="text-gray-500 mt-1 sm:text-lg">Acompanhe visualmente seus ganhos e gastos nos dias do mês atual.</p>
        </div>
        <div class="flex gap-2">
            <!-- Os botões antigos "Anterior / Próximo" foram integrados diretamente no grid do calendário abaixo para melhor consistência -->
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden relative">
        <!-- Loader Overlay -->
        <div v-if="isLoading" class="absolute inset-0 bg-white/60 backdrop-blur-sm flex flex-col items-center justify-center z-10 gap-3">
            <svg class="animate-spin w-8 h-8 text-primary" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span class="text-gray-500 font-semibold uppercase tracking-wider text-sm animate-pulse">CARREGANDO MAPA...</span>
        </div>

        <div class="bg-gray-50 flex items-center justify-between py-3 px-4 border-b border-gray-200">
            <button @click="changeMonth(-1)" class="p-2 hover:bg-gray-200 rounded-full transition text-gray-500 cursor-pointer">
                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <div class="flex items-center gap-2">
                <select v-model="selectedMonth" class="bg-transparent font-bold text-lg md:text-xl text-primary uppercase tracking-widest focus:outline-none cursor-pointer appearance-none text-center">
                    <option v-for="(m, i) in monthsNames" :key="i" :value="i">{{ m }}</option>
                </select>
                <select v-model="selectedYear" class="bg-transparent font-bold text-lg md:text-xl text-primary tracking-widest focus:outline-none cursor-pointer appearance-none">
                    <option v-for="y in yearsList" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
            <button @click="changeMonth(1)" class="p-2 hover:bg-gray-200 rounded-full transition text-gray-500 cursor-pointer">
                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>
        </div>

        <div class="grid grid-cols-7 border-b border-gray-200">
            <div v-for="day in ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']" :key="day" class="text-center py-3 text-sm font-semibold text-gray-500 uppercase">
                {{ day }}
            </div>
        </div>

        <div class="grid grid-cols-7 auto-rows-fr bg-gray-200 gap-px">
            <div v-for="(day, index) in calendarDays" :key="index" :class="['bg-white min-h-[120px] p-2 sm:p-3 relative group/day transition', day.isCurrentMonth ? 'hover:bg-gray-50' : 'bg-gray-50/50 opacity-60']">
                
                <span :class="['text-sm font-medium w-7 h-7 flex items-center justify-center rounded-full mb-2', day.isToday ? 'bg-primary text-white shadow-md' : 'text-gray-600 group-hover/day:bg-gray-100']">
                    {{ day.date }}
                </span>

                <div v-if="day.transactions.length > 0" class="flex flex-col gap-1.5 overflow-visible max-h-[80px] custom-scrollbar">
                    <div v-for="t in day.transactions.slice(0, 3)" :key="t.id" 
                         @mouseenter="showTooltip($event, t)"
                         @mouseleave="hideTooltip"
                         @mousemove="updateTooltipPos($event)"
                         @click="selectedTransaction = t"
                         :class="['group/tx relative text-xs font-semibold px-2 py-1 rounded truncate shadow-sm cursor-pointer', t.type === 'asset' || t.type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                        {{ t.type === 'asset' || t.type === 'income' ? '+' : '-' }}R$ {{ formatCurrency(t.amount) }}
                    </div>
                    <div v-if="day.transactions.length > 3" class="text-[10px] text-gray-500 font-medium text-center bg-gray-100 rounded px-1 mt-1">
                        +{{ day.transactions.length - 3 }} transações
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Teleport do Tooltip Dinâmico (Desktop Hover) -->
    <Teleport to="body">
        <div v-if="tooltipData && !selectedTransaction" 
             class="hidden sm:block fixed z-[9999] pointer-events-none w-max max-w-[200px] whitespace-normal bg-gray-900 text-white text-xs rounded px-2 py-1.5 shadow-lg transition-opacity duration-100"
             :style="{ left: tooltipPos.x + 'px', top: (tooltipPos.y - 12) + 'px', transform: 'translate(-50%, -100%)' }">
            <span class="font-bold flex items-center gap-1">
                <span class="w-2 h-2 rounded-full inline-block" :class="tooltipData.type === 'asset' || tooltipData.type === 'income' ? 'bg-green-400' : 'bg-red-400'"></span>
                {{ tooltipData.title }}
            </span>
            <span class="block text-gray-300 mt-0.5">{{ tooltipData.description || 'Sem descrição' }}</span>
            <span class="block mt-1 font-bold" :class="tooltipData.status === 'PAID' ? 'text-green-400' : 'text-amber-400'">{{ tooltipData.status === 'PAID' ? 'Pago' : tooltipData.status === 'CANCELED' ? 'Desconsiderada' : 'Pendente' }}</span>
        </div>
    </Teleport>

    <!-- Modal Responsivo Principal (Mobile/Click Viewer) -->
    <Teleport to="body">
        <div v-if="selectedTransaction" class="fixed inset-0 z-[10000] bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden animate-fadeIn relative">
                <div class="p-5 border-b border-gray-100 flex justify-between items-start" :class="selectedTransaction.type === 'asset' || selectedTransaction.type === 'income' ? 'bg-green-50' : 'bg-red-50'">
                     <div class="pr-6">
                         <div class="inline-flex items-center justify-center text-[10px] font-bold uppercase tracking-wider mb-2 px-2 py-0.5 rounded-full" :class="selectedTransaction.type === 'asset' || selectedTransaction.type === 'income' ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'">
                             {{ selectedTransaction.type === 'asset' || selectedTransaction.type === 'income' ? 'Entrada / Receita' : 'Saída / Despesa' }}
                         </div>
                         <h3 class="font-bold text-gray-900 text-lg leading-tight">{{ selectedTransaction.title }}</h3>
                     </div>
                     <button @click="selectedTransaction = null" class="text-gray-400 hover:text-gray-800 transition bg-white rounded-full p-1 shadow-sm absolute right-4 top-4">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                     </button>
                </div>
                <div class="p-5">
                     <p class="text-sm text-gray-600 mb-6 italic border-l-2 border-gray-200 pl-3">"{{ selectedTransaction.description || 'Sem discrição ou anotação' }}"</p>
                     
                     <div class="bg-gray-50 rounded-xl p-4 flex flex-col gap-3 text-sm">
                         <div class="flex justify-between items-center"><span class="text-gray-500">Valor Alocado:</span> 
                             <span class="text-xl font-bold" :class="selectedTransaction.type === 'asset' || selectedTransaction.type === 'income' ? 'text-green-600' : 'text-red-600'">
                                 {{ selectedTransaction.type === 'asset' || selectedTransaction.type === 'income' ? '+' : '-' }} R$ {{ formatCurrency(selectedTransaction.amount) }}
                             </span>
                         </div>
                         <hr class="border-gray-200"/>
                         <div class="flex justify-between"><span class="text-gray-500">Data de Competência:</span> <span class="font-medium text-gray-800">{{ selectedTransaction.date.split('-').reverse().join('/') }}</span></div>
                         <div class="flex justify-between"><span class="text-gray-500">Data do Retirada:</span> <span class="font-medium" :class="selectedTransaction.dueDate ? 'text-gray-800' : 'text-gray-400'">{{ selectedTransaction.dueDate ? selectedTransaction.dueDate.split('-').reverse().join('/') : '(Não agendada)' }}</span></div>
                         <div class="flex justify-between"><span class="text-gray-500">Prioridade SLA:</span> 
                             <span class="font-bold flex items-center gap-1">
                                 <span class="w-2 h-2 rounded-full" :class="{'bg-red-500': selectedTransaction.priority === 'HIGH', 'bg-blue-500': selectedTransaction.priority === 'NORMAL', 'bg-gray-400': selectedTransaction.priority === 'LOW'}"></span>
                                 {{ selectedTransaction.priority || 'NORMAL' }}
                             </span>
                         </div>
                         <div class="flex justify-between"><span class="text-gray-500">Status de Quitação:</span> 
                             <span class="font-bold px-2 py-0.5 rounded text-[10px] uppercase tracking-wider" :class="selectedTransaction.status === 'PAID' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">
                                 {{ selectedTransaction.status === 'PAID' ? 'Pago (Baixado)' : selectedTransaction.status === 'CANCELED' ? 'Desconsiderada' : 'Pendente' }}
                             </span>
                         </div>
                     </div>
                </div>
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button @click="selectedTransaction = null" class="px-5 py-2 text-sm bg-gray-800 text-white hover:bg-gray-700 rounded-lg font-medium transition cursor-pointer">Entendi</button>
                </div>
            </div>
        </div>
    </Teleport>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import MainLayout from '@/components/layout/MainLayout.vue'
import { useRouter } from 'vue-router'
import { transactionRepository } from '@/data/repositories/TransactionRepositoryImpl'

const router = useRouter()
const isLoading = ref(true)
const transactions = ref<any[]>([])

const selectedMonth = ref(new Date().getMonth())
const selectedYear = ref(new Date().getFullYear())

const monthsNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro']
// 16 anos totais sendo 5 pro passado e 10 pro futuro a partir do ano corrente
const yearsList = Array.from({ length: 16 }, (_, i) => new Date().getFullYear() - 5 + i)

const currentYear = computed(() => selectedYear.value)
const currentMonth = computed(() => selectedMonth.value)

const changeMonth = (diff: number) => {
    let newM = selectedMonth.value + diff
    if (newM > 11) { newM = 0; selectedYear.value++ }
    if (newM < 0) { newM = 11; selectedYear.value-- }
    selectedMonth.value = newM
}

const formatCurrency = (val: number) => Number(val).toLocaleString('pt-BR', { minimumFractionDigits: 2 })

// Tooltip e Modal JS Engine
const tooltipData = ref<any>(null)
const tooltipPos = ref({ x: 0, y: 0 })
const selectedTransaction = ref<any>(null)

const showTooltip = (e: MouseEvent, t: any) => {
    tooltipData.value = t
    tooltipPos.value = { x: e.clientX, y: e.clientY }
}
const updateTooltipPos = (e: MouseEvent) => {
    if (tooltipData.value) {
        tooltipPos.value = { x: e.clientX, y: e.clientY }
    }
}
const hideTooltip = () => {
    tooltipData.value = null
}

// Engine do Calendário
const calendarDays = computed(() => {
    const days = []
    const firstDay = new Date(currentYear.value, currentMonth.value, 1)
    const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0)
    
    // Adiciona dias em branco / padding do mês anterior
    for (let i = 0; i < firstDay.getDay(); i++) {
        const d = new Date(currentYear.value, currentMonth.value, 0 - i)
        days.unshift({ date: d.getDate(), isCurrentMonth: false, isToday: false, transactions: [] })
    }
    
    // Dias reais do mês
    const todayStr = new Date().toDateString()
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const d = new Date(currentYear.value, currentMonth.value, i)
        
        // Match transações deste dia perfeitamente considerando timezone bug do javascript
        const dayStrDate = [d.getFullYear(), ('0' + (d.getMonth() + 1)).slice(-2), ('0' + d.getDate()).slice(-2)].join('-')

        const matches = transactions.value.filter(t => {
            const dateToUse = (t.dueDate && t.dueDate !== '') ? t.dueDate : t.date;
            return dateToUse.startsWith(dayStrDate);
        })
        
        days.push({
            date: i,
            isCurrentMonth: true,
            isToday: d.toDateString() === todayStr,
            transactions: matches
        })
    }
    
    // Padding preencher final da grid (fazer fechar 35 ou 42 quadrados)
    const remaining = (days.length % 7 === 0) ? 0 : 7 - (days.length % 7)
    for (let i = 1; i <= remaining; i++) {
        days.push({ date: i, isCurrentMonth: false, isToday: false, transactions: [] })
    }

    return days
})

const fetchAllTransactions = async () => {
    isLoading.value = true;
    try {
        transactions.value = await transactionRepository.getTransactions()
    } catch (e: any) {
        if (e.response?.status === 401) { 
            toast.error('Credenciais expiradas'); router.push('/'); return;
        }
        toast.error('Erro de conexão ao carregar transações.')
    } finally {
        setTimeout(() => isLoading.value = false, 400); // Visual lock loader
    }
}

onMounted(() => {
    fetchAllTransactions()
})
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 4px;
}
</style>
