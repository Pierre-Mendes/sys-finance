/**
 * Opções do ECharts no padrão do design system (cores, eixos, tooltip, modo escuro).
 *
 * Regras que as telas não precisam repetir:
 * - O tooltip é montado com elementos DOM e textContent: nome digitado pelo usuário nunca vira HTML
 *   e não há atributo style= no markup (a CSP de produção tem style-src 'self', sem 'unsafe-inline').
 * - Cores de texto/grade/tooltip vêm de chartTheme(isDark); as opções são computed e se refazem ao trocar o tema.
 */

export type ValueFormatter = (v: number) => string

export interface ChartTheme {
  text: string
  grid: string
  reference: string
  surface: string
  tooltipBg: string
  tooltipBorder: string
  tooltipText: string
}

// ink-500 / ink-100 / ink-200 no claro; textos e linhas do dark.css no escuro
export const chartTheme = (dark: boolean): ChartTheme =>
  dark
    ? { text: '#C5CCD6', grid: '#2A3754', reference: '#5B6B85', surface: '#17223A', tooltipBg: '#141E33', tooltipBorder: '#2A3754', tooltipText: '#EEF1F5' }
    : { text: '#5B6B85', grid: '#EEF1F5', reference: '#9CA3AF', surface: '#FFFFFF', tooltipBg: '#FFFFFF', tooltipBorder: '#EEF1F5', tooltipText: '#0B1324' }

