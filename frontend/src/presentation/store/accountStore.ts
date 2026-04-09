import { defineStore } from 'pinia'
import { accountRepository } from '@/data/repositories/AccountRepositoryImpl'
import type { Account } from '@/core/domain/Account'

export const useAccountStore = defineStore('account', {
    state: () => ({
        accounts: [] as Account[],
        isLoading: false,
    }),
    getters: {
        totalBalance: (state) => state.accounts.reduce((acc, account) => acc + account.balance, 0),
    },
    actions: {
        async fetchAccounts() {
            if (this.accounts.length > 0) return // Cached
            
            this.isLoading = true
            try {
                this.accounts = await accountRepository.getAccounts()
            } catch (e) {
                console.error('Failed to load accounts', e)
            } finally {
                this.isLoading = false
            }
        },
        async forceRefreshAccounts() {
            this.accounts = [] // Clear cache
            await this.fetchAccounts()
        }
    }
})
