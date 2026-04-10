import { defineStore } from 'pinia'
import api from '@/data/api/HttpClient'
import { ref } from 'vue'

export interface Goal {
  id: number
  workspaceId: number
  sharedWithWorkspaceId: number | null
  accountId: number | null
  isFavorite: boolean
  title: string
  targetAmount: number
  accumulatedAmount: number
  targetDate: string | null
}

export const useGoalStore = defineStore('goal', () => {
  const goals = ref<Goal[]>([])
  const isLoading = ref(false)

  async function fetchGoals() {
    isLoading.value = true
    try {
      const response = await api.get('/api/goals')
      if (response.data.success) {
        goals.value = response.data.data
      }
    } catch (error) {
      console.error('Failed to fetch goals', error)
    } finally {
      isLoading.value = false
    }
  }

  async function createGoal(payload: Partial<Goal>) {
    isLoading.value = true
    try {
      await api.post('/api/goals', payload)
      await fetchGoals()
      return true
    } catch (error) {
      console.error('Failed to create goal', error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function updateGoal(id: number, payload: Partial<Goal>) {
    isLoading.value = true
    try {
      await api.put(`/api/goals/${id}`, payload)
      await fetchGoals()
      return true
    } catch (error) {
      console.error('Failed to update goal', error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function deleteGoal(id: number) {
    isLoading.value = true
    try {
      await api.delete(`/api/goals/${id}`)
      await fetchGoals()
      return true
    } catch (error) {
      console.error('Failed to delete goal', error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function addContribution(goalId: number, amount: number, accountId?: number | null, description?: string) {
    isLoading.value = true
    try {
      await api.post('/api/goals/contributions', { goalId, amount, accountId, description })
      await fetchGoals()
      return true
    } catch (error) {
      console.error('Failed to add contribution', error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  return { goals, isLoading, fetchGoals, createGoal, updateGoal, deleteGoal, addContribution }
})
