<template>
  <MainLayout>
    <div class="space-y-6">
      
      <!-- BANNER: Em Desenvolvimento -->
      <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm flex items-start">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-yellow-800">Aviso: Módulo Beta</h3>
          <div class="mt-1 text-sm text-yellow-700">
            <p>A aba de Investimentos encontra-se em desenvolvimento (Alpha). A interface e funcionalidades completas serão finalizadas nas próximas atualizações!</p>
          </div>
        </div>
      </div>

      <!-- Header Area -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
            <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            Carteira de Investimentos
          </h2>
          <p class="text-sm text-gray-500 mt-1">Acompanhe suas ações, FIIs, Tesouro e Renda Fixa integrados via API (B3).</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <button @click="syncQuotes" :disabled="isSyncing" class="px-4 py-2.5 bg-white text-indigo-600 border border-indigo-200 rounded-lg text-sm font-semibold hover:bg-indigo-50 hover:border-indigo-300 transition shadow-sm flex items-center gap-2 disabled:opacity-50">
            <svg :class="{'animate-spin': isSyncing}" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            {{ isSyncing ? 'Buscando Cotações...' : 'Sincronizar B3' }}
          </button>
          <button @click="openCreateModal" class="px-4 py-2.5 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition shadow-md flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Novo Ativo
          </button>
        </div>
      </div>

      <!-- Dashboard Widgets -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-card text-white rounded-2xl p-6 shadow-xl relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 to-purple-600/20 opacity-0 group-hover:opacity-100 transition duration-500"></div>
            <h3 class="text-gray-300 text-sm font-medium flex items-center gap-2">
               <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
               Total Aplicado (Custo)
            </h3>
            <p class="text-3xl font-extrabold mt-2 cursor-pointer">{{ formatCurrency(totalAplicado) }}</p>
        </div>
        
        <div class="bg-card text-white rounded-2xl p-6 shadow-xl relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-r from-emerald-600/20 to-teal-600/20 opacity-0 group-hover:opacity-100 transition duration-500"></div>
            <h3 class="text-gray-300 text-sm font-medium flex items-center gap-2">
               <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
               Saldo Bruto Atual (Mercado)
            </h3>
            <p class="text-3xl font-extrabold mt-2 cursor-pointer">{{ formatCurrency(totalAtual) }}</p>
        </div>
        
        <div :class="[lucroPrejuizoPct >= 0 ? 'bg-gradient-to-br from-emerald-500 to-green-600' : 'bg-gradient-to-br from-red-500 to-rose-600']" class="text-white rounded-2xl p-6 shadow-xl relative overflow-hidden">
            <h3 class="text-white/80 text-sm font-medium flex items-center gap-2">
               Resultado (Rentabilidade)
            </h3>
            <div class="flex items-end gap-3 mt-2">
                <p class="text-3xl font-extrabold">{{ formatCurrency(Math.abs(lucroPrejuizoValor)) }} <span class="text-lg font-normal">{{ lucroPrejuizoValor >= 0 ? 'LUCRO' : 'PREJUÍZO' }}</span></p>
            </div>
            <p class="text-white/90 text-sm mt-1 flex items-center gap-1 font-bold">
                <svg v-if="lucroPrejuizoPct >= 0" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                {{ lucroPrejuizoPct >= 0 ? '+' : '' }}{{ lucroPrejuizoPct.toFixed(2) }}%
            </p>
        </div>
      </div>

      <!-- Content Area -->
      <div v-if="loading" class="flex justify-center p-12">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-emerald-500 border-t-transparent"></div>
      </div>
      
      <div v-else-if="investments?.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Sua carteira está vazia</h3>
        <p class="text-gray-500 max-w-md mx-auto mb-6">Crie seu primeiro ativo, como uma Ação (Ex: PETR4) ou um Tesouro Direto, para começar a gerenciar seus aportes.</p>
        <button @click="openCreateModal" class="px-6 py-2.5 bg-emerald-600 text-white font-bold rounded-lg hover:bg-emerald-700 transition shadow">Criar Primeiro Ativo</button>
      </div>

      <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
         <div class="overflow-x-auto">
             <table class="w-full text-left border-collapse">
                 <thead>
                     <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-xs uppercase tracking-wider">
                         <th class="p-4 font-bold">Ativo</th>
                         <th class="p-4 font-bold">Tipo</th>
                         <th class="p-4 font-bold text-right">Patrimônio / Qtd</th>
                         <th class="p-4 font-bold text-right">Preço Médio</th>
                         <th class="p-4 font-bold text-right">Cotação Ref.</th>
                         <th class="p-4 font-bold text-right">Total (R$)</th>
                         <th class="p-4 font-bold text-right">Resultado</th>
                         <th class="p-4 font-bold text-center">Opções</th>
                     </tr>
                 </thead>
                 <tbody class="divide-y divide-gray-100">
                     <tr v-for="inv in investments" :key="inv.id" class="hover:bg-gray-50/50 transition duration-150">
                         <td class="p-4">
                             <div class="flex items-center gap-3">
                                 <div class="w-10 h-10 rounded-lg flex items-center justify-center shadow-sm text-white font-bold text-sm" :class="getTypeColor(inv.type)">
                                     {{ inv.ticker ? inv.ticker.substring(0,2) : inv.type.substring(0,2) }}
                                 </div>
                                 <div class="max-w-[150px]">
                                     <p class="font-bold text-gray-900 truncate">{{ inv.ticker || inv.name }}</p>
                                     <p v-if="inv.ticker" class="text-xs text-gray-500 truncate" :title="inv.name">{{ inv.name }}</p>
                                 </div>
                             </div>
                         </td>
                         <td class="p-4">
                             <span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded text-xs font-semibold uppercase tracking-wider">{{ inv.type }}</span>
                         </td>
                         <td class="p-4 text-right">
                             <p class="font-bold text-gray-900">{{ parseFloat(inv.quantity).toFixed(2) }}</p>
                         </td>
                         <td class="p-4 text-right">
                             <p class="text-gray-700 font-medium">{{ formatCurrency(inv.average_price) }}</p>
                         </td>
                         <td class="p-4 text-right group relative cursor-pointer" @click="openManualQuoteModal(inv)">
                             <div class="flex items-center justify-end gap-1 text-gray-900 font-bold hover:text-indigo-600 transition">
                                 {{ formatCurrency(inv.current_price) }}
                                 <svg class="w-3 h-3 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                             </div>
                             <span v-if="inv.type!=='ACAO' && inv.type!=='FII'" class="text-xs text-gray-500 block -mt-1">Clique para atualizar</span>
                         </td>
                         <td class="p-4 text-right">
                             <p class="font-black text-gray-900">{{ formatCurrency(parseFloat(inv.quantity) * parseFloat(inv.current_price)) }}</p>
                         </td>
                         <td class="p-4 text-right">
                             <div v-if="parseFloat(inv.quantity) > 0">
                                 <p :class="getRentabilityInfo(inv).val >= 0 ? 'text-emerald-500' : 'text-red-500'" class="font-bold text-sm">
                                    {{ getRentabilityInfo(inv).val >= 0 ? '+' : '' }}{{ formatCurrency(getRentabilityInfo(inv).val) }}
                                 </p>
                                 <p :class="getRentabilityInfo(inv).pct >= 0 ? 'text-emerald-500/70' : 'text-red-500/70'" class="text-xs font-semibold">
                                    {{ getRentabilityInfo(inv).pct >= 0 ? '+' : '' }}{{ getRentabilityInfo(inv).pct.toFixed(2) }}%
                                 </p>
                             </div>
                             <p v-else class="text-gray-500 text-sm">-</p>
                         </td>
                         <td class="p-4 text-center">
                             <div class="flex items-center justify-center gap-2">
                                 <button @click="openTransactionModal(inv, 'BUY')" title="Aportar (Comprar Mais)" class="p-1.5 text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></button>
                                 <button @click="openTransactionModal(inv, 'SELL')" title="Vender / Sacar" class="p-1.5 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg></button>
                                 <button @click="deleteInvestment(inv.id)" title="Deletar Ativo (Apaga todo Histórico)" class="p-1.5 text-gray-500 hover:text-red-600 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                             </div>
                         </td>
                     </tr>
                 </tbody>
             </table>
         </div>
      </div>
    </div>
    
    <!-- Modal: Create Asset -->
    <div v-if="createModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm animate-fade-in-down" @click.self="createModalOpen = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="p-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Cadastrar Novo Ativo
            </h3>
            <button @click="createModalOpen = false" class="text-gray-500 hover:text-gray-600 transition"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
        <form @submit.prevent="submitCreate" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">Classe de Ativo</label>
              <select v-model="createForm.type" required class="w-full bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block p-2.5 transition">
                <option value="ACAO">Ação (B3)</option>
                <option value="FII">Fundo Imobiliário (B3)</option>
                <option value="CDB">CDB / LCI / LCA</option>
                <option value="RDC">RDC cooperativo (Sicoob/Sicredi)</option>
                <option value="TESOURO">Tesouro Direto</option>
                <option value="OUTRO">Outros</option>
              </select>
            </div>
            
            <div v-if="createForm.type === 'ACAO' || createForm.type === 'FII'">
              <label class="block text-sm font-semibold text-gray-700 mb-1">Ticker (Código B3) <span class="text-red-500">*</span></label>
              <input v-model="createForm.ticker" type="text" placeholder="Ex: PETR4, MXRF11" required class="w-full bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block p-2.5 uppercase transition" />
            </div>
            
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">Nome Descritivo <span class="text-red-500">*</span></label>
              <input v-model="createForm.name" type="text" placeholder="Ex: FII Maxi Renda / CDB Banco Inter" required class="w-full bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block p-2.5 transition" />
            </div>
            
            <p class="text-xs text-gray-500 mt-2"><svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Você cadastrará as transações de compra/venda na próxima etapa.</p>
            
            <div class="pt-4 flex gap-3">
              <button type="button" @click="createModalOpen = false" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-bold hover:bg-gray-200 transition">Cancelar</button>
              <button type="submit" :disabled="isSubmitting" class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-700 transition disabled:opacity-50">Criar Ativo</button>
            </div>
        </form>
      </div>
    </div>
    
    <!-- Modal: Buy/Sell Transaction -->
    <div v-if="txModalOpen && activeAsset" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-fade-in-down" @click.self="txModalOpen = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="p-5 border-b border-gray-100 bg-gray-50">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-lg">Nova Operação</h3>
                <button @click="txModalOpen = false" class="text-gray-500 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            <p class="text-sm text-gray-500 mt-1">Lançando em: <span class="font-bold text-indigo-600">{{ activeAsset.ticker || activeAsset.name }}</span></p>
        </div>
        <form @submit.prevent="submitTx" class="p-6 space-y-4">
            <div class="flex rounded-lg shadow-sm border border-gray-200 p-1 bg-gray-50">
                <button type="button" @click="txForm.action = 'BUY'" :class="txForm.action === 'BUY' ? 'bg-emerald-500 text-white shadow' : 'text-gray-600 hover:bg-gray-200'" class="flex-1 py-1.5 rounded-md text-sm font-bold transition">Aporte (Compra)</button>
                <button type="button" @click="txForm.action = 'SELL'" :class="txForm.action === 'SELL' ? 'bg-rose-500 text-white shadow' : 'text-gray-600 hover:bg-gray-200'" class="flex-1 py-1.5 rounded-md text-sm font-bold transition">Retirada (Venda)</button>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-1">Quantidade</label>
                  <input v-model.number="txForm.quantity" type="number" step="0.0001" min="0.0001" placeholder="Ex: 100" required class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2.5" />
                </div>
                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-1">Preço Unit. (R$)</label>
                  <input v-model.number="txForm.price" type="number" step="0.01" min="0" placeholder="Ex: 25.50" required class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2.5" />
                </div>
            </div>
            
            <div>
               <div class="w-full text-right text-xs text-gray-500 mb-2">Total Lançamento: <span class="font-bold text-gray-900 text-sm">{{ formatCurrency((Number(txForm.quantity) || 0) * (Number(txForm.price) || 0)) }}</span></div>
            </div>
            
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">Data da Operação</label>
              <input v-model="txForm.date" type="date" required class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2.5" />
            </div>
            
            <div class="pt-2">
              <button type="submit" :disabled="isSubmitting" :class="txForm.action === 'BUY' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'" class="w-full py-2.5 text-white rounded-lg font-bold shadow-md transition disabled:opacity-50">Confirmar Operação</button>
            </div>
        </form>
      </div>
    </div>
    
    <!-- Modal: Update Manual Quote -->
    <div v-if="quoteModalOpen && activeAsset" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-fade-in-down" @click.self="quoteModalOpen = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xs overflow-hidden">
        <div class="p-5 border-b border-gray-100 bg-gray-50 text-center">
            <h3 class="font-bold text-gray-800 text-lg">Cotação Manual</h3>
            <p class="text-sm text-gray-500 mt-1">{{ activeAsset.name }}</p>
        </div>
        <form @submit.prevent="submitQuote" class="p-6">
            <div class="mb-5">
              <label class="block text-sm font-semibold text-gray-700 mb-2 text-center">Valor Atual de 1 Unidade</label>
              <input v-model.number="quoteForm.current_price" type="number" step="0.0001" min="0" required class="w-full text-center text-xl font-bold bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-3" />
            </div>
            <button type="submit" :disabled="isSubmitting" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-md transition mb-2">Salvar Cotação</button>
            <button type="button" @click="quoteModalOpen = false" class="w-full py-2 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-lg font-bold transition">Cancelar</button>
        </form>
      </div>
    </div>
    
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '@/data/api/HttpClient'
import MainLayout from '../components/layout/MainLayout.vue'
import { toast } from 'vue3-toastify'
import Swal from 'sweetalert2'

