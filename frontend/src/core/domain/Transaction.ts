export type TransactionType = 'asset' | 'income' | 'bill' | 'expense' | 'transfer';
/** CANCELED = desconsiderada: conta pendente que o usuário decidiu não pagar (fica no histórico). */
export type TransactionStatus = 'PAID' | 'PENDING' | 'CANCELED';
export type TransactionPriority = 'LOW' | 'NORMAL' | 'HIGH';
export type TransactionRecurrence = 'NONE' | 'MONTHLY' | 'YEARLY';

export interface Transaction {
    id?: number;
    title: string;
    amount: number;
    date: string;
    dueDate?: string;
    type: TransactionType;
    status: TransactionStatus;
    priority: TransactionPriority;
    recurrence_type: TransactionRecurrence;
    description?: string;
    categoryId?: number;
    accountId?: number;
    /** Alternativa ao ID: o backend busca a categoria/conta pelo nome ou cria se não existir. */
    categoryName?: string;
    accountName?: string;
}
