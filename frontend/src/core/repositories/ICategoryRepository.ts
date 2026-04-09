import type { Category } from '../domain/Category'

export interface ICategoryRepository {
    getCategories(): Promise<Category[]>;
    createCategory(category: Partial<Category>): Promise<Category>;
    updateCategory(id: number, category: Partial<Category>): Promise<Category>;
    deleteCategory(id: number): Promise<void>;
}
