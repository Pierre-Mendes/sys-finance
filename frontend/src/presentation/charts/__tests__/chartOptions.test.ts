import { describe, it, expect } from 'vitest'
import { axisTooltip, chartTheme, donutOption, tooltipContent, withAlpha } from '../chartOptions'

const brl = (v: number) => `R$ ${v}`

describe('tooltipContent', () => {
  it('trata nome digitado pelo usuário como texto, nunca como HTML', () => {
    const el = tooltipContent('<b>Jan</b>', [{ color: '#2346D8', name: '<img src=x onerror=alert(1)>', value: 'R$ 10' }])

    expect(el.querySelector('img')).toBeNull()
    expect(el.querySelector('b')).toBeNull()
    expect(el.querySelector('.sf-chart-tooltip__name')!.textContent).toBe('<img src=x onerror=alert(1)>')
    expect(el.querySelector('.sf-chart-tooltip__title')!.textContent).toBe('<b>Jan</b>')
  })

  it('não usa atributo style= no markup (a CSP bloqueia); cor do marcador via CSSOM', () => {
    const el = tooltipContent(null, [{ color: '#0E7A55', name: 'Receitas', value: 'R$ 1' }])
    const marker = el.querySelector<HTMLElement>('.sf-chart-tooltip__marker')!

    expect(marker.style.backgroundColor).toBe('rgb(14, 122, 85)')
    expect(el.querySelector('.sf-chart-tooltip__title')).toBeNull()
  })
})

describe('axisTooltip', () => {
  it('uma linha por série, com a cor da série e o valor formatado', () => {
    const tip = axisTooltip(chartTheme(false), ['#111111', '#222222'], brl)
    const el = tip.formatter([
      { seriesIndex: 0, seriesName: 'Receitas', value: 100, axisValueLabel: 'Jan' },
      { seriesIndex: 1, seriesName: 'Despesas', value: 40, axisValueLabel: 'Jan' },
    ]) as HTMLElement

    expect(el.querySelector('.sf-chart-tooltip__title')!.textContent).toBe('Jan')
    expect([...el.querySelectorAll('.sf-chart-tooltip__value')].map((v) => v.textContent)).toEqual(['R$ 100', 'R$ 40'])
  })

  it('séries de tempo ([data, valor]) usam o título formatado e ignoram pontos vazios', () => {
    const tip = axisTooltip(chartTheme(true), ['#111111', '#222222'], brl, () => '01/10/2026')
    const el = tip.formatter([
      { seriesIndex: 0, seriesName: 'Realizado', value: [1, 50], axisValue: 1 },
      { seriesIndex: 1, seriesName: 'Previsto', value: [1, null], axisValue: 1 },
    ]) as HTMLElement

    expect(el.querySelector('.sf-chart-tooltip__title')!.textContent).toBe('01/10/2026')
    expect(el.querySelectorAll('.sf-chart-tooltip__row')).toHaveLength(1)
  })
})

describe('donutOption', () => {
  it('mantém o nome original da categoria (sem escapar duas vezes) e a cor de cada fatia', () => {
    const opt = donutOption(chartTheme(false), [{ name: 'Café & Padaria', value: 30, color: '#2346D8' }], brl)
    expect(opt.series[0]!.data[0]).toEqual({ name: 'Café & Padaria', value: 30, itemStyle: { color: '#2346D8' } })

    const el = opt.tooltip.formatter({ color: '#2346D8', name: 'Café & Padaria', value: 30, percent: 12.5 }) as HTMLElement
    expect(el.textContent).toContain('Café & Padaria')
    expect(el.textContent).toContain('R$ 30 (12,5%)')
  })
})

describe('withAlpha', () => {
  it('converte hex em rgba', () => {
    expect(withAlpha('#2346D8', 0.25)).toBe('rgba(35, 70, 216, 0.25)')
    expect(withAlpha('#fff', 1)).toBe('rgba(255, 255, 255, 1)')
  })
})
