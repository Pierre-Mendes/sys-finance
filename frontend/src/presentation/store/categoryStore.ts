import { defineStore } from 'pinia'
import { categoryRepository } from '@/data/repositories/CategoryRepositoryImpl'
import type { Category } from '@/core/domain/Category'

export const useCategoryStore = defineStore('category', {
    state: () => ({
        categories: [] as Category[],
        isLoading: false,
    }),
    getters: {
        getAssets: (state) => state.categories.filter(c => c.type === 'asset' || c.type === 'income'),
        getBills: (state) => state.categories.filter(c => c.type === 'bill' || c.type === 'expense'),
    },
    actions: {
        async fetchCategories() {
            if (this.categories.length > 0) return // Cached
            
            this.isLoading = true
            try {
                this.categories = await categoryRepository.getCategories()
            } catch (e) {
                console.error('Failed to load categories', e)
            } finally {
                this.isLoading = false
            }
        },
        async forceRefreshCategories() {
            this.categories = [] // Clear cache
            await this.fetchCategories()
        }
    }
})
