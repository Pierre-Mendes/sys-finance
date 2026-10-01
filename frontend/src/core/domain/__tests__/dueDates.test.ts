import { describe, it, expect } from 'vitest'
import { daysUntil, dueLabel } from '../dueDates'

describe('dueDates', () => {
  const today = new Date(2026, 9, 31, 23, 30) // 31/10, tarde da noite (fuso local)

  it('conta dias pelo calendário, sem erro de fuso ou virada de mês', () => {
    expect(daysUntil('2026-10-31', today)).toBe(0)
    expect(daysUntil('2026-11-01', today)).toBe(1)
    expect(daysUntil('2026-10-28T00:00:00', today)).toBe(-3)
  })

  it('descreve o vencimento', () => {
    expect(dueLabel(-3)).toBe('3d atrasada')
    expect(dueLabel(-1)).toBe('Venceu ontem')
    expect(dueLabel(0)).toBe('Hoje')
    expect(dueLabel(1)).toBe('Amanhã')
    expect(dueLabel(5)).toBe('Em 5 dias')
  })
})