const investments = ref<any[]>([])
const loading = ref(true)
const isSyncing = ref(false)
const isSubmitting = ref(false)

const createModalOpen = ref(false)
const createForm = ref({ type: 'ACAO', ticker: '', name: '' })

const txModalOpen = ref(false)
const activeAsset = ref<any>(null)
const txForm = ref({ action: 'BUY', quantity: '', price: '', date: new Date().toISOString().slice(0,10) })

const quoteModalOpen = ref(false)
const quoteForm = ref({ current_price: 0 })

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}

const getTypeColor = (type: string) => {
    switch (type) {
        case 'ACAO': return 'bg-blue-600'
        case 'FII': return 'bg-indigo-600'
        case 'CDB': return 'bg-teal-500'
        case 'RDC': return 'bg-cyan-600'
        case 'TESOURO': return 'bg-emerald-500'
        default: return 'bg-gray-500'
    }
}

const totalAplicado = computed(() => {
    return (investments.value || []).reduce((sum, inv) => sum + (parseFloat(inv.quantity) * parseFloat(inv.average_price)), 0)
})

const totalAtual = computed(() => {
    return (investments.value || []).reduce((sum, inv) => sum + (parseFloat(inv.quantity) * parseFloat(inv.current_price)), 0)
})

const lucroPrejuizoValor = computed(() => totalAtual.value - totalAplicado.value)
const lucroPrejuizoPct = computed(() => totalAplicado.value > 0 ? (lucroPrejuizoValor.value / totalAplicado.value) * 100 : 0)

