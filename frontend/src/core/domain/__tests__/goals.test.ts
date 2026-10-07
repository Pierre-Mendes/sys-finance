import { describe, it, expect } from 'vitest'
import { contributionReachesGoal } from '../goals'

describe('contributionReachesGoal', () => {
  it('é verdadeiro quando o aporte cruza o alvo', () => {
    expect(contributionReachesGoal(900, 1000, 100)).toBe(true)
    expect(contributionReachesGoal(900, 1000, 250)).toBe(true)
  })

  it('é falso quando ainda falta', () => {
    expect(contributionReachesGoal(500, 1000, 100)).toBe(false)
  })

  it('não comemora de novo uma meta já concluída', () => {
    expect(contributionReachesGoal(1000, 1000, 50)).toBe(false)
  })

  it('ignora meta sem alvo e aporte não positivo', () => {
    expect(contributionReachesGoal(0, 0, 10)).toBe(false)
    expect(contributionReachesGoal(990, 1000, 0)).toBe(false)
  })
})
