import { defineStore } from 'pinia'
import api from '@/data/api/HttpClient'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('token') || null,
        user: JSON.parse(localStorage.getItem('user') || 'null'),
    }),
    getters: {
        isAuthenticated: (state) => !!state.token
    },
    actions: {
        setAuth(token: string, user: any) {
            this.token = token
            this.user = user
            localStorage.setItem('token', token)
            localStorage.setItem('user', JSON.stringify(user))
        },
        clearAuth() {
            this.token = null
            this.user = null
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            localStorage.removeItem('workspaceId')
        },
        async fetchProfile() {
            try {
                const response = await api.get('/api/profile')
                this.user = response.data.data
                localStorage.setItem('user', JSON.stringify(this.user))
            } catch (e) {
                console.error('Failed to fetch profile', e)
                this.clearAuth()
            }
        }
    }
})
