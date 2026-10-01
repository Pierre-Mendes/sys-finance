import api from '@/data/api/HttpClient'

export type ReportType = 'monthly' | 'annual'

export interface ReportPeriodQuery {
  type: ReportType
  month?: string
  year?: number
}

export async function fetchReportSummary(q: ReportPeriodQuery) {
  const { data } = await api.get('/api/reports/summary', { params: q })
  return data.data
}

export async function fetchForecast(days: 30 | 60 | 90) {
  const { data } = await api.get('/api/reports/forecast', { params: { days } })
  return data.data
}

/** Baixa um arquivo da API (com token e X-Workspace-Id do HttpClient). */
export async function downloadReport(path: string, params: Record<string, unknown>, filename: string) {
  const response = await api.get(path, { params, responseType: 'blob' })
  const url = window.URL.createObjectURL(new Blob([response.data]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}
