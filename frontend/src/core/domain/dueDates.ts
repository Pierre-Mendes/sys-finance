/** Dias entre hoje (data local) e o vencimento 'YYYY-MM-DD'. Negativo = atrasada. */
export function daysUntil(dueDate: string, today: Date = new Date()): number {
  const [y, m, d] = dueDate.slice(0, 10).split('-').map(Number)
  const due = Date.UTC(y!, m! - 1, d!)
  const now = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate())
  return Math.round((due - now) / 86_400_000)
}

export function dueLabel(days: number): string {
  if (days < -1) return `${-days}d atrasada`
  if (days === -1) return 'Venceu ontem'
  if (days === 0) return 'Hoje'
  if (days === 1) return 'Amanhã'
  return `Em ${days} dias`
}
