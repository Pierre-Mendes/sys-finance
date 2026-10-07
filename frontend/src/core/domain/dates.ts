const DATE_ONLY = /^(\d{4})-(\d{2})-(\d{2})$/

/**
 * Data no formato brasileiro (dd/mm/aaaa), independente do idioma do navegador.
 * "2026-12-31" (só data) é lida sem fuso: new Date() a trataria como UTC e mostraria o dia 30 no Brasil.
 */
export function formatDateBR(value: string | Date | null | undefined): string {
  if (!value) return '—'
  if (typeof value === 'string') {
    const m = value.match(DATE_ONLY)
    if (m) return `${m[3]}/${m[2]}/${m[1]}`
  }
  const date = value instanceof Date ? value : new Date(value)
  return Number.isNaN(date.getTime()) ? '—' : date.toLocaleDateString('pt-BR')
}

/** Hora no formato brasileiro (hh:mm). */
export function formatTimeBR(value: string | Date): string {
  const date = value instanceof Date ? value : new Date(value)
  return Number.isNaN(date.getTime()) ? '' : date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
}
