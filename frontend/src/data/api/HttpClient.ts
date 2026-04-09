import axios from 'axios'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || ''
})

api.interceptors.request.use(config => {
    const token = localStorage.getItem('token')
    const workspaceId = localStorage.getItem('workspaceId')
    
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    if (workspaceId) {
        config.headers['X-Workspace-Id'] = workspaceId
    }
    
    return config
})

export default api
