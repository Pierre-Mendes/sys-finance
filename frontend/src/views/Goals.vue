<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useGoalStore } from '@/presentation/store/goalStore'
import { useWorkspaceStore } from '@/presentation/store/workspaceStore'
import MainLayout from '@/components/layout/MainLayout.vue'
import LoaderSpinner from '@/components/ui/LoaderSpinner.vue'

const store = useGoalStore()
const workspaceStore = useWorkspaceStore()

const showModal = ref(false)
const newGoal = ref({
  title: '',
  targetAmount: 0,
  targetDate: '',
  sharedWithWorkspaceId: null as number | null
})

const showContributionModal = ref(false)
const selectedGoal = ref<any>(null)
const contributionAmount = ref(0)
const contributionDesc = ref('')

onMounted(async () => {
  await store.fetchGoals()
  if (workspaceStore.workspaces.length === 0) {
    await workspaceStore.fetchWorkspaces()
  }
})

const saveGoal = async () => {
  if (!newGoal.value.title || newGoal.value.targetAmount <= 0) return
  const success = await store.createGoal(newGoal.value)
  if (success) {
    showModal.value = false
    newGoal.value = { title: '', targetAmount: 0, targetDate: '', sharedWithWorkspaceId: null }
  }
}

const openContribution = (goal: any) => {
  selectedGoal.value = goal
  showContributionModal.value = true
}

const saveContribution = async () => {
  if (contributionAmount.value <= 0) return
  const success = await store.addContribution(selectedGoal.value.id, contributionAmount.value, contributionDesc.value)
  if (success) {
    showContributionModal.value = false
    contributionAmount.value = 0
    contributionDesc.value = ''
  }
}

const getPercentage = (accumulated: number, target: number) => {
  const p = (accumulated / target) * 100
  return Math.min(Math.round(p), 100)
}
</script>

<template>
  <MainLayout>
    <div class="px-4 py-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
          <h2 class="text-2xl sm:text-3xl font-semibold text-gray-800">Metas & Conquistas</h2>
          <p class="text-gray-500 mt-1">Planeje o enxoval, viagens ou aquela reserva de emergência.</p>
        </div>
        <button @click="showModal = true" class="w-full sm:w-auto bg-primary hover:bg-blue-600 px-5 py-2.5 rounded-lg text-white font-medium shadow transition cursor-pointer flex items-center justify-center gap-2 whitespace-nowrap">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
          Criar Nova Meta
        </button>
      </div>

      <div v-if="store.isLoading" class="flex justify-center py-12">
        <LoaderSpinner class="w-10 h-10 text-indigo-600" />
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="goal in store.goals" :key="goal.id" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ goal.title }}</h3>
            <span class="px-2 py-1 text-[10px] bg-indigo-50 text-indigo-600 rounded-full font-bold uppercase tracking-wider" v-if="goal.sharedWithWorkspaceId">Compartilhada</span>
          </div>

          <div class="mb-6">
            <div class="flex justify-between text-sm mb-2">
              <span class="text-gray-500">Progresso</span>
              <span class="font-bold text-indigo-600">{{ getPercentage(goal.accumulatedAmount, goal.targetAmount) }}%</span>
            </div>
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
              <div class="bg-indigo-500 h-3 rounded-full transition-all duration-1000" :style="{ width: getPercentage(goal.accumulatedAmount, goal.targetAmount) + '%' }"></div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
              <p class="text-[10px] text-gray-400 uppercase font-bold">Acumulado</p>
              <p class="text-sm font-bold text-gray-900 dark:text-white">R$ {{ goal.accumulatedAmount }}</p>
            </div>
            <div class="text-right">
              <p class="text-[10px] text-gray-400 uppercase font-bold">Objetivo</p>
              <p class="text-sm font-bold text-gray-900 dark:text-white">R$ {{ goal.targetAmount }}</p>
            </div>
          </div>

          <div v-if="goal.targetDate" class="mb-6 p-3 bg-gray-50 dark:bg-gray-750 rounded-lg border border-dashed border-gray-200 dark:border-gray-600">
             <p class="text-xs text-gray-500">Prazo estimado:</p>
             <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ new Date(goal.targetDate).toLocaleDateString() }}</p>
          </div>

          <button @click="openContribution(goal)" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition-colors shadow-sm">
            Efetuar Aporte
          </button>
        </div>
      </div>

      <div v-if="store.goals.length === 0 && !store.isLoading" class="text-center py-24 bg-white dark:bg-gray-800 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700">
        <p class="text-gray-500 text-lg mb-4">Você ainda não tem metas traçadas.</p>
        <button @click="showModal = true" class="mx-auto bg-primary hover:bg-blue-600 text-white px-6 py-3 rounded-xl font-bold transition shadow-md flex items-center gap-2">
           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
           Começar meu primeiro sonho
        </button>
      </div>
    </div>

    <!-- Modal Nova Meta -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
      <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Qual seu próximo objetivo?</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Título da Meta</label>
            <input v-model="newGoal.title" type="text" placeholder="Ex: Enxoval Casal" class="w-full p-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor Alvo R$</label>
              <input v-model.number="newGoal.targetAmount" type="number" class="w-full p-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl outline-none" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Desejada</label>
              <input v-model="newGoal.targetDate" type="date" class="w-full p-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl outline-none" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Compartilhar com Workspace (Opcional)</label>
            <select v-model="newGoal.sharedWithWorkspaceId" class="w-full p-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl outline-none">
              <option :value="null">Não compartilhar</option>
              <option v-for="ws in workspaceStore.workspaces" :key="ws.id" :value="ws.id">{{ ws.name }}</option>
            </select>
          </div>
        </div>
        <div class="mt-8 flex gap-3">
          <button @click="showModal = false" class="flex-1 px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-bold hover:bg-gray-200 transition">Pensei melhor</button>
          <button @click="saveGoal" class="flex-1 px-4 py-2.5 rounded-xl bg-primary hover:bg-blue-600 text-white font-bold shadow transition">Criar Meta</button>
        </div>
      </div>
    </div>

    <!-- Modal Aporte -->
    <div v-if="showContributionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
      <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-sm p-6 shadow-2xl border border-indigo-100">
        <h2 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Aporte para: {{ selectedGoal?.title }}</h2>
        <p class="text-xs text-gray-500 mb-6">Este valor será somado ao total acumulado desta meta.</p>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor do Aporte R$</label>
            <input v-model.number="contributionAmount" type="number" step="0.01" class="w-full p-4 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-2xl font-bold border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas (Opcional)</label>
            <input v-model="contributionDesc" type="text" placeholder="Ex: Economia do mês" class="w-full p-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl outline-none" />
          </div>
        </div>

        <div class="mt-8 flex gap-3">
          <button @click="showContributionModal = false" class="flex-1 px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-bold hover:bg-gray-200 transition">Cancelar</button>
          <button @click="saveContribution" class="flex-1 px-4 py-2.5 rounded-xl bg-primary hover:bg-blue-600 text-white font-bold shadow transition">Confirmar Aporte</button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>
