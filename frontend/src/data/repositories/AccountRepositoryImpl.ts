import type { IAccountRepository } from '@/core/repositories/IAccountRepository'
import type { Account } from '@/core/domain/Account'
import api from '@/data/api/HttpClient'

export class AccountRepositoryImpl implements IAccountRepository {
    async getAccounts(): Promise<Account[]> {
        const response = await api.get('/api/accounts')
        return response.data.data
    }

    async createAccount(account: Partial<Account>): Promise<Account> {
        const response = await api.post('/api/accounts', account)
        return response.data.data
    }

    async updateAccount(id: number, account: Partial<Account>): Promise<Account> {
        const response = await api.put(`/api/accounts/${id}`, account)
        return response.data.data
    }

    async deleteAccount(id: number): Promise<void> {
        await api.delete(`/api/accounts/${id}`)
    }
}

export const accountRepository = new AccountRepositoryImpl()
