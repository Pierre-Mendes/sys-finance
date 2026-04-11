import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../authStore'
import HttpClient from '@/data/api/HttpClient'

// Mock HttpClient
vi.mock('@/data/api/HttpClient', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
  },
}))

describe('AuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('initial state should be logged out', () => {
    const store = useAuthStore()
    expect(store.isAuthenticated).toBe(false)
    expect(store.user).toBeNull()
  })

  it('setAuth should update state and storage', () => {
    const store = useAuthStore()
    const mockUser = { id: 1, firstName: 'John', email: 'john@example.com' }
    const mockToken = 'fake-token'
    
    store.setAuth(mockToken, mockUser)

    expect(store.isAuthenticated).toBe(true)
    expect(store.token).toBe(mockToken)
    expect(store.user).toEqual(mockUser)
    expect(localStorage.getItem('token')).toBe(mockToken)
    expect(localStorage.getItem('user')).toBe(JSON.stringify(mockUser))
  })

  it('logout should reset state', () => {
    const store = useAuthStore()
    store.user = { id: 1, firstName: 'John', email: 'john@example.com' }
    
    store.clearAuth()

    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(localStorage.getItem('user')).toBeNull()
  })
})
