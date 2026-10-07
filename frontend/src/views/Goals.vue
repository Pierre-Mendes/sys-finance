<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useGoalStore, type Goal } from '@/presentation/store/goalStore'
import { useWorkspaceStore } from '@/presentation/store/workspaceStore'
import { useAccountStore } from '@/presentation/store/accountStore'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoaderSpinner from '@/components/ui/LoaderSpinner.vue'
import { toast } from 'vue3-toastify'
import Swal from 'sweetalert2'
import CapiMascot from '@/components/brand/CapiMascot.vue'

const store = useGoalStore()
const workspaceStore = useWorkspaceStore()
const accountStore = useAccountStore()

const showModal = ref(false)
const editingGoalId = ref<number | null>(null)
const goalForm = ref({
  title: '',
  targetAmount: 0,
  targetDate: '',
  sharedWithWorkspaceId: null as number | null,
  accountId: null as number | null,
  isFavorite: false
})

const showContributionModal = ref(false)
const selectedGoal = ref<Goal | null>(null)
const contributionAmount = ref(0)
const contributionAccountId = ref<number | null>(null)
const contributionDesc = ref('')

onMounted(async () => {
  await store.fetchGoals()
  await accountStore.fetchAccounts()
  if (workspaceStore.workspaces.length === 0) {
    await workspaceStore.fetchWorkspaces()
  }
})

const openCreate = () => {
  editingGoalId.value = null
  goalForm.value = { title: '', targetAmount: 0, targetDate: '', sharedWithWorkspaceId: null, accountId: null, isFavorite: false }
  showModal.value = true
}

const openEdit = (goal: Goal) => {
  editingGoalId.value = goal.id
  goalForm.value = {
    title: goal.title,
    targetAmount: goal.targetAmount,
    targetDate: goal.targetDate ? goal.targetDate.split(' ')[0] : '', // Format for date input
    sharedWithWorkspaceId: goal.sharedWithWorkspaceId,
    accountId: goal.accountId,
    isFavorite: goal.isFavorite
  }
  showModal.value = true
}

const saveGoal = async () => {
  if (!goalForm.value.title || goalForm.value.targetAmount <= 0) {
      toast.warn("Por favor, preencha o título e um valor alvo válido.")
      return
  }
  
  let success = false
  if (editingGoalId.value) {
    success = await store.updateGoal(editingGoalId.value, goalForm.value)
  } else {
    success = await store.createGoal(goalForm.value)
  }

  if (success) {
    showModal.value = false
    toast.success(editingGoalId.value ? "Meta atualizada!" : "Meta criada com sucesso!")
  }
}

const confirmDelete = async (id: number) => {
    const result = await Swal.fire({
        title: 'Excluir Meta?',
        text: 'Tem certeza que deseja excluir esta meta? O histórico de aportes será mantido como despesa nas contas, mas o objetivo deixará de existir.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#C2410C',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    });
    
    if (result.isConfirmed) {
        const success = await store.deleteGoal(id)
        if (success) toast.success("Meta removida.")
    }
}

const toggleFavorite = async (goal: Goal) => {
    const success = await store.updateGoal(goal.id, {
        ...goal,
        isFavorite: !goal.isFavorite
    })
    if (success) {
        toast.info(goal.isFavorite ? "Removida dos favoritos" : "Meta favoritada!")
    }
}

const openContribution = (goal: Goal) => {
  selectedGoal.value = goal
  contributionAccountId.value = goal.accountId
  showContributionModal.value = true
}

const saveContribution = async () => {
  if (contributionAmount.value <= 0) return
  const success = await store.addContribution(selectedGoal.value!.id, contributionAmount.value, contributionAccountId.value, contributionDesc.value)
  if (success) {
    showContributionModal.value = false
    contributionAmount.value = 0
    contributionAccountId.value = null
    contributionDesc.value = ''
    toast.success("Aporte registrado!")
    // Force refresh accounts if an account was used
    if (contributionAccountId.value || selectedGoal.value?.accountId) {
        await accountStore.forceRefreshAccounts()
    }
  }
}

