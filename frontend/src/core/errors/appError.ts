import { ref } from 'vue'

/** Códigos que têm tela própria. 503 = servidor inalcançável (sem resposta / sem internet). */
export type ErrorCode = 400 | 403 | 404 | 500 | 503

export interface AppError {
    code: ErrorCode
    /** Código de referência devolvido pela API em erros internos ("(ref. 1a2b3c4d)"), para o suporte. */
    reference?: string
}

/** Erro que substitui a tela atual (o App.vue mostra a página de erro no lugar do router-view). */
export const currentError = ref<AppError | null>(null)

export function showError(error: AppError) {
    currentError.value = error
}

export function clearError() {
    currentError.value = null
}

export function extractReference(message: unknown): string | undefined {
    if (typeof message !== 'string') return undefined
    return message.match(/ref\.\s*([0-9a-f]{8})/i)?.[1]
}

/**
 * Decide se uma falha de requisição deve virar uma tela de erro.
 * Só leituras (GET) que montam a tela: falha de escrita (salvar, excluir) continua como aviso no formulário,
 * e 401 é tratado à parte (volta ao login). Chamadas de fundo marcam `skipErrorPage`.
 */
export function errorPageFor(error: any): AppError | null {
    const config = error?.config || {}
    if (config.skipErrorPage) return null
    if ((config.method || 'get').toLowerCase() !== 'get') return null

    if (!error?.response) {
        // Requisição cancelada de propósito não é erro do usuário.
        if (error?.code === 'ERR_CANCELED') return null
        return { code: 503 }
    }

    const status: number = error.response.status
    if (status >= 500) return { code: 500, reference: extractReference(error.response.data?.error) }
    if (status === 403) return { code: 403 }
    if (status === 400) return { code: 400 }
    return null
}

/**
 * Erro do Vue que impede a tela de aparecer (setup, render, created, mounted).
 * Em desenvolvimento `info` é descritivo ("render function"); no build de produção é um link
 * "https://vuejs.org/error-reference/#runtime-<código>" (0 = setup, 1 = render, c = created, m = mounted).
 */
export function isRenderError(info: string): boolean {
    return /setup function|render function|created hook|mounted hook/i.test(info)
        || /#runtime-(0|1|c|m)$/.test(info)
}
