import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../authStore'


// Mock HttpClient
vi.mock('@/data/api/HttpClient', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
  },
  clearLocalSession: () => {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('workspaceId')
  },
  logout: vi.fn(),
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

  it('setUser guarda só dados de exibição, nunca o token', () => {
    const store = useAuthStore()
    localStorage.setItem('token', 'legado')
    const mockUser = { id: 1, firstName: 'John', email: 'john@example.com' }

    store.setUser(mockUser)

    expect(store.isAuthenticated).toBe(true)
    expect(store.user).toEqual(mockUser)
    expect(localStorage.getItem('user')).toBe(JSON.stringify(mockUser))
    expect(localStorage.getItem('token')).toBeNull()
    expect('token' in store.$state).toBe(false)
  })

  it('logout should reset state', () => {
    const store = useAuthStore()
    store.user = { id: 1, firstName: 'John', email: 'john@example.com' }
    
    store.clearAuth()

    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(localStorage.getItem('user')).toBeNull()
  })
})
