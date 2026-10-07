<template>
  <MainLayout>
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">Relatórios</h2>
        <p class="text-gray-500 mt-1">Como foi o período, para onde foi o dinheiro e como fica o saldo daqui para frente.</p>
      </div>
      <button type="button" @click="exportSummary" :disabled="!report || exporting"
              class="self-start sm:self-auto border border-gray-200 bg-white hover:bg-gray-50 font-semibold px-4 py-2.5 rounded-lg text-sm disabled:opacity-50 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
        {{ exporting ? 'Exportando...' : 'Exportar CSV' }}
      </button>
    </div>

    <!-- Filtros: uma linha, acima de tudo -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-col sm:flex-row gap-3 sm:items-center">
      <div class="inline-flex bg-gray-100 rounded-lg p-1 self-start" role="group" aria-label="Tipo de relatório">
        <button v-for="opt in TYPES" :key="opt.value" type="button" @click="type = opt.value"
                :class="['px-4 py-1.5 text-sm rounded-md font-medium', type === opt.value ? 'bg-white shadow text-gray-900' : 'text-gray-500']">
          {{ opt.label }}
        </button>
      </div>
      <div v-if="type === 'monthly'" class="flex items-center gap-2">
        <button type="button" @click="shiftMonth(-1)" class="p-2 rounded-lg hover:bg-gray-100" aria-label="Mês anterior">‹</button>
        <input v-model="month" type="month" aria-label="Mês" class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm" />
        <button type="button" @click="shiftMonth(1)" class="p-2 rounded-lg hover:bg-gray-100" aria-label="Próximo mês">›</button>
        <button type="button" @click="month = currentMonth()" class="text-sm text-primary font-medium px-2">Este mês</button>
      </div>
      <label v-else class="flex items-center gap-2 text-sm">
        <span class="text-gray-500">Ano</span>
        <select v-model.number="year" class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
      </label>
      <span v-if="loading" class="text-xs text-gray-500 sm:ml-auto">Atualizando...</span>
    </div>

    <template v-if="report">
      <!-- KPIs -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div v-for="k in kpiCards" :key="k.label" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
          <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ k.label }}</p>
            <span v-if="k.change !== null" :class="['text-xs font-semibold px-2 py-0.5 rounded-full', k.good ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700']"
                  :title="`Comparado ao ${previousLabel}`">
              {{ k.change > 0 ? '↑' : k.change < 0 ? '↓' : '' }} {{ formatChange(k.change) }}
            </span>
          </div>
          <p :class="['text-2xl font-bold mt-1', k.negative ? 'text-red-700' : 'text-gray-900']">{{ formatBRL(k.value) }}</p>
          <p v-if="k.note" class="text-xs text-gray-500 mt-1">{{ k.note }}</p>
        </div>
      </div>

      <!-- Insights -->
      <div v-if="report.insights.length" class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
        <div v-for="(ins, i) in report.insights" :key="i" :class="['rounded-xl border p-4 flex gap-3', TONE[ins.tone].box]">
          <span :class="['text-lg leading-none', TONE[ins.tone].icon]" aria-hidden="true">{{ TONE[ins.tone].symbol }}</span>
          <div>
            <p class="font-semibold text-sm">{{ ins.title }}</p>
            <p class="text-sm mt-0.5 opacity-90">{{ ins.message }}</p>
          </div>
        </div>
      </div>

      <ForecastCard class="mb-6" />

      <div v-if="isEmpty" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-gray-500 mb-6">
        Nenhum lançamento pago em {{ report.period.label }}. Os gráficos aparecem quando houver receitas ou despesas pagas no período.
      </div>

      <template v-else>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-bold text-gray-800">Receitas × despesas</h3>
            <p class="text-xs text-gray-500 mb-2">{{ type === 'monthly' ? '12 meses até ' + report.period.label : 'Mês a mês em ' + report.period.label }}</p>
            <apexchart type="bar" height="280" :options="flowOptions" :series="flowSeries" />
          </section>
          <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-start justify-between gap-3">
              <div>
                <h3 class="font-bold text-gray-800">Saldo acumulado</h3>
                <p class="text-xs text-gray-500 mb-2">Saldo no fim de cada mês, somando tudo que já foi pago.</p>
              </div>
              <!-- No celular fica recolhido: a previsão acima já mostra o saldo realizado recente -->
              <button v-if="!isDesktop" type="button" @click="balanceExpanded = !balanceExpanded" :aria-expanded="balanceExpanded"
                      class="text-sm font-medium text-primary whitespace-nowrap">
                {{ balanceExpanded ? 'Ver menos' : 'Ver mais' }}
              </button>
            </div>
            <apexchart v-if="isDesktop || balanceExpanded" type="area" height="280" :options="balanceOptions" :series="balanceSeries" />
          </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          <section v-for="pie in pieSections" :key="pie.key" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-bold text-gray-800 mb-2">{{ pie.title }}</h3>
            <p v-if="!pie.slices.length" class="py-10 text-center text-sm text-gray-500">{{ pie.empty }}</p>
            <template v-else>
              <apexchart type="donut" height="240" :options="pieOptions(pie.slices)" :series="pie.slices.map(s => s.value)" />
              <!-- Lista com valores: a cor nunca é o único jeito de identificar a categoria -->
              <ul class="mt-3 space-y-1.5 text-sm">
                <li v-for="(s, i) in pie.slices" :key="s.name" class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ background: PALETTE[i] }"></span>
                  <button type="button" class="flex-1 min-w-0 truncate text-left text-gray-700 hover:underline" @click="drill(s.name)" :disabled="s.name === 'Outras'">{{ s.name }}</button>
                  <span class="tabular-nums text-gray-900 font-medium">{{ formatBRL(s.value) }}</span>
                  <span class="tabular-nums text-gray-500 w-12 text-right">{{ pct(s.value, pie.total) }}</span>
                </li>
              </ul>
            </template>
          </section>
        </div>

        <!-- Só no mensal: média histórica e padrões de gasto -->
        <template v-if="type === 'monthly' && report.history">
          <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
            <h3 class="font-bold text-gray-800">Comparado com a média</h3>
            <p class="text-xs text-gray-500 mb-4">Média dos 6 meses anteriores a {{ report.period.label }}.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div v-for="h in historyCards" :key="h.label" class="rounded-xl bg-gray-50 p-4">
                <p class="text-xs text-gray-500">{{ h.label }} média</p>
                <p class="font-bold text-gray-900">{{ formatBRL(h.avg) }}</p>
                <p :class="['text-xs mt-1 font-medium', h.change === null ? 'text-gray-500' : h.good ? 'text-emerald-700' : 'text-red-700']">
                  {{ h.change === null ? 'Sem histórico para comparar' : `Este mês: ${formatChange(h.change)} vs média` }}
                </p>
              </div>
            </div>
          </section>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
              <h3 class="font-bold text-gray-800 mb-2">Despesas por dia da semana</h3>
              <apexchart type="bar" height="240" :options="weekdayOptions" :series="weekdaySeries" />
            </section>
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
              <h3 class="font-bold text-gray-800">Despesas por dia do mês</h3>
              <p class="text-xs text-gray-500 mb-3">Quanto mais escuro, maior o gasto no dia.</p>
              <div class="grid grid-cols-7 gap-1.5 mb-1.5 text-center text-[11px] font-medium text-gray-500" aria-hidden="true">
                <span v-for="(w, i) in ['D', 'S', 'T', 'Q', 'Q', 'S', 'S']" :key="i">{{ w }}</span>
              </div>
              <div class="grid grid-cols-7 gap-1.5" role="list">
                <!-- Alinha o dia 1 ao dia da semana correto -->
                <div v-for="n in firstWeekday" :key="'pad' + n" aria-hidden="true"></div>
                <div v-for="d in report.byMonthDay" :key="d.day" role="listitem"
                     :title="`Dia ${d.day}: ${formatBRL(d.expense)}`" :aria-label="`Dia ${d.day}: ${formatBRL(d.expense)}`"
                     :class="['aspect-square rounded-md flex items-center justify-center text-xs font-medium', heatClass(d.expense)]">
                  {{ d.day }}
                </div>
              </div>
              <div class="flex items-center gap-1.5 mt-3 text-[11px] text-gray-500">
                <span>Menos</span>
                <span v-for="c in HEAT" :key="c" :class="['w-4 h-4 rounded', c]"></span>
                <span>Mais</span>
                <span class="ml-auto">Maior gasto: {{ formatBRL(maxDay) }}</span>
              </div>
            </section>
          </div>
        </template>

        <!-- Tabela por categoria: também é a "visão em tabela" dos gráficos -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-6 overflow-hidden">
          <header class="p-5 flex items-center justify-between">
            <div>
              <h3 class="font-bold text-gray-800">Detalhamento por categoria</h3>
              <p class="text-xs text-gray-500">{{ report.categories.length }} categorias · toque numa linha para ver os lançamentos</p>
            </div>
          </header>
          <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
              <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                  <th v-for="col in COLUMNS" :key="col.key" scope="col" :class="['px-4 py-3 font-semibold', col.key === 'name' ? 'text-left' : 'text-right']">
                    <button type="button" class="inline-flex items-center gap-1 hover:text-gray-900" @click="sortBy(col.key)" :aria-sort="sort.key === col.key ? (sort.dir === 'asc' ? 'ascending' : 'descending') : 'none'">
                      {{ col.label }}<span v-if="sort.key === col.key" aria-hidden="true">{{ sort.dir === 'asc' ? '▲' : '▼' }}</span>
                    </button>
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="c in sortedCategories" :key="c.name" class="hover:bg-gray-50 cursor-pointer" @click="drill(c.name)">
                  <td class="px-4 py-3 text-gray-800 font-medium">{{ c.name }}</td>
                  <td class="px-4 py-3 text-right tabular-nums">{{ formatBRL(c.income) }}</td>
                  <td class="px-4 py-3 text-right tabular-nums">{{ formatBRL(c.expense) }}</td>
                  <td :class="['px-4 py-3 text-right tabular-nums font-medium', c.net < 0 ? 'text-red-700' : 'text-gray-900']">{{ formatBRL(c.net) }}</td>
                  <td class="px-4 py-3 text-right tabular-nums text-gray-500">{{ c.incomeShare ? c.incomeShare.toLocaleString('pt-BR') + '%' : '—' }}</td>
                  <td class="px-4 py-3 text-right tabular-nums text-gray-500">{{ c.expenseShare ? c.expenseShare.toLocaleString('pt-BR') + '%' : '—' }}</td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50 font-semibold">
                <tr>
                  <td class="px-4 py-3">Total</td>
                  <td class="px-4 py-3 text-right tabular-nums">{{ formatBRL(report.kpis.income) }}</td>
                  <td class="px-4 py-3 text-right tabular-nums">{{ formatBRL(report.kpis.expense) }}</td>
                  <td :class="['px-4 py-3 text-right tabular-nums', report.kpis.balance < 0 ? 'text-red-700' : '']">{{ formatBRL(report.kpis.balance) }}</td>
                  <td class="px-4 py-3 text-right">100%</td>
                  <td class="px-4 py-3 text-right">100%</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </section>
      </template>
    </template>
    <div v-else-if="loading" class="py-20 text-center text-gray-500">Carregando relatório...</div>

    <TransactionExports />
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import MainLayout from '@/components/layout/MainLayout.vue'
import ForecastCard from '@/presentation/components/reports/ForecastCard.vue'
import TransactionExports from '@/presentation/components/reports/TransactionExports.vue'
import { fetchReportSummary, downloadReport, type ReportType } from '@/data/api/reportApi'
import { formatBRL, formatBRLCompact, formatChange } from '@/core/domain/money'
import { escapeHtml } from '@/core/security/escapeHtml'

