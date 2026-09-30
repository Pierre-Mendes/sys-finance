import { describe, it, expect, vi, beforeEach } from 'vitest'
import api from '@/data/api/HttpClient'
import { transactionRepository } from '../TransactionRepositoryImpl'

vi.mock('@/data/api/HttpClient', () => ({
  default: {
    post: vi.fn().mockResolvedValue({ data: { data: { id: 7 } } }),
    put: vi.fn().mockResolvedValue({ data: { data: {} } }),
  },
}))

describe('TransactionRepositoryImpl', () => {
  beforeEach(() => vi.clearAllMocks())

  it('dá baixa com POST, o único método que a API aceita em /pay', async () => {
    await transactionRepository.payTransaction(7, true, 'bill')

    expect(api.post).toHaveBeenCalledWith('/api/transactions/7/pay', { is_paid: true, type: 'bill' })
    expect(api.put).not.toHaveBeenCalled()
  })
})
