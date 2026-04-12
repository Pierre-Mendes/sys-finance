import axios from 'axios'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '',
    headers: {
        'Accept': 'application/json'
    }
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

api.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.data) {
            console.error('[API Error Detected]:', JSON.stringify(error.response.data, null, 2))
        }
        return Promise.reject(error)
    }
)

export default api