/** "#2346D8" + 0.25 -> "rgba(35, 70, 216, 0.25)" */
export function withAlpha(hex: string, alpha: number): string {
  const h = hex.replace('#', '')
  const n = parseInt(h.length === 3 ? h.split('').map((c) => c + c).join('') : h, 16)
  return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${alpha})`
}

export interface TooltipRow {
  color: string
  name: string
  value: string
}

/** Conteúdo do tooltip como DOM: textContent escapa tudo; a cor do marcador vai por CSSOM (permitido pela CSP). */
export function tooltipContent(title: string | null, rows: TooltipRow[]): HTMLElement {
  const root = document.createElement('div')
  root.className = 'sf-chart-tooltip'
  if (title) {
    const t = document.createElement('div')
    t.className = 'sf-chart-tooltip__title'
    t.textContent = title
    root.append(t)
  }
  for (const row of rows) {
    const line = document.createElement('div')
    line.className = 'sf-chart-tooltip__row'
    const marker = document.createElement('span')
    marker.className = 'sf-chart-tooltip__marker'
    marker.style.backgroundColor = row.color
    const name = document.createElement('span')
    name.className = 'sf-chart-tooltip__name'
    name.textContent = row.name
    const value = document.createElement('span')
    value.className = 'sf-chart-tooltip__value'
    value.textContent = row.value
    line.append(marker, name, value)
    root.append(line)
  }
  return root
}

const tooltipBase = (t: ChartTheme) => ({
  confine: true,
  backgroundColor: t.tooltipBg,
  borderColor: t.tooltipBorder,
  borderWidth: 1,
  padding: [8, 12],
  textStyle: { color: t.tooltipText, fontFamily: 'inherit' },
  extraCssText: 'border-radius: 12px; box-shadow: 0 8px 24px rgba(11, 19, 36, 0.12);',
})

const pointValue = (p: any): number => (Array.isArray(p.value) ? p.value[1] : p.value)

/** Tooltip das séries de eixo (área/barra): título = rótulo do eixo X, uma linha por série. */
export function axisTooltip(t: ChartTheme, colors: string[], fmt: ValueFormatter, title?: (axisValue: any) => string) {
  return {
    ...tooltipBase(t),
    trigger: 'axis',
    axisPointer: { type: 'line', lineStyle: { color: t.reference, type: 'dashed' } },
    formatter: (params: any) => {
      const list = (Array.isArray(params) ? params : [params]).filter((p: any) => pointValue(p) != null)
      if (!list.length) return ''
      const head = title ? title(list[0].axisValue) : String(list[0].axisValueLabel ?? list[0].name ?? '')
      return tooltipContent(head, list.map((p: any) => ({ color: colors[p.seriesIndex] ?? colors[0]!, name: p.seriesName, value: fmt(pointValue(p)) })))
    },
  }
}

/** Grade, eixos e legenda de gráficos cartesianos. */
export function cartesian(t: ChartTheme, opts: { categories?: string[]; time?: boolean; xFormatter?: (v: number) => string; yFormatter: ValueFormatter; legend?: 'left' | 'right' }) {
  const axisLabel = { color: t.text, fontFamily: 'inherit', fontSize: 12 }
  return {
    textStyle: { fontFamily: 'inherit' },
    grid: { left: 8, right: 12, top: opts.legend ? 40 : 16, bottom: 8, containLabel: true },
    legend: opts.legend
      ? { show: true, top: 0, [opts.legend]: 0, icon: 'circle', itemWidth: 10, itemHeight: 10, textStyle: { color: t.text, fontFamily: 'inherit' } }
      : { show: false },
    xAxis: {
      type: opts.time ? 'time' : 'category',
      data: opts.time ? undefined : opts.categories,
      boundaryGap: opts.time ? undefined : true,
      axisLine: { lineStyle: { color: t.grid } },
      axisTick: { show: false },
      axisLabel: { ...axisLabel, hideOverlap: true, formatter: opts.xFormatter },
      splitLine: { show: false },
    },
    yAxis: {
      type: 'value',
      axisLabel: { ...axisLabel, formatter: opts.yFormatter },
      splitLine: { lineStyle: { color: t.grid, type: 'dashed' } },
    },
  }
}

/** Série de área com degradê vertical (cor -> quase transparente). */
export function areaSeries(name: string, color: string, data: any[], t: ChartTheme, extra: Record<string, unknown> = {}) {
  return {
    type: 'line',
    name,
    data,
    smooth: true,
    symbol: 'circle',
    symbolSize: 7,
    showSymbol: data.length <= 31,
    lineStyle: { width: 2, color },
    itemStyle: { color, borderColor: t.surface, borderWidth: 2 },
    areaStyle: {
      color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1, colorStops: [{ offset: 0, color: withAlpha(color, 0.25) }, { offset: 1, color: withAlpha(color, 0.02) }] },
    },
    ...extra,
  }
}

/** Série de barras com cantos arredondados no topo. */
export function barSeries(name: string, color: string, data: number[]) {
  return { type: 'bar', name, data, barMaxWidth: 28, itemStyle: { color, borderRadius: [4, 4, 0, 0] } }
}

/** Linha tracejada de referência (ex.: saldo zero) num eixo. */
export const referenceLine = (t: ChartTheme, value: number, axis: 'yAxis' | 'xAxis' = 'yAxis') => ({
  silent: true,
  symbol: 'none',
  label: { show: false },
  lineStyle: { color: t.reference, type: 'dashed', width: 1 },
  data: [{ [axis]: value }],
})

export interface DonutSlice {
  name: string
  value: number
  color: string
}

/** Rosca sem rótulos (a tela mostra a lista com valores ao lado): tooltip com valor e percentual. */
export function donutOption(t: ChartTheme, slices: DonutSlice[], fmt: ValueFormatter) {
  return {
    textStyle: { fontFamily: 'inherit' },
    tooltip: {
      ...tooltipBase(t),
      trigger: 'item',
      formatter: (p: any) =>
        tooltipContent(null, [{ color: p.color, name: p.name, value: `${fmt(p.value)} (${p.percent.toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%)` }]),
    },
    legend: { show: false },
    series: [
      {
        type: 'pie',
        radius: ['62%', '92%'],
        avoidLabelOverlap: true,
        label: { show: false },
        labelLine: { show: false },
        itemStyle: { borderColor: t.surface, borderWidth: 2 },
        emphasis: { scale: true, scaleSize: 4 },
        data: slices.map((s) => ({ name: s.name, value: s.value, itemStyle: { color: s.color } })),
      },
    ],
  }
}
