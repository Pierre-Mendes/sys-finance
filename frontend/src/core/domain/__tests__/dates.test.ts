import { describe, it, expect } from 'vitest'
import { formatDateBR, formatTimeBR } from '../dates'

describe('formatDateBR', () => {
  it('data sem horário não muda de dia por causa do fuso', () => {
    expect(formatDateBR('2026-12-31')).toBe('31/12/2026')
    expect(formatDateBR('2026-01-01')).toBe('01/01/2026')
  })

  it('data com horário sai em dd/mm/aaaa mesmo com navegador em inglês', () => {
    expect(formatDateBR(new Date(2026, 9, 7, 15, 30))).toBe('07/10/2026')
  })

  it('vazio ou inválido vira travessão', () => {
    expect(formatDateBR(null)).toBe('—')
    expect(formatDateBR('não é data')).toBe('—')
  })
})

describe('formatTimeBR', () => {
  it('formata hh:mm em 24h', () => {
    expect(formatTimeBR(new Date(2026, 9, 7, 15, 5))).toBe('15:05')
  })
})
