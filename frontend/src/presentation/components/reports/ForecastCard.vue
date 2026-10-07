<template>
  <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6">
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h3 class="text-lg font-bold text-gray-800">Saldo: realizado e previsto</h3>
        <p class="text-xs text-gray-500">Linha sólida: saldo no fim de cada mês até hoje. Tracejada: previsão com contas a pagar e receber, recorrências e compras no cartão ainda sem fatura.</p>
      </div>
      <div class="inline-flex flex-shrink-0 bg-gray-100 rounded-lg p-1 self-start" role="group" aria-label="Horizonte da previsão">
        <button v-for="d in HORIZONS" :key="d" type="button" @click="days = d"
                :class="['px-3 py-1.5 text-sm rounded-md font-medium whitespace-nowrap', days === d ? 'bg-white shadow text-gray-900' : 'text-gray-500']">
          {{ d }} dias
        </button>
      </div>
    </header>

    <div v-if="!data && loading" class="h-64 flex items-center justify-center text-sm text-gray-500">Calculando previsão...</div>
    <!-- Ao trocar o horizonte o gráfico continua montado e só atualiza (sem piscar) -->
    <div v-else-if="data" :class="{ 'opacity-60 pointer-events-none': loading }">
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

      <p v-if="data.stale?.count" class="mb-4 text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-xl p-3">
        {{ data.stale.count }} {{ data.stale.count === 1 ? 'conta atrasada' : 'contas atrasadas' }} há mais de {{ data.stale.olderThanDays }} dias
        ({{ formatBRL(Math.abs(data.stale.amount)) }}) {{ data.stale.count === 1 ? 'ficou' : 'ficaram' }} fora da previsão.
        <router-link :to="{ path: '/dashboard', query: { review: 'overdue' } }" class="font-semibold underline">Revisar</router-link>
      </p>

      <BaseChart :height="260" :option="chartOption" label="Gráfico do saldo realizado (linha sólida) e previsto (tracejada) por dia" />

      <details v-if="data.events.length" class="mt-4 group">
        <summary class="cursor-pointer text-sm font-medium text-primary select-none">Ver os {{ data.events.length }} lançamentos previstos</summary>
        <ul class="mt-3 divide-y divide-gray-100 text-sm">
          <li v-for="(e, i) in data.events" :key="i" class="py-2 flex items-center gap-3">
            <span class="w-14 text-gray-500 tabular-nums">{{ formatDay(e.date) }}</span>
            <span class="flex-1 min-w-0 truncate text-gray-800">{{ e.title }}</span>
            <span class="text-xs uppercase font-semibold px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">{{ KIND_LABEL[e.kind] }}</span>
            <span :class="['font-semibold tabular-nums whitespace-nowrap', e.amount < 0 ? 'text-red-700' : 'text-emerald-700']">{{ formatBRL(e.amount) }}</span>
          </li>
        </ul>
      </details>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import { fetchForecast } from '@/data/api/reportApi'
import { formatBRL, formatBRLCompact } from '@/core/domain/money'
import BaseChart from '@/components/ui/BaseChart.vue'
import { areaSeries, axisTooltip, cartesian, chartTheme, referenceLine } from '@/presentation/charts/chartOptions'
import { useTheme } from '@/presentation/composables/useTheme'

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

// Duas séries no mesmo eixo de tempo: identidade pelo traço (sólido x tracejado) + legenda, não só pela cor.
const COLOR = '#2346D8'
const { isDark } = useTheme()
const toPoints = (list: any[] | undefined) => (list ?? []).map((p: any) => [localDay(p.date), p.balance])
const todayTs = computed(() => (data.value?.points?.[0] ? localDay(data.value.points[0].date) : Date.now()))
const formatTs = (ts: number, withYear = false) => {
  const d = new Date(ts)
  const dm = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}`
  return withYear ? `${dm}/${d.getFullYear()}` : dm
}

const chartOption = computed(() => {
  const t = chartTheme(isDark.value)
  return {
    ...cartesian(t, { time: true, xFormatter: (v: number) => formatTs(v), yFormatter: formatBRLCompact, legend: 'left' }),
    tooltip: axisTooltip(t, [COLOR, COLOR], formatBRL, (ts: number) => formatTs(ts, true)),
    series: [
      areaSeries('Realizado (linha sólida)', COLOR, toPoints(data.value?.history), t, {
        smooth: false,
        markLine: {
          ...referenceLine(t, 0),
          data: [
            { yAxis: 0 },
            // "Hoje": separa o realizado da previsão
            { xAxis: todayTs.value, lineStyle: { color: t.text, type: 'solid', width: 1 },
              label: { show: true, formatter: 'Hoje', position: 'insideEndTop', color: t.text, fontFamily: 'inherit', fontWeight: 600 } },
          ],
        },
      }),
      {
        type: 'line',
        name: 'Previsto (tracejado)',
        data: toPoints(data.value?.points),
        step: 'end',
        showSymbol: false,
        symbol: 'circle',
        lineStyle: { width: 2, type: [6, 6], color: COLOR },
        itemStyle: { color: COLOR },
      },
    ],
  }
})

watch(days, load)
onMounted(load)
defineExpose({ reload: load })
</script>