const getPercentage = (accumulated: number, target: number) => {
  if (!target || target <= 0) return 0
  const p = (accumulated / target) * 100
  return Math.min(Math.round(p), 100)
}

const getAccountName = (id: number | null) => {
    if (!id) return null
    return accountStore.accounts.find(a => a.id === id)?.name
}
</script>

<template>
  <MainLayout>
    <div class="px-4 py-8 max-w-7xl mx-auto">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 mb-10">
        <div>
          <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Metas & Conquistas</h2>
          <p class="text-gray-500 mt-2 text-lg">Transforme seus sonhos em planos concretos.</p>
        </div>
        <button @click="openCreate" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 px-6 py-3 rounded-2xl text-white font-bold shadow-lg shadow-indigo-200 transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2 whitespace-nowrap">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
          Nova Meta
        </button>
      </div>

      <div v-if="store.isLoading && store.goals.length === 0" class="flex justify-center py-20">
        <LoaderSpinner class="w-12 h-12 text-indigo-600" />
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
        <div v-for="goal in store.goals" :key="goal.id" class="group bg-white rounded-3xl p-8 shadow-sm border border-gray-100 hover:shadow-xl hover:border-indigo-100 transition-all duration-300 relative overflow-hidden">
          
          <div class="relative">
            <div class="flex justify-between items-start mb-6">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="text-xl font-bold text-gray-900">{{ goal.title }}</h3>
                    <button @click="toggleFavorite(goal)" class="transition-colors duration-300" :class="goal.isFavorite ? 'text-amber-400' : 'text-gray-200 hover:text-amber-200'">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-0.5 text-xs bg-indigo-50 text-indigo-600 rounded-full font-black uppercase tracking-tighter" v-if="goal.sharedWithWorkspaceId">Compartilhada</span>
                    <span class="px-2 py-0.5 text-xs bg-emerald-50 text-emerald-600 rounded-full font-black uppercase tracking-tighter" v-if="goal.accountId">Vinculada: {{ getAccountName(goal.accountId) }}</span>
                    <router-link v-if="goal.accountId" :to="`/statement?accountId=${goal.accountId}`" class="px-2 py-0.5 text-xs bg-indigo-50 text-indigo-600 rounded-full font-black uppercase tracking-tighter hover:bg-indigo-100 transition-colors">
                        Ver Histórico
                    </router-link>
                </div>
              </div>
              <div class="flex gap-1">
                <button @click="openEdit(goal)" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition" title="Editar">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                </button>
                <button @click="confirmDelete(goal.id)" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition" title="Excluir">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m4-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
              </div>
            </div>

            <div class="mb-8">
              <div class="flex justify-between text-sm mb-3">
                <span class="text-gray-500 font-medium">Progresso Atual</span>
                <span class="font-black text-indigo-600">{{ getPercentage(goal.accumulatedAmount, goal.targetAmount) }}%</span>
              </div>
              <div class="w-full bg-gray-100 rounded-full h-4 overflow-hidden p-1 shadow-inner">
                <div class="bg-gradient-to-r from-indigo-500 to-blue-500 h-2 rounded-full transition-all duration-1000 ease-out" :style="{ width: getPercentage(goal.accumulatedAmount, goal.targetAmount) + '%' }"></div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-8 bg-gray-50 rounded-2xl p-4">
              <div>
                <p class="text-xs text-gray-500 uppercase font-black tracking-widest mb-1">Acumulado</p>
                <p class="text-lg font-extrabold text-gray-900">R$ {{ (goal.accumulatedAmount || 0).toLocaleString() }}</p>
              </div>
              <div class="text-right border-l border-gray-200 pl-4">
                <p class="text-xs text-gray-500 uppercase font-black tracking-widest mb-1">Objetivo</p>
                <p class="text-lg font-extrabold text-gray-900">R$ {{ (goal.targetAmount || 0).toLocaleString() }}</p>
              </div>
            </div>

            <div v-if="goal.targetDate" class="mb-8 flex items-center gap-3 text-gray-500">
               <svg class="w-5 h-5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
               <span class="text-sm font-medium">Até {{ new Date(goal.targetDate).toLocaleDateString() }}</span>
            </div>

            <button @click="openContribution(goal)" class="w-full py-4 bg-white hover:bg-indigo-600 text-indigo-600 hover:text-white border-2 border-indigo-600 rounded-2xl font-black transition-all shadow-sm hover:shadow-indigo-100 flex items-center justify-center gap-2 group/btn">
              <svg class="w-5 h-5 transform group-hover/btn:scale-125 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
              Efetuar Aporte
            </button>
          </div>
        </div>
      </div>

      <div v-if="store.goals.length === 0 && !store.isLoading" class="text-center py-16 bg-white rounded-3xl border-2 border-dashed border-gray-100">
        <CapiMascot mood="thinking" :size="140" class="mx-auto mb-2" />
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Sem metas no horizonte</h3>
        <p class="text-gray-500 mb-8 max-w-sm mx-auto">Você ainda não traçou nenhum objetivo financeiro. Que tal começar a planejar seu próximo sonho agora?</p>
        <button @click="openCreate" class="inline-flex bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-4 rounded-2xl font-bold transition shadow-xl shadow-indigo-100 items-center gap-3">
           Nova Meta
        </button>
      </div>
    </div>

    <!-- Modal Nova/Editar Meta -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-md">
      <div class="bg-white rounded-3xl w-full max-w-lg p-10 shadow-3xl transform transition-all scale-100">
        <div class="flex justify-between items-start mb-8">
            <h2 class="text-2xl font-black text-gray-900 leading-tight">
                {{ editingGoalId ? 'Atualizar seu Objetivo' : 'Definir novo Próximo Passo' }}
            </h2>
            <button @click="showModal = false" class="text-gray-500 hover:text-gray-600 transition p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="space-y-6">
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2 ml-1">O que você quer conquistar?</label>
            <input v-model="goalForm.title" type="text" placeholder="Ex: Viagem para Maldivas 🏝️" class="w-full p-4 bg-gray-50 border-2 border-transparent focus:border-indigo-500 focus:bg-white rounded-2xl outline-none transition-all placeholder:text-gray-300 font-bold" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2 ml-1">Quanto custa?</label>
              <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">R$</span>
                <input v-model.number="goalForm.targetAmount" type="number" class="w-full p-4 pl-12 bg-gray-50 border-2 border-transparent focus:border-indigo-500 focus:bg-white rounded-2xl outline-none transition-all font-bold" />
              </div>
            </div>
            <div>
              <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2 ml-1">Até quando?</label>
              <input v-model="goalForm.targetDate" type="date" class="w-full p-4 bg-gray-50 border-2 border-transparent focus:border-indigo-500 focus:bg-white rounded-2xl outline-none transition-all font-bold" />
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2 ml-1">Vincular Conta</label>
                <select v-model="goalForm.accountId" class="w-full p-4 bg-gray-50 border-2 border-transparent focus:border-indigo-500 focus:bg-white rounded-2xl outline-none transition-all font-bold">
                  <option :value="null">Nenhuma (Virtual)</option>
                  <option v-for="acc in accountStore.accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                </select>
                <p class="text-[9px] text-gray-500 mt-1 px-1">Se vinculado, os aportes baixarão o saldo desta conta.</p>
              </div>
              <div>
                <div class="flex items-center gap-1 mb-2 ml-1">
                    <label class="block text-xs font-black uppercase tracking-widest text-gray-500">Compartilhamento</label>
                    <div class="group/hint relative cursor-help">
                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="opacity-0 group-hover/hint:opacity-100 transition-opacity absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 bg-gray-900 text-white text-xs p-2 rounded-lg z-10 text-center pointer-events-none">
                            <strong>Privado:</strong> Apenas você vê.<br>
                            <strong>Workspace:</strong> Todos os membros do espaço podem ver e acompanhar o progresso.
                        </div>
                    </div>
                </div>
                <select v-model="goalForm.sharedWithWorkspaceId" class="w-full p-4 bg-gray-50 border-2 border-transparent focus:border-indigo-500 focus:bg-white rounded-2xl outline-none transition-all font-bold">
                  <option :value="null">Privado</option>
                  <option v-for="ws in workspaceStore.workspaces" :key="ws.id" :value="ws.id">{{ ws.name }}</option>
                </select>
              </div>
          </div>
        </div>
        
        <div class="mt-10 flex gap-4">
          <button @click="saveGoal" class="flex-1 px-8 py-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-black shadow-xl shadow-indigo-100 hover:shadow-indigo-200 transition-all transform hover:-translate-y-1">
             {{ editingGoalId ? 'Salvar Alterações' : 'Confirmar Objetivo' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Aporte -->
    <div v-if="showContributionModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-indigo-900/60 backdrop-blur-md">
      <div class="bg-white rounded-3xl w-full max-w-md p-10 shadow-3xl text-center">
        <div class="w-20 h-20 bg-indigo-50 text-indigo-600 rounded-3xl flex items-center justify-center mx-auto mb-6 transform rotate-12">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h2 class="text-2xl font-black mb-2 text-gray-900 leading-tight">Aportar para {{ selectedGoal?.title }}</h2>
        <p class="text-sm text-gray-500 mb-8 px-4">Defina o montante que você está reservando para este objetivo agora.</p>
        
        <div class="space-y-6 text-left">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2 ml-1">Valor do Aporte</label>
              <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-indigo-400 font-black text-xl">R$</span>
                  <input v-model.number="contributionAmount" type="number" step="0.01" class="w-full p-4 pl-14 bg-indigo-50 text-indigo-600 text-xl font-black border-none rounded-2xl focus:ring-4 focus:ring-indigo-100 outline-none transition-all" />
              </div>
            </div>
            <div>
                <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2 ml-1">Sair da Conta</label>
                <select v-model="contributionAccountId" class="w-full p-4 bg-gray-50 border-2 border-transparent focus:border-indigo-500 focus:bg-white rounded-2xl outline-none transition-all font-bold">
                    <option :value="null">Nenhuma (Apenas registrar)</option>
                    <option v-for="acc in accountStore.accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                </select>
            </div>
          </div>
          <div>
            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2 ml-1">Notas Opcionais</label>
            <input v-model="contributionDesc" type="text" placeholder="Ex: Economia do mês" class="w-full p-4 bg-gray-50 border-transparent focus:bg-white focus:border-indigo-100 rounded-2xl outline-none transition-all font-bold" />
          </div>
        </div>

        <div class="mt-10 flex gap-4">
          <button @click="showContributionModal = false" class="flex-1 px-4 py-4 rounded-2xl bg-gray-50 text-gray-500 font-bold hover:bg-gray-100 transition">Não agora</button>
          <button @click="saveContribution" class="flex-2 px-8 py-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-black shadow-xl shadow-indigo-100 transition-all transform hover:-translate-y-1">
            Confirmar e Guardar
          </button>
        </div>

        <p v-if="contributionAccountId" class="mt-6 text-xs text-indigo-400 bg-indigo-50 py-2 rounded-xl font-bold uppercase tracking-widest">
            Isto gerará um lançamento na conta: {{ getAccountName(contributionAccountId) }}
        </p>
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
.shadow-3xl {
    box-shadow: 0 35px 60px -15px rgba(79, 70, 229, 0.15);
}
</style>/template>
