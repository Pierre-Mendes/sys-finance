import { defineStore } from 'pinia'
import api from '@/data/api/HttpClient'
import { ref } from 'vue'

export interface Goal {
  id: number
  workspaceId: number
  sharedWithWorkspaceId: number | null
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

  async function addContribution(goalId: number, amount: number, description?: string) {
    isLoading.value = true
    try {
      await api.post('/api/goals/contributions', { goalId, amount, description })
      await fetchGoals()
      return true
    } catch (error) {
      console.error('Failed to add contribution', error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  return { goals, isLoading, fetchGoals, createGoal, addContribution }
})
