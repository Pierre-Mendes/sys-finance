import type { ITransactionRepository } from '@/core/repositories/ITransactionRepository'
import type { Transaction } from '@/core/domain/Transaction'
import api from '@/data/api/HttpClient'

export class TransactionRepositoryImpl implements ITransactionRepository {
    async getTransactions(): Promise<Transaction[]> {
        const response = await api.get('/api/transactions')
        return response.data.data
    }

    async createTransaction(transaction: Partial<Transaction>): Promise<Transaction> {
        const response = await api.post('/api/transactions', transaction)
        return response.data.data
    }

    async updateTransaction(id: number, transaction: Partial<Transaction>): Promise<Transaction> {
        const response = await api.put(`/api/transactions/${id}`, transaction)
        return response.data.data
    }

    async deleteTransaction(id: number, type: string): Promise<void> {
        await api.delete(`/api/transactions/${id}?type=${type}`)
    }

    async payTransaction(id: number, isPaid: boolean, type: string): Promise<Transaction> {
        const response = await api.post(`/api/transactions/${id}/pay`, { is_paid: isPaid, type })
        return response.data.data
    }

    async rescheduleTransaction(id: number, type: string, dueDate: string): Promise<void> {
        await api.post(`/api/transactions/${id}/reschedule`, { type, due_date: dueDate })
    }

    async cancelTransaction(id: number, type: string): Promise<void> {
        await api.post(`/api/transactions/${id}/cancel`, { type })
    }
}

export const transactionRepository = new TransactionRepositoryImpl()
