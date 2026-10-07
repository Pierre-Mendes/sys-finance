import { describe, it, expect } from 'vitest'
import { errorPageFor, extractReference, isRenderError } from '../appError'

const httpError = (status: number | null, method = 'get', extra: any = {}) => ({
  config: { method, ...extra },
  ...(status === null ? {} : { response: { status, data: { error: 'Erro interno ao processar a solicitação. (ref. 1a2b3c4d)' } } }),
})

describe('errorPageFor', () => {
  it('leitura com 500 vira tela de erro com o código de referência', () => {
    expect(errorPageFor(httpError(500))).toEqual({ code: 500, reference: '1a2b3c4d' })
    expect(errorPageFor(httpError(502))?.code).toBe(500)
  })

  it('403 e 400 em leitura têm tela própria', () => {
    expect(errorPageFor(httpError(403))?.code).toBe(403)
    expect(errorPageFor(httpError(400))?.code).toBe(400)
  })

  it('sem resposta do servidor vira 503, mas requisição cancelada não', () => {
    expect(errorPageFor(httpError(null))?.code).toBe(503)
    expect(errorPageFor({ ...httpError(null), code: 'ERR_CANCELED' })).toBeNull()
  })

  it('falha ao salvar continua como aviso no formulário (sem trocar a tela)', () => {
    expect(errorPageFor(httpError(500, 'post'))).toBeNull()
    expect(errorPageFor(httpError(400, 'put'))).toBeNull()
  })

  it('401, 404 e chamadas de fundo não trocam a tela', () => {
    expect(errorPageFor(httpError(401))).toBeNull()
    expect(errorPageFor(httpError(404))).toBeNull()
    expect(errorPageFor(httpError(500, 'get', { skipErrorPage: true }))).toBeNull()
  })
})

describe('extractReference / isRenderError', () => {
  it('extrai a referência da mensagem da API', () => {
    expect(extractReference('Falhou (ref. abcdef12)')).toBe('abcdef12')
    expect(extractReference('Saldo insuficiente.')).toBeUndefined()
    expect(extractReference(undefined)).toBeUndefined()
  })

  it('reconhece erros de montagem da tela em dev e no build de produção', () => {
    expect(isRenderError('render function')).toBe(true)
    expect(isRenderError('https://vuejs.org/error-reference/#runtime-1')).toBe(true)
    expect(isRenderError('https://vuejs.org/error-reference/#runtime-m')).toBe(true)
    expect(isRenderError('https://vuejs.org/error-reference/#runtime-5')).toBe(false) // clique em botão
    expect(isRenderError('component event handler')).toBe(false)
  })
})
