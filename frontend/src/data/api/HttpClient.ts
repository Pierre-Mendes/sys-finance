import axios from 'axios'
import { errorPageFor, showError } from '@/core/errors/appError'

declare module 'axios' {
    interface AxiosRequestConfig {
        /** Chamadas de fundo (sino, convites): falha não troca a tela pela página de erro. */
        skipErrorPage?: boolean
    }
}

/**
 * Cliente HTTP único da SPA.
 * - Sessão: cookie HttpOnly "sf_session" definido pela API no login (o JS nunca vê o token).
 * - CSRF: em requisições de escrita repete o cookie "sf_csrf" no header X-CSRF-Token (double-submit).
 * - Workspace ativo no header X-Workspace-Id.
 */
const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '',
    withCredentials: true,
    headers: {
        'Accept': 'application/json'
    }
})

export function readCookie(name: string, source: string = document.cookie): string | null {
    for (const part of source.split(';')) {
        const [k, ...v] = part.trim().split('=')
        if (k === name) return decodeURIComponent(v.join('='))
    }
    return null
}

const SAFE_METHODS = ['get', 'head', 'options']

api.interceptors.request.use(config => {
    const workspaceId = localStorage.getItem('workspaceId')
    if (workspaceId) {
        config.headers['X-Workspace-Id'] = workspaceId
    }
    if (!SAFE_METHODS.includes((config.method || 'get').toLowerCase())) {
        const csrf = readCookie('sf_csrf')
        if (csrf) config.headers['X-CSRF-Token'] = csrf
    }
    return config
})

/** Limpa os dados locais da sessão (o cookie é apagado pela API em /api/auth/logout). */
export function clearLocalSession() {
    localStorage.removeItem('token') // legado: versões antigas guardavam o JWT aqui
    localStorage.removeItem('user')
    localStorage.removeItem('workspaceId')
}

export async function logout() {
    try {
        await api.post('/api/auth/logout')
    } finally {
        clearLocalSession()
    }
}

api.interceptors.response.use(
    response => response,
    error => {
        if (import.meta.env.DEV && error.response?.data) {
            console.error('[API Error Detected]:', JSON.stringify(error.response.data, null, 2))
        }
        // Sessão expirada/ausente: volta ao login (exceto na própria tentativa de login).
        const url: string = error.config?.url || ''
        if (error.response?.status === 401 && !url.includes('/api/auth/login') && localStorage.getItem('user')) {
            clearLocalSession()
            if (window.location.pathname !== '/') window.location.assign('/')
            return Promise.reject(error)
        }
        // Leitura que monta a tela falhou (500, 403, 400, sem conexão): mostra a página de erro.
        const page = errorPageFor(error)
        if (page) showError(page)
        return Promise.reject(error)
    }
)

export default api
