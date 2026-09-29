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
        // Token inválido/expirado (ex.: tokens antigos pré-JWT): encerra a sessão e volta ao login.
        if (error.response?.status === 401 && localStorage.getItem('token')) {
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            localStorage.removeItem('workspaceId')
            if (window.location.pathname !== '/') window.location.assign('/')
        }
        return Promise.reject(error)
    }
)

export default api
