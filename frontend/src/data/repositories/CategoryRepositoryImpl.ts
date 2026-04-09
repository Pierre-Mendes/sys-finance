import type { ICategoryRepository } from '@/core/repositories/ICategoryRepository'
import type { Category } from '@/core/domain/Category'
import api from '@/data/api/HttpClient'

export class CategoryRepositoryImpl implements ICategoryRepository {
    async getCategories(): Promise<Category[]> {
        const response = await api.get('/api/categories')
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
