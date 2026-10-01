import type { Transaction } from '../domain/Transaction'

export interface ITransactionRepository {
    getTransactions(): Promise<Transaction[]>;
    createTransaction(transaction: Partial<Transaction>): Promise<Transaction>;
    updateTransaction(id: number, transaction: Partial<Transaction>): Promise<Transaction>;
    deleteTransaction(id: number, type: string): Promise<void>;
    payTransaction(id: number, isPaid: boolean, type: string): Promise<Transaction>;
    rescheduleTransaction(id: number, type: string, dueDate: string): Promise<void>;
    cancelTransaction(id: number, type: string): Promise<void>;
}