// Ordem fixa da paleta categórica (validada para daltonismo); "Outras" em cinza.
const PALETTE = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#9ca3af']
const INCOME = '#1baf7a'
const EXPENSE = '#e34948'
const HEAT = ['bg-gray-100 text-gray-500', 'bg-red-100 text-red-900', 'bg-red-200 text-red-900', 'bg-red-400 text-white', 'bg-red-600 text-white']
const TYPES: { value: ReportType; label: string }[] = [{ value: 'monthly', label: 'Mensal' }, { value: 'annual', label: 'Anual' }]
const TONE: Record<string, { box: string; icon: string; symbol: string }> = {
  positive: { box: 'bg-emerald-50 border-emerald-200 text-emerald-900', icon: 'text-emerald-600', symbol: '✓' },
  info: { box: 'bg-blue-50 border-blue-200 text-blue-900', icon: 'text-blue-600', symbol: 'ℹ' },
  warning: { box: 'bg-amber-50 border-amber-200 text-amber-900', icon: 'text-amber-600', symbol: '!' },
  negative: { box: 'bg-red-50 border-red-200 text-red-900', icon: 'text-red-600', symbol: '✕' },
}
const COLUMNS = [
  { key: 'name', label: 'Categoria' }, { key: 'income', label: 'Receitas' }, { key: 'expense', label: 'Despesas' },
  { key: 'net', label: 'Saldo' }, { key: 'incomeShare', label: '% receitas' }, { key: 'expenseShare', label: '% despesas' },
] as const

