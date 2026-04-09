import { defineStore } from 'pinia'
import api from '@/data/api/HttpClient'
import { ref } from 'vue'
import { toast } from 'vue3-toastify'

export interface CreditCard {
  id: number
  accountId: number
  name: string
  brand: string | null
  limitAmount: number
  closingDay: number
  dueDay: number
  color: string
  usedAmount: number
}

export interface CardTransaction {
  id: number
  cardId: number
  categoryId: number
  title: string
  amount: number
  date: string
  installments: number
  currentInstallment: number
  description: string | null
}

export const useCreditCardStore = defineStore('creditCard', () => {
  const cards = ref<CreditCard[]>([])
  const selectedCardId = ref<number | null>(null)
  const cardTransactions = ref<CardTransaction[]>([])
  const isLoading = ref(false)
  const isTransactionsLoading = ref(false)

  async function fetchCards() {
    isLoading.value = true
    try {
      const response = await api.get('/api/credit-cards')
      if (response.data.success) {
        cards.value = response.data.data
        
        // Select first card if none selected
        if (cards.value.length > 0 && !selectedCardId.value) {
          selectedCardId.value = cards.value[0].id
        }
      }
    } catch (error) {
      console.error('Failed to fetch credit cards', error)
      toast.error('Erro ao listar cartões.')
    } finally {
      isLoading.value = false
    }
  }

  async function fetchCardTransactions(cardId: number) {
    isTransactionsLoading.value = true
    try {
      const response = await api.get(`/api/credit-cards/${cardId}/transactions`)
      if (response.data.success) {
        cardTransactions.value = response.data.data
      }
    } catch (error) {
      console.error('Failed to fetch card transactions', error)
      toast.error('Erro ao carregar compras do cartão.')
    } finally {
      isTransactionsLoading.value = false
    }
  }

  async function createCard(payload: Partial<CreditCard>) {
    isLoading.value = true
    try {
      await api.post('/api/credit-cards', payload)
      await fetchCards()
      return true
    } catch (error) {
      console.error('Failed to create credit card', error)
      toast.error('Erro ao criar cartão.')
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function updateCard(id: number, payload: Partial<CreditCard>) {
    try {
      await api.put(`/api/credit-cards/${id}`, payload)
      toast.success('Cartão atualizado com sucesso!')
      await fetchCards()
      return true
    } catch (error) {
      console.error('Failed to update credit card', error)
      toast.error('Erro ao atualizar cartão.')
      return false
    }
  }

  async function deleteCard(id: number) {
    try {
      await api.delete(`/api/credit-cards/${id}`)
      await fetchCards()
      if (selectedCardId.value === id) {
        selectedCardId.value = cards.value.length > 0 ? cards.value[0].id : null
      }
      toast.info('Cartão removido.')
      return true
    } catch (error) {
      console.error('Failed to delete credit card', error)
      toast.error('Erro ao excluir cartão.')
      return false
    }
  }

  async function deleteTransaction(txId: number) {
    try {
      await api.delete(`/api/credit-cards/transactions/${txId}`)
      toast.info('Compra removida.')
      if (selectedCardId.value) {
        await fetchCardTransactions(selectedCardId.value)
        await fetchCards() // To update usedAmount
      }
      return true
    } catch (error) {
      console.error('Failed to delete transaction', error)
      toast.error('Erro ao excluir compra.')
      return false
    }
  }

  async function updateTransaction(txId: number, payload: any) {
    try {
      await api.put(`/api/credit-cards/transactions/${txId}`, payload)
      toast.success('Compra atualizada.')
      if (selectedCardId.value) {
        await fetchCardTransactions(selectedCardId.value)
        await fetchCards() // To update usedAmount
      }
      return true
    } catch (error) {
      console.error('Failed to update transaction', error)
      toast.error('Erro ao atualizar compra.')
      return false
    }
  }

  async function addTransaction(cardId: number, tx: any) {
    isLoading.value = true
    try {
      await api.post(`/api/credit-cards/${cardId}/transactions`, tx)
      toast.success('Compra adicionada com sucesso!')
      await fetchCards()
      if (selectedCardId.value === cardId) {
        await fetchCardTransactions(cardId)
      }
      return true
    } catch (error) {
      console.error('Failed to add transaction to card', error)
      toast.error('Erro ao adicionar compra.')
      return false
    } finally {
      isLoading.value = false
    }
  }

  async function generateInvoice(cardId: number, month: number, year: number) {
    isLoading.value = true
    try {
      const response = await api.post(`/api/credit-cards/${cardId}/invoices/generate`, { month, year })
      return response.data;
    } catch (error: any) {
      console.error('Failed to generate invoice', error)
      toast.error(error.response?.data?.error || 'Erro ao gerar fatura.')
      return null
    } finally {
      isLoading.value = false
    }
  }

  return {
    cards,
    selectedCardId,
    cardTransactions,
    isLoading,
    isTransactionsLoading,
    fetchCards,
    fetchCardTransactions,
    createCard,
    updateCard,
    addTransaction,
    generateInvoice,
    deleteCard,
    deleteTransaction,
    updateTransaction
  }
})
