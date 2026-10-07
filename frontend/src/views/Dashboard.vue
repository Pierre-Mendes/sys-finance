<template>
  <MainLayout>
    <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
      <div class="flex items-center gap-2">
        <CapiMascot :mood="healthStatus === 'critical' ? 'alert' : 'happy'" :size="72" class="hidden sm:block -ml-2" />
        <div>
          <h2 class="text-3xl font-extrabold text-ink-900">Visão Geral</h2>
          <p class="text-ink-500 mt-1 sm:text-lg">Aqui está o resumo da sua vida financeira.</p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-3">
          <label class="flex items-center cursor-pointer border border-gray-200 bg-white px-3 py-1.5 rounded-lg shadow-sm">
              <div class="relative">
                  <input type="checkbox" v-model="view360" @change="fetchAnalytics" class="sr-only" />
                  <div class="block bg-gray-200 w-12 h-6 rounded-full transition-colors" :class="{'bg-indigo-500': view360}"></div>
                  <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform" :class="{'transform translate-x-6': view360}"></div>
              </div>
              <div class="ml-3 font-medium flex flex-col">
                  <span class="text-sm text-gray-800" :class="{'font-bold text-indigo-700': view360}">Visão 360</span>
              </div>
          </label>

          <button @click="fetchAnalytics" :disabled="isRefreshing" class="text-sm font-medium text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 py-2.5 px-5 rounded-lg flex items-center gap-2 cursor-pointer transition disabled:opacity-50 disabled:cursor-not-allowed">
            <svg :class="{'animate-spin': isRefreshing}" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            <span class="hidden sm:inline">{{ isRefreshing ? 'Atualizando...' : 'Atualizar Painel' }}</span>
          </button>
          <!-- Quick Actions Dropdown -->
          <div class="relative">
              <button @click="showQuickActions = !showQuickActions" class="text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 py-2.5 px-4 rounded-lg flex items-center gap-2 cursor-pointer transition shadow-sm">
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                  <span class="hidden sm:inline">Ações Rápidas</span>
              </button>
              
              <!-- Quick Actions Folder Window -->
              <div v-show="showQuickActions" class="absolute right-0 mt-3 w-72 bg-white/70 backdrop-blur-3xl rounded-3xl shadow-2xl border border-white/40 p-5 z-50 grid grid-cols-2 gap-4 origin-top-right transition-all">
                <button @click="openModal = true; showQuickActions = false" class="flex flex-col items-center justify-center p-3 bg-white/80 hover:bg-white text-blue-600 rounded-2xl shadow-sm hover:shadow-md transition cursor-pointer text-center group">
                    <div class="bg-blue-100 p-3 rounded-2xl text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors mb-2 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </div>
                    <span class="font-medium text-xs text-gray-700">Lançamento</span>
                </button>
                <router-link to="/credit-cards" class="flex flex-col items-center justify-center p-3 bg-white/80 hover:bg-white text-orange-600 rounded-2xl shadow-sm hover:shadow-md transition cursor-pointer text-center group">
                    <div class="bg-orange-100 p-3 rounded-2xl text-orange-600 group-hover:bg-orange-500 group-hover:text-white transition-colors mb-2 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    </div>
                    <span class="font-medium text-xs text-gray-700">Cartões</span>
                </router-link>
                <router-link to="/accounts" class="flex flex-col items-center justify-center p-3 bg-white/80 hover:bg-white text-purple-600 rounded-2xl shadow-sm hover:shadow-md transition cursor-pointer text-center group">
                    <div class="bg-purple-100 p-3 rounded-2xl text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors mb-2 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5L18.5 8M6 12h.01M6 16h.01M10 12h.01M10 16h.01M14 12h.01M14 16h.01"></path></svg>
                    </div>
                    <span class="font-medium text-xs text-gray-700">Contas</span>
                </router-link>
                <router-link to="/categories" class="flex flex-col items-center justify-center p-3 bg-white/80 hover:bg-white text-teal-600 rounded-2xl shadow-sm hover:shadow-md transition cursor-pointer text-center group">
                    <div class="bg-teal-100 p-3 rounded-2xl text-teal-600 group-hover:bg-teal-500 group-hover:text-white transition-colors mb-2 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    </div>
                    <span class="font-medium text-xs text-gray-700">Categorias</span>
                </router-link>
              </div>
          </div>
      </div>
    </div>

    <SetupChecklist ref="setupChecklist" @new-transaction="openModal = true" />

    <OverdueReview ref="overdueReview" @changed="onTransactionSaved" />

    <UpcomingBills ref="upcomingBills" @paid="onTransactionSaved" />

    <ForecastCard ref="forecastCard" class="mb-8" />

    <!-- Alerta de Saúde Financeira -->
    <div v-if="healthStatus === 'critical'" role="alert" class="mb-8 bg-expense-50 rounded-2xl py-3 pl-2 pr-4 flex flex-wrap items-center gap-x-3 gap-y-2 animate-fade-up">
        <CapiMascot mood="alert" :size="76" />
        <div class="flex-1 min-w-[200px]">
            <h4 class="font-extrabold text-expense-900 text-[15px]">As despesas do mês passaram das receitas</h4>
            <p class="text-expense-900 text-sm leading-snug">Revise os orçamentos de maior gasto antes do fechamento para proteger seu caixa.</p>
        </div>
        <router-link to="/budgets" class="min-h-9 inline-flex items-center px-3.5 rounded-lg bg-[#7C2D12] hover:bg-[#5F2310] text-white text-sm font-bold transition">Revisar orçamentos</router-link>
    </div>

    <!-- Metricas Principais -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <!-- Saldo Atual -->
        <div class="bg-brand-600 rounded-2xl p-6 shadow-lg shadow-brand-600/25 text-white flex flex-col justify-between transition hover:-translate-y-1 animate-fade-up">
            <div class="flex items-start justify-between mb-4">
                <div>
                <div class="flex items-center gap-2 mb-1 group relative">
                    <h3 class="text-blue-100 font-medium text-sm uppercase tracking-wider flex items-center gap-1">
                        Saldo Atual
                    </h3>
                    <div class="cursor-help text-blue-200 hover:text-white transition relative">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 bg-gray-900 text-white text-xs rounded-xl py-2 px-3 shadow-lg z-10 text-center before:content-[''] before:absolute before:top-full before:left-1/2 before:-translate-x-1/2 before:border-4 before:border-transparent before:border-t-gray-900">
                             Somatório de todas as suas contas cadastradas.
                        </div>
                    </div>
                </div>
                    <p class="font-display text-3xl font-extrabold tracking-tight truncate pr-2">R$ {{ formatCurrency(balanceShown) }}</p>
                    <div class="mt-1 text-xs text-blue-100 flex items-center gap-1 opacity-80">
                        <span>Projeção:</span>
                        <span class="font-bold">R$ {{ formatCurrency(analytics.projections.projectedBalance) }}</span>
                    </div>
                </div>
                <div class="bg-white/20 p-3 rounded-full backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            
            <div v-if="view360 && analytics.breakdown?.length > 0" class="mt-4 pt-4 border-t border-blue-500/50">
                <div class="text-xs text-blue-200 mb-2 uppercase font-semibold">Composição do Saldo</div>
                <ul class="space-y-1">
                    <li v-for="b in analytics.breakdown.slice(0, 3)" :key="b.id" class="flex justify-between text-sm">
                        <span class="truncate pr-2" :class="{'font-medium text-white': b.type === 'personal', 'text-blue-100': b.type !== 'personal'}">
                            {{ b.type === 'personal' ? 'Meu Saldo' : b.name }}
                        </span>
                        <span class="font-mono">R$ {{ formatCurrency(b.balance) }}</span>
                    </li>
                    <li v-if="analytics.breakdown.length > 3" class="text-center mt-2">
                        <button class="text-xs font-medium hover:text-white transition text-blue-200">Ver mais +{{ analytics.breakdown.length - 3 }}</button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Receitas Card -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-ink-100 flex flex-col justify-between transition hover:-translate-y-1 animate-fade-up [animation-delay:60ms]">
            <div class="flex items-start justify-between mb-4">
                <div class="w-full">
                    <div class="flex items-center gap-2 mb-1 group relative">
                    <h3 class="text-gray-500 font-medium text-sm uppercase tracking-wider flex items-center gap-1">
                            Total Receitas
                        </h3>
                        <div class="cursor-help text-gray-500 hover:text-gray-700 transition relative">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 bg-gray-900 text-white text-xs rounded-xl py-2 px-3 shadow-lg z-10 text-center before:content-[''] before:absolute before:top-full before:left-1/2 before:-translate-x-1/2 before:border-4 before:border-transparent before:border-t-gray-900">
                                Todas as entradas registradas.
                            </div>
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <p class="font-display text-3xl font-extrabold tracking-tight text-ink-900 truncate pr-2">R$ {{ formatCurrency(incomeShown) }}</p>
                        <span v-if="incomeMoM !== 0" :class="incomeMoM > 0 ? 'text-green-600' : 'text-red-600'" class="text-xs font-bold mb-1 flex items-center">
                            {{ incomeMoM > 0 ? '▲' : '▼' }} {{ Math.abs(incomeMoM) }}%
                        </span>
                    </div>
                </div>
                <div class="bg-green-50 p-3 rounded-full text-green-500 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
            </div>
        </div>

        <!-- Despesas Card -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-ink-100 flex flex-col justify-between transition hover:-translate-y-1 animate-fade-up [animation-delay:120ms]">
            <div class="flex items-start justify-between mb-4">
                <div class="w-full">
                    <div class="flex items-center gap-2 mb-1 group relative">
                        <h3 class="text-gray-500 font-medium text-sm uppercase tracking-wider flex items-center gap-1">
                            Total Despesas
                        </h3>
                        <div class="cursor-help text-gray-500 hover:text-gray-700 transition relative">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 bg-gray-900 text-white text-xs rounded-xl py-2 px-3 shadow-lg z-10 text-center before:content-[''] before:absolute before:top-full before:left-1/2 before:-translate-x-1/2 before:border-4 before:border-transparent before:border-t-gray-900">
                                Todas as saídas registradas.
                            </div>
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <p class="font-display text-3xl font-extrabold tracking-tight text-ink-900 truncate pr-2">R$ {{ formatCurrency(expenseShown) }}</p>
                        <span v-if="expenseMoM !== 0" :class="expenseMoM > 0 ? 'text-red-600' : 'text-green-600'" class="text-xs font-bold mb-1 flex items-center">
                            {{ expenseMoM > 0 ? '▲' : '▼' }} {{ Math.abs(expenseMoM) }}%
                        </span>
                    </div>
                </div>
                <div class="bg-red-50 p-3 rounded-full text-red-500 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                </div>
            </div>
            <!-- Villain Highlight -->
            <div v-if="analytics.villain" class="mt-2 flex items-center justify-between text-xs uppercase font-bold px-2 py-1 bg-red-50 text-red-600 rounded-lg">
                <span>Vilão: {{ analytics.villain.name }}</span>
                <span>R$ {{ formatCurrency(analytics.villain.total) }}</span>
            </div>
        </div>

        <!-- Patrimônio Acumulado Card -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-ink-100 group hover:shadow-md transition-shadow animate-fade-up [animation-delay:180ms]">
            <div class="flex items-start justify-between mb-4">
                <div class="w-full">
                    <div class="flex items-center gap-2 mb-1 group relative">
                        <h3 class="text-gray-500 font-medium text-sm uppercase tracking-wider flex items-center gap-1">
                            {{ analytics.goals.label || 'Dinheiro Guardado' }}
                        </h3>
                        <div class="cursor-help text-gray-500 hover:text-gray-700 transition relative">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 bg-gray-900 text-white text-xs rounded-xl py-2 px-3 shadow-lg z-10 text-center before:content-[''] before:absolute before:top-full before:left-1/2 before:-translate-x-1/2 before:border-4 before:border-transparent before:border-t-gray-900">
                                Capital reservado em suas metas de acúmulo.
                            </div>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-indigo-600 truncate pr-2">R$ {{ formatCurrency(analytics.goals.current) }}</p>
                </div>
                <div class="bg-indigo-50 p-3 rounded-full text-indigo-500 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <!-- Progress toward Goals -->
            <div v-if="analytics.goals.target > 0" class="mt-2">
                <div class="flex justify-between text-xs uppercase font-bold text-gray-500 mb-1">
                    <span>Meta Total: R$ {{ formatCurrency(analytics.goals.target) }}</span>
                    <span>{{ analytics.goals.percent }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000" :style="{ width: analytics.goals.percent + '%' }"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Gráficos Principais -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Evolução -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col h-[400px]">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Evolução de Saldo</h3>
                <select v-model="evolutionPeriod" class="text-sm border-gray-200 rounded-md py-1 pl-2 pr-6">
                    <option value="monthly">Últimos 6 Meses</option>
                    <option value="daily">Últimos 30 Dias</option>
                </select>
            </div>
            <div class="flex-1 w-full relative">
                <BaseChart v-show="!isRefreshing && evolutionData.length > 0" height="100%" :option="evolutionChartOption" label="Gráfico da evolução do saldo no período" />
                <div v-if="!isRefreshing && evolutionData.length === 0" class="flex h-full items-center justify-center text-gray-500 text-sm">Sem dados suficientes.</div>
            </div>
        </div>

        <!-- Receita x Despesa -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col h-[400px]">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Fluxo de Caixa</h3>
            </div>
            <div class="flex-1 w-full relative">
                <BaseChart v-show="!isRefreshing && evolutionData.length > 0" height="100%" :option="flowChartOption" label="Gráfico de barras de receitas e despesas no período" />
                <div v-if="!isRefreshing && evolutionData.length === 0" class="flex h-full items-center justify-center text-gray-500 text-sm">Sem movimentos contabilizados.</div>
            </div>
        </div>
    </div>

    <!-- Seção Secundária: Categoria e Bancos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        
        <!-- Categorias -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col h-[400px]">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Gastos por Categoria</h3>
                <select v-model="categoryView" class="text-sm border-gray-200 rounded-md py-1 pl-2 pr-6">
                    <option value="general">Geral</option>
                    <option value="creditCard">Cartão de Crédito</option>
                </select>
            </div>
            <div class="flex-1 flex items-center justify-center relative">
                <!-- Durante a atualização o gráfico continua montado (só esmaece) para não piscar -->
                <BaseChart v-if="categoryData.length" :class="['max-w-[300px]', { 'opacity-50': isRefreshing }]" :height="260" :option="categoryChartOption" label="Gráfico de rosca dos gastos por categoria" />
                <div v-else-if="!isRefreshing" class="text-gray-500 text-sm">Sem dados suficientes.</div>
            </div>
        </div>

        <!-- Saldo por Contas -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[400px]">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex-shrink-0">
                <h3 class="text-lg font-bold text-gray-800">Desempenho por Banco</h3>
            </div>
            <div class="p-6 flex-1 overflow-y-auto max-h-80 custom-scroll">
                <ul v-if="analytics.accounts.length > 0" class="space-y-4">
                    <li v-for="acc in analytics.accounts" :key="acc.name" class="flex justify-between items-center p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition">
                        <div class="flex items-center gap-3 w-[60%]">
                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg flex-shrink-0">
                                {{ acc.name.charAt(0).toUpperCase() }}
                            </div>
                            <span class="font-medium text-gray-800 truncate" :title="acc.name">{{ acc.name }}</span>
                        </div>
                        <span class="font-bold text-gray-800 truncate" :title="'R$ ' + formatCurrency(acc.balance)">R$ {{ formatCurrency(acc.balance) }}</span>
                    </li>
                </ul>
                <div v-else class="text-center py-6 text-gray-500">
                    Nenhuma conta bancária registrada ainda.
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Injetado no Dashboard -->
    <TransactionModal 
        v-model="openModal" 
        :transaction-to-edit="null" 
        @saved="onTransactionSaved" 
    />
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import MainLayout from '@/components/layout/MainLayout.vue'
import TransactionModal from '@/presentation/components/domain/TransactionModal.vue'
import SetupChecklist from '@/presentation/components/domain/SetupChecklist.vue'
import UpcomingBills from '@/presentation/components/domain/UpcomingBills.vue'
import OverdueReview from '@/presentation/components/domain/OverdueReview.vue'
import ForecastCard from '@/presentation/components/reports/ForecastCard.vue'
import CapiMascot from '@/components/brand/CapiMascot.vue'
import { useCountUp } from '@/presentation/composables/useCountUp'
import BaseChart from '@/components/ui/BaseChart.vue'
import { areaSeries, axisTooltip, barSeries, cartesian, chartTheme, donutOption } from '@/presentation/charts/chartOptions'
import { useTheme } from '@/presentation/composables/useTheme'
import { formatBRL, formatBRLCompact } from '@/core/domain/money'
import api from '@/data/api/HttpClient'
import { useWorkspaceStore } from '@/presentation/store/workspaceStore'

const router = useRouter()
const workspaceStore = useWorkspaceStore()

const isRefreshing = ref(false)
const view360 = ref(workspaceStore.isView360)
const openModal = ref(false)
const showQuickActions = ref(false)

const analytics = ref<any>({
    totalIncome: 0,
    totalExpense: 0,
    balance: 0,
    prevMonth: { income: 0, expense: 0 },
    projections: { pendingIncome: 0, pendingExpense: 0, projectedBalance: 0 },
    villain: null,
    goals: { target: 0, current: 0, percent: 0 },
    accounts: [],
    breakdown: [],
    charts: {
        evolution: { monthly: [], daily: [] },
        categories: { general: [], creditCard: [] }
    }
})

const evolutionPeriod = ref<'monthly' | 'daily'>('monthly')
const categoryView = ref<'general' | 'creditCard'>('general')

const incomeMoM = computed(() => {
    if (!analytics.value.prevMonth.income) return 0;
    const diff = analytics.value.totalIncome - analytics.value.prevMonth.income;
    return Math.round((diff / analytics.value.prevMonth.income) * 100);
})

const expenseMoM = computed(() => {
    if (!analytics.value.prevMonth.expense) return 0;
    const diff = analytics.value.totalExpense - analytics.value.prevMonth.expense;
    return Math.round((diff / analytics.value.prevMonth.expense) * 100);
})

// Valores dos cards contam até o número real ao carregar (direto ao fim com prefers-reduced-motion)
const balanceShown = useCountUp(computed(() => Number(analytics.value.balance) || 0))
const incomeShown = useCountUp(computed(() => Number(analytics.value.totalIncome) || 0))
const expenseShown = useCountUp(computed(() => Number(analytics.value.totalExpense) || 0))

const healthStatus = computed(() => {
    // Subtract goals from available balance to get "real" liquid health
    const availableLiquid = analytics.value.balance;
    if (analytics.value.totalExpense > (analytics.value.totalIncome + availableLiquid)) return 'critical';
    if (analytics.value.totalExpense > analytics.value.totalIncome) return 'warning';
    return 'good';
})

const formatCurrency = (val: number) => {
    return Number(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Gráficos (ECharts): opções computed, refeitas ao trocar período, dados ou tema
const { isDark } = useTheme()
const BALANCE = '#2346D8'
const FLOW_COLORS = ['#0E7A55', '#F4A06A'] // receita escura x despesa clara: diferem também em luminosidade
const CATEGORY_COLORS = ['#2346D8', '#F2A541', '#0E7A55', '#C2410C', '#6F8BF0', '#A8724A', '#5B6B85']
const money2 = (v: number) => parseFloat(Number(v).toFixed(2))

const evolutionData = computed<any[]>(() =>
    evolutionPeriod.value === 'monthly' ? analytics.value.charts.evolution.monthly : analytics.value.charts.evolution.daily)
const categoryData = computed<any[]>(() =>
    categoryView.value === 'general' ? analytics.value.charts.categories.general : analytics.value.charts.categories.creditCard)

const evolutionChartOption = computed(() => {
    const t = chartTheme(isDark.value)
    // Evolução = saldo acumulado do fluxo (receitas - despesas) período a período
    let acc = 0
    const data = evolutionData.value.map((i: any) => money2((acc += Number(i.income) - Number(i.expense))))
    return {
        ...cartesian(t, { categories: evolutionData.value.map((i: any) => i.period), yFormatter: formatBRLCompact }),
        tooltip: axisTooltip(t, [BALANCE], formatBRL),
        series: [areaSeries('Saldo acumulado', BALANCE, data, t)],
    }
})

const flowChartOption = computed(() => {
    const t = chartTheme(isDark.value)
    return {
        ...cartesian(t, { categories: evolutionData.value.map((i: any) => i.period), yFormatter: formatBRLCompact, legend: 'right' }),
        tooltip: axisTooltip(t, FLOW_COLORS, formatBRL),
        series: [
            barSeries('Receitas', FLOW_COLORS[0]!, evolutionData.value.map((i: any) => money2(i.income))),
            barSeries('Despesas', FLOW_COLORS[1]!, evolutionData.value.map((i: any) => money2(i.expense))),
        ],
    }
})

// Nome de categoria é dado do usuário: o ECharts desenha em canvas e o tooltip usa textContent (chartOptions.ts).
const categoryChartOption = computed(() =>
    donutOption(chartTheme(isDark.value), categoryData.value.map((i: any, idx: number) => ({
        name: i.name, value: money2(i.total), color: CATEGORY_COLORS[idx % CATEGORY_COLORS.length]!,
    })), formatBRL))

const fetchAnalytics = async () => {
    isRefreshing.value = true;
    workspaceStore.setView360(view360.value) // Memoriza escolha
    try {
        const response = await api.get(`/api/dashboard?view360=${view360.value}`)
        analytics.value = response.data.data
    } catch (e: any) {
        if (e.response?.status === 401) { 
            toast.error('Sessão expirada.');
            router.push('/'); 
            return;
        }
        toast.error('Erro ao carregar dados do painel.')
    } finally {
        setTimeout(() => isRefreshing.value = false, 500);
    }
}

const setupChecklist = ref<InstanceType<typeof SetupChecklist> | null>(null)
const upcomingBills = ref<InstanceType<typeof UpcomingBills> | null>(null)
const overdueReview = ref<InstanceType<typeof OverdueReview> | null>(null)
const forecastCard = ref<InstanceType<typeof ForecastCard> | null>(null)

const onTransactionSaved = () => {
    fetchAnalytics()
    setupChecklist.value?.refresh()
    upcomingBills.value?.reload()
    overdueReview.value?.reload()
    forecastCard.value?.reload()
}

onMounted(() => {
    fetchAnalytics()
})
</script>

<style scoped>
.custom-scroll::-webkit-scrollbar {
  width: 6px;
}
.custom-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scroll::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 20px;
}
</style>
