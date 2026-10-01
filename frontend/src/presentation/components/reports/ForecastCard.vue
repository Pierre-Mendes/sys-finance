<template>
  <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6">
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h3 class="text-lg font-bold text-gray-800">Previsão de saldo</h3>
        <p class="text-xs text-gray-500">Saldo de hoje + contas a pagar e receber, recorrências e compras no cartão ainda sem fatura.</p>
      </div>
      <div class="inline-flex bg-gray-100 rounded-lg p-1 self-start" role="group" aria-label="Horizonte da previsão">
        <button v-for="d in HORIZONS" :key="d" type="button" @click="days = d"
                :class="['px-3 py-1.5 text-sm rounded-md font-medium', days === d ? 'bg-white shadow text-gray-900' : 'text-gray-500']">
          {{ d }} dias
        </button>
      </div>
    </header>

    <div v-if="loading" class="h-64 flex items-center justify-center text-sm text-gray-400">Calculando previsão...</div>
    <template v-else-if="data">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="rounded-xl bg-gray-50 p-3">
          <p class="text-xs text-gray-500">Saldo hoje</p>
          <p class="font-bold text-gray-900">{{ formatBRL(data.startBalance) }}</p>
        </div>
        <div class="rounded-xl bg-gray-50 p-3">
          <p class="text-xs text-gray-500">Em {{ data.days }} dias</p>
          <p :class="['font-bold', data.endBalance < 0 ? 'text-red-700' : 'text-gray-900']">{{ formatBRL(data.endBalance) }}</p>
        </div>
        <div class="rounded-xl bg-gray-50 p-3">
          <p class="text-xs text-gray-500">Entradas previstas</p>
          <p class="font-bold text-gray-900">{{ formatBRL(data.incoming) }}</p>
        </div>
        <div class="rounded-xl bg-gray-50 p-3">
          <p class="text-xs text-gray-500">Saídas previstas</p>
          <p class="font-bold text-gray-900">{{ formatBRL(data.outgoing) }}</p>
        </div>
      </div>

      <div v-if="data.firstNegativeDate" class="mb-4 flex gap-3 items-start rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"></path></svg>
        <p><strong>Saldo negativo em {{ formatDay(data.firstNegativeDate) }}.</strong>
          O menor saldo previsto é {{ formatBRL(data.min.balance) }} em {{ formatDay(data.min.date) }}.</p>
      </div>
      <div v-else class="mb-4 flex gap-3 items-start rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <p>O saldo fica positivo nos próximos {{ data.days }} dias. Menor saldo: {{ formatBRL(data.min.balance) }} em {{ formatDay(data.min.date) }}.</p>
      </div>

      <apexchart type="area" height="260" :options="chartOptions" :series="series" />

      <details v-if="data.events.length" class="mt-4 group">
        <summary class="cursor-pointer text-sm font-medium text-primary select-none">Ver os {{ data.events.length }} lançamentos previstos</summary>
        <ul class="mt-3 divide-y divide-gray-100 text-sm">
          <li v-for="(e, i) in data.events" :key="i" class="py-2 flex items-center gap-3">
            <span class="w-14 text-gray-500 tabular-nums">{{ formatDay(e.date) }}</span>
            <span class="flex-1 min-w-0 truncate text-gray-800">{{ e.title }}</span>
            <span class="text-[10px] uppercase font-semibold px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">{{ KIND_LABEL[e.kind] }}</span>
            <span :class="['font-semibold tabular-nums whitespace-nowrap', e.amount < 0 ? 'text-red-700' : 'text-emerald-700']">{{ formatBRL(e.amount) }}</span>
          </li>
        </ul>
      </details>
    </template>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import { fetchForecast } from '@/data/api/reportApi'
import { formatBRL, formatBRLCompact } from '@/core/domain/money'

const HORIZONS = [30, 60, 90] as const
const KIND_LABEL: Record<string, string> = { pending: 'A vencer', overdue: 'Atrasada', recurring: 'Recorrente', card: 'Cartão' }

const days = ref<30 | 60 | 90>(90)
const data = ref<any>(null)
const loading = ref(true)

// Meia-noite local: "2026-10-01" lido como UTC viraria 30/09 no Brasil.
const localDay = (d: string) => { const [y, m, dd] = d.split('-').map(Number); return new Date(y!, m! - 1, dd!).getTime() }
const formatDay = (d: string) => d.slice(0, 10).split('-').reverse().slice(0, 2).join('/')

const load = async () => {
  loading.value = true
  try {
    data.value = await fetchForecast(days.value)
  } catch {
    toast.error('Não foi possível calcular a previsão.')
  } finally {
    loading.value = false
  }
}

const series = computed(() => [{ name: 'Saldo previsto', data: (data.value?.points ?? []).map((p: any) => ({ x: localDay(p.date), y: p.balance })) }])

const chartOptions = computed(() => ({
  chart: { toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
  colors: ['#2a78d6'],
  stroke: { width: 2, curve: 'stepline' },
  fill: { type: 'gradient', gradient: { opacityFrom: 0.25, opacityTo: 0.02 } },
  dataLabels: { enabled: false },
  xaxis: { type: 'datetime', labels: { datetimeUTC: false, format: 'dd/MM', style: { colors: '#6b7280' } }, tooltip: { enabled: false } },
  yaxis: { labels: { formatter: formatBRLCompact, style: { colors: '#6b7280' } } },
  grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
  annotations: { yaxis: [{ y: 0, borderColor: '#9ca3af', strokeDashArray: 4 }] },
  tooltip: { x: { format: 'dd/MM/yyyy' }, y: { formatter: (v: number) => formatBRL(v) } },
}))

watch(days, load)
onMounted(load)
defineExpose({ reload: load })
</script>
