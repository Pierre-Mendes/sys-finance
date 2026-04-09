import { defineStore } from 'pinia'
import api from '@/data/api/HttpClient'
import type { Workspace } from '@/core/domain/Workspace'

export const useWorkspaceStore = defineStore('workspace', {
    state: () => ({
        workspaces: [] as Workspace[],
        activeWorkspaceId: localStorage.getItem('workspaceId') ? Number(localStorage.getItem('workspaceId')) : null,
        isView360: false,
    }),
    getters: {
        activeWorkspace: (state) => state.workspaces.find(w => w.id === state.activeWorkspaceId) || null,
        isOwner: (state): boolean => {
            const ws = state.workspaces.find(w => w.id === state.activeWorkspaceId)
            return ws ? ws.role === 'owner' : false
        }
    },
    actions: {
        async fetchWorkspaces() {
            try {
                const response = await api.get('/api/workspaces')
                this.workspaces = response.data.data
                
                // Configura um default se não existir ou se perdeu o workspace
                if (this.workspaces.length > 0) {
                    if (!this.activeWorkspaceId || !this.workspaces.find(w => w.id === this.activeWorkspaceId)) {
                        this.setActiveWorkspace(this.workspaces[0].id)
                    }
                } else {
                    this.activeWorkspaceId = null
                    localStorage.removeItem('workspaceId')
                }
            } catch (e) {
                console.error('Failed to fetch workspaces', e)
            }
        },
        setActiveWorkspace(id: number | null) {
            this.activeWorkspaceId = id
            if (id) {
                localStorage.setItem('workspaceId', id.toString())
            } else {
                localStorage.removeItem('workspaceId')
            }
        },
        setView360(status: boolean) {
            this.isView360 = status
        }
    }
})