const router = useRouter()
// Mês local (toISOString é UTC e vira o mês seguinte nas noites de fim de mês no Brasil).
const currentMonth = () => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}` }

const type = ref<ReportType>('monthly')
const month = ref(currentMonth())
const year = ref(new Date().getFullYear())
const report = ref<any>(null)
const loading = ref(false)
const exporting = ref(false)
const sort = ref<{ key: string; dir: 'asc' | 'desc' }>({ key: 'expense', dir: 'desc' })

// Saldo acumulado: aberto no desktop, recolhido ("Ver mais") no celular.
const desktopQuery = typeof window !== 'undefined' && window.matchMedia ? window.matchMedia('(min-width: 1024px)') : null
const isDesktop = ref(desktopQuery?.matches ?? true)
const balanceExpanded = ref(false)
const onViewportChange = (e: MediaQueryListEvent) => { isDesktop.value = e.matches }
desktopQuery?.addEventListener('change', onViewportChange)
onUnmounted(() => desktopQuery?.removeEventListener('change', onViewportChange))

const years = computed<number[]>(() => report.value?.availableYears ?? [year.value])
const previousLabel = computed(() => (type.value === 'monthly' ? 'mês anterior' : 'ano anterior'))
const isEmpty = computed(() => report.value && report.value.kpis.income === 0 && report.value.kpis.expense === 0)
const query = () => (type.value === 'monthly' ? { type: type.value, month: month.value } : { type: type.value, year: year.value })

const load = async () => {
  if (type.value === 'monthly' && !/^\d{4}-\d{2}$/.test(month.value)) return
  loading.value = true
  try {
    report.value = await fetchReportSummary(query())
  } catch {
    toast.error('Não foi possível carregar o relatório.')
  } finally {
    loading.value = false
  }
}

const shiftMonth = (delta: number) => {
  const [y, m] = month.value.split('-').map(Number)
  const d = new Date(y!, m! - 1 + delta, 1)
  month.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

const exportSummary = async () => {
  exporting.value = true
  try {
    const q = query()
    const name = q.type === 'monthly' ? `relatorio_mensal_${q.month}.csv` : `relatorio_anual_${q.year}.csv`
    await downloadReport('/api/reports/summary/csv', q, name)
  } catch {
    toast.error('Não foi possível exportar.')
  } finally {
    exporting.value = false
  }
}

const drill = (category: string) => {
  if (category === 'Outras') return
  router.push({ path: '/transactions', query: { category, from: report.value.period.from, to: report.value.period.to } })
}

// ---- KPIs
const kpiCards = computed(() => {
  const k = report.value.kpis
  return [
    { label: 'Receitas', value: k.income, change: k.incomeChange, good: (k.incomeChange ?? 0) >= 0, negative: false, note: '' },
    { label: 'Despesas', value: k.expense, change: k.expenseChange, good: (k.expenseChange ?? 0) <= 0, negative: false, note: '' },
    {
      label: 'Saldo do período', value: k.balance, change: k.balanceChange, good: (k.balanceChange ?? 0) >= 0, negative: k.balance < 0,
      note: [k.savingsRate !== null ? `Taxa de poupança: ${k.savingsRate.toLocaleString('pt-BR')}%` : '',
             k.monthlyAverage !== undefined ? `Média mensal: ${formatBRL(k.monthlyAverage)}` : ''].filter(Boolean).join(' · '),
    },
  ]
})

const historyCards = computed(() => {
  const h = report.value.history
  return [
    { label: 'Receita', avg: h.avgIncome, change: h.incomeVsAvg, good: (h.incomeVsAvg ?? 0) >= 0 },
    { label: 'Despesa', avg: h.avgExpense, change: h.expenseVsAvg, good: (h.expenseVsAvg ?? 0) <= 0 },
    { label: 'Saldo', avg: h.avgNet, change: h.netVsAvg, good: (h.netVsAvg ?? 0) >= 0 },
  ]
})

// ---- Gráficos
const baseChart = { toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' }
const axisLabels = { style: { colors: '#6b7280' } }
const grid = { borderColor: '#f1f1f1', strokeDashArray: 4 }

const flowSeries = computed(() => [
  { name: 'Receitas', data: report.value.timeline.map((m: any) => m.income) },
  { name: 'Despesas', data: report.value.timeline.map((m: any) => m.expense) },
])
const flowOptions = computed(() => ({
  chart: baseChart,
  colors: [INCOME, EXPENSE],
  plotOptions: { bar: { columnWidth: '60%', borderRadius: 4, borderRadiusApplication: 'end' } },
  stroke: { show: true, width: 2, colors: ['transparent'] },
  dataLabels: { enabled: false },
  xaxis: { categories: report.value.timeline.map((m: any) => m.label), labels: axisLabels },
  yaxis: { labels: { ...axisLabels, formatter: formatBRLCompact } },
  legend: { position: 'top', horizontalAlign: 'left' },
  grid,
  tooltip: { shared: true, intersect: false, y: { formatter: (v: number) => formatBRL(v) } },
}))

const balanceSeries = computed(() => [{ name: 'Saldo acumulado', data: report.value.timeline.map((m: any) => m.balance) }])
const balanceOptions = computed(() => ({
  chart: baseChart,
  colors: ['#2a78d6'],
  stroke: { width: 2, curve: 'smooth' },
  fill: { type: 'gradient', gradient: { opacityFrom: 0.25, opacityTo: 0.02 } },
  markers: { size: 4, strokeColors: '#fff', strokeWidth: 2 },
  dataLabels: { enabled: false },
  xaxis: { categories: report.value.timeline.map((m: any) => m.label), labels: axisLabels, tooltip: { enabled: false } },
  yaxis: { labels: { ...axisLabels, formatter: formatBRLCompact } },
  annotations: { yaxis: [{ y: 0, borderColor: '#9ca3af', strokeDashArray: 4 }] },
  grid,
  tooltip: { y: { formatter: (v: number) => formatBRL(v) } },
}))

interface Slice { name: string; value: number }
const pieSections = computed<{ key: string; title: string; empty: string; slices: Slice[]; total: number }[]>(() => [
  { key: 'expense', title: 'Despesas por categoria', empty: 'Nenhuma despesa paga no período.', slices: report.value.pies.expense, total: report.value.kpis.expense },
  { key: 'income', title: 'Receitas por categoria', empty: 'Nenhuma receita recebida no período.', slices: report.value.pies.income, total: report.value.kpis.income },
])
// O ApexCharts monta legenda/tooltip com innerHTML: nome de categoria (dado do usuário) sempre escapado.
const pieOptions = (slices: Slice[]) => ({
  chart: baseChart,
  labels: slices.map(s => escapeHtml(s.name)),
  colors: slices.map((s, i) => (s.name === 'Outras' ? PALETTE[7] : PALETTE[i])),
  legend: { show: false },
  dataLabels: { enabled: false },
  stroke: { width: 2, colors: ['#fff'] },
  plotOptions: { pie: { donut: { size: '62%' } } },
  tooltip: { y: { formatter: (v: number) => formatBRL(v) } },
})
const pct = (v: number, total: number) => (total > 0 ? `${((v / total) * 100).toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%` : '—')

const weekdaySeries = computed(() => [{ name: 'Despesas', data: report.value.byWeekday }])
const weekdayOptions = computed(() => ({
  chart: baseChart,
  colors: [EXPENSE],
  plotOptions: { bar: { columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' } },
  dataLabels: { enabled: false },
  xaxis: { categories: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'], labels: axisLabels },
  yaxis: { labels: { ...axisLabels, formatter: formatBRLCompact } },
  grid,
  tooltip: { y: { formatter: (v: number) => formatBRL(v) } },
}))

const firstWeekday = computed(() => {
  const [y, m] = String(report.value?.period.from ?? '').split('-').map(Number)
  return y && m ? new Date(y, m - 1, 1).getDay() : 0
})
const maxDay = computed(() => Math.max(0, ...(report.value?.byMonthDay ?? []).map((d: any) => d.expense)))
const heatClass = (v: number) => {
  if (v <= 0 || maxDay.value <= 0) return HEAT[0]
  const r = v / maxDay.value
  return HEAT[r > 0.75 ? 4 : r > 0.5 ? 3 : r > 0.25 ? 2 : 1]
}

// ---- Tabela
const sortBy = (key: string) => {
  sort.value = sort.value.key === key ? { key, dir: sort.value.dir === 'asc' ? 'desc' : 'asc' } : { key, dir: key === 'name' ? 'asc' : 'desc' }
}
const sortedCategories = computed(() => {
  const { key, dir } = sort.value
  const rows = [...report.value.categories]
  rows.sort((a, b) => {
    const cmp = key === 'name' ? a.name.localeCompare(b.name, 'pt-BR') : a[key] - b[key]
    return dir === 'asc' ? cmp : -cmp
  })
  return rows
})

watch([type, month, year], load)
onMounted(load)
</script>