const getRentabilityInfo = (inv: any) => {
    const q = parseFloat(inv.quantity)
    const avg = parseFloat(inv.average_price)
    const curr = parseFloat(inv.current_price)
    
    if (q <= 0) return { val: 0, pct: 0 }
    
    const invested = q * avg
    const current = q * curr
    const diff = current - invested
    const pct = invested > 0 ? (diff / invested) * 100 : 0
    return { val: diff, pct }
}

const fetchData = async () => {
    loading.value = true
    try {
        const { data } = await api.get('/api/investments')
        investments.value = data.data || []
    } catch (e: any) {
        toast.error('Erro ao carregar carteira')
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    fetchData()
})

const openCreateModal = () => {
    createForm.value = { type: 'ACAO', ticker: '', name: '' }
    createModalOpen.value = true
}

const submitCreate = async () => {
    isSubmitting.value = true
    try {
        await api.post('/api/investments', createForm.value)
        toast.success('Ativo criado com sucesso!')
        createModalOpen.value = false
        fetchData()
    } catch(e: any) {
        toast.error(e.response?.data?.error || 'Falha ao criar')
    } finally {
        isSubmitting.value = false
    }
}

const openTransactionModal = (inv: any, action: 'BUY'|'SELL') => {
    activeAsset.value = inv
    txForm.value = { action, quantity: '' as any, price: inv.current_price > 0 ? inv.current_price : inv.average_price, date: new Date().toISOString().slice(0,10) }
    txModalOpen.value = true
}

