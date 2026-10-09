import { describe, it, expect } from 'vitest'
import { formatBRLCompact, formatChange, formatPercent } from '../money'

describe('money', () => {
  it('formata eixos de forma compacta', () => {
    expect(formatBRLCompact(1234)).toBe('R$ 1,2 mil')
    expect(formatBRLCompact(-2_500_000)).toBe('-R$ 2,5 mi')
    expect(formatBRLCompact(800)).toBe('R$ 800')
  })

  it('nunca mostra NaN: variação sem base vira travessão', () => {
    expect(formatChange(null)).toBe('—')
    expect(formatChange(NaN)).toBe('—')
    expect(formatChange(12.5)).toBe('+12,5%')
    expect(formatChange(-3)).toBe('-3%')
  })

  // Bug: Investimentos usava toFixed(2) e mostrava "12.35%" (ponto) em vez de "12,35%"
  it('percentual com vírgula decimal e sinal opcional', () => {
    expect(formatPercent(12.3456)).toBe('12,35%')
    expect(formatPercent(-3.1)).toBe('-3,10%')
    expect(formatPercent(5, { signed: true })).toBe('+5,00%')
    expect(formatPercent(0, { signed: true })).toBe('0,00%')
    expect(formatPercent(NaN)).toBe('—')
  })
})
