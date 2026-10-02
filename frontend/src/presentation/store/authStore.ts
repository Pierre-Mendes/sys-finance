import { defineStore } from 'pinia'
import api, { clearLocalSession, logout as apiLogout } from '@/data/api/HttpClient'

/**
 * Estado de autenticação da SPA. O token de sessão NÃO fica aqui: ele vive num cookie HttpOnly
 * que o JavaScript não consegue ler. "user" é só para exibição (nome, e-mail).
 */
export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user') || 'null'),
    }),
    getters: {
        isAuthenticated: (state) => !!state.user
    },
    actions: {
        setUser(user: any) {
            this.user = user
            localStorage.removeItem('token')
            localStorage.setItem('user', JSON.stringify(user))
        },
        clearAuth() {
            this.user = null
            clearLocalSession()
        },
        async logout() {
            await apiLogout()
            this.user = null
        },
        async fetchProfile() {
            try {
                const response = await api.get('/api/auth/me')
                this.user = response.data.data
                localStorage.setItem('user', JSON.stringify(this.user))
            } catch (e) {
                console.error('Failed to fetch profile', e)
                this.clearAuth()
            }
        }
    }
})
