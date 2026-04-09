import type { Account } from '../domain/Account'

export interface IAccountRepository {
    getAccounts(): Promise<Account[]>;
    createAccount(account: Partial<Account>): Promise<Account>;
    updateAccount(id: number, account: Partial<Account>): Promise<Account>;
    deleteAccount(id: number): Promise<void>;
}