const submitTx = async () => {
    if (!activeAsset.value) return
    isSubmitting.value = true
    try {
        await api.post(`/api/investments/${activeAsset.value.id}/transactions`, txForm.value)
        toast.success(txForm.value.action === 'BUY' ? 'Aporte registrado!' : 'Venda registrada!')
        txModalOpen.value = false
        fetchData()
    } catch(e: any) {
        toast.error(e.response?.data?.error || 'Falha ao lançar')
    } finally {
        isSubmitting.value = false
    }
}

const openManualQuoteModal = (inv: any) => {
    activeAsset.value = inv
    quoteForm.value.current_price = parseFloat(inv.current_price)
    quoteModalOpen.value = true
}

const submitQuote = async () => {
    if (!activeAsset.value) return
    isSubmitting.value = true
    try {
        await api.put(`/api/investments/${activeAsset.value.id}`, quoteForm.value)
        toast.success('Cotação manual gravada!')
        quoteModalOpen.value = false
        fetchData()
    } catch(e: any) {
        toast.error(e.response?.data?.error || 'Falha ao atualizar')
    } finally {
        isSubmitting.value = false
    }
}

const syncQuotes = async () => {
    isSyncing.value = true
    try {
        const { data } = await api.post('/api/investments/quotes/sync', {})
        toast.success(data.message)
        fetchData()
    } catch(e: any) {
        toast.error(e.response?.data?.error || 'Falha na Sincronização B3')
    } finally {
        isSyncing.value = false
    }
}

const deleteInvestment = async (id: number) => {
    const result = await Swal.fire({
        title: 'Cuidado!',
        text: 'Apagar este ativo excluirá todo o seu histórico de rentabilidade. Deseja prosseguir?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#C2410C',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sim, Apagar Histórico',
        cancelButtonText: 'Cancelar'
    })

    if (result.isConfirmed) {
        try {
            await api.delete(`/api/investments/${id}`);
            toast.success('Ativo apagado da carteira.');
            fetchData();
        } catch (e: any) {
            toast.error(e.response?.data?.error || 'Erro ao apagar ativo');
        }
    }
}
</script>
