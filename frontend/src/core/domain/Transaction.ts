export type TransactionType = 'asset' | 'income' | 'bill' | 'expense' | 'transfer';
export type TransactionStatus = 'PAID' | 'PENDING';
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
}
