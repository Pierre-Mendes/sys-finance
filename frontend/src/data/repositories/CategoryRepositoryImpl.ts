import type { ICategoryRepository } from '@/core/repositories/ICategoryRepository'
import type { Category } from '@/core/domain/Category'
import api from '@/data/api/HttpClient'

export class CategoryRepositoryImpl implements ICategoryRepository {
    async getCategories(): Promise<Category[]> {
        // type=all devolve receitas ('income') e despesas ('bill') com o campo `type` preenchido.
        const response = await api.get('/api/categories?type=all')
        return response.data.data
    }

    async createCategory(category: Partial<Category>): Promise<Category> {
        const response = await api.post('/api/categories', category)
        return response.data.data
    }

    async updateCategory(id: number, category: Partial<Category>): Promise<Category> {
        const response = await api.put(`/api/categories/${id}`, category)
        return response.data.data
    }

    async deleteCategory(id: number): Promise<void> {
        await api.delete(`/api/categories/${id}`)
    }
}

export const categoryRepository = new CategoryRepositoryImpl()
