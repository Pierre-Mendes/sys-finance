const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })

export function formatBRL(value: number | null | undefined): string {
  return brl.format(Number(value ?? 0))
}

/** Eixos de gráfico: "R$ 1,2 mil", "R$ 3,4 mi". */
export function formatBRLCompact(value: number): string {
  const abs = Math.abs(value)
  const sign = value < 0 ? '-' : ''
  if (abs >= 1_000_000) return `${sign}R$ ${(abs / 1_000_000).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} mi`
  if (abs >= 1_000) return `${sign}R$ ${(abs / 1_000).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} mil`
  return `${sign}R$ ${abs.toLocaleString('pt-BR', { maximumFractionDigits: 0 })}`
}

/** Variação em %, com sinal; null (sem base de comparação) vira "—". */
export function formatChange(value: number | null | undefined): string {
  if (value === null || value === undefined || !Number.isFinite(value)) return '—'
  return `${value > 0 ? '+' : ''}${value.toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%`
}
