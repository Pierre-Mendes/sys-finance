<script setup lang="ts">
// Registro "tree-shaken" do ECharts: só os tipos e componentes usados entram no bundle.
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { BarChart, LineChart, PieChart } from 'echarts/charts'
import { GridComponent, LegendComponent, MarkLineComponent, TooltipComponent } from 'echarts/components'
import VChart from 'vue-echarts'

use([CanvasRenderer, BarChart, LineChart, PieChart, GridComponent, LegendComponent, MarkLineComponent, TooltipComponent])

withDefaults(defineProps<{
  option: Record<string, any>
  /** Altura do gráfico (px ou CSS, ex.: "100%"). */
  height?: number | string
  /** Descrição para leitores de tela: o canvas não tem texto acessível. */
  label: string
}>(), {
  height: 280,
})
</script>

<template>
  <div role="img" :aria-label="label" class="w-full" :style="{ height: typeof height === 'number' ? `${height}px` : height }">
    <!-- notMerge: ao trocar de período/tema a opção substitui a anterior (séries removidas não ficam na tela) -->
    <VChart :option="option" autoresize :update-options="{ notMerge: true }" />
  </div>
</template>
