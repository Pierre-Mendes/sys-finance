import { describe, it, expect } from 'vitest'
import { escapeHtml } from '../escapeHtml'

describe('escapeHtml', () => {
    it('neutraliza tags e atributos', () => {
        expect(escapeHtml('<img src=x onerror="alert(1)">')).toBe('&lt;img src=x onerror=&quot;alert(1)&quot;&gt;')
    })

    it('mantém texto comum e trata nulos', () => {
        expect(escapeHtml('Alimentação & Mercado')).toBe('Alimentação &amp; Mercado')
        expect(escapeHtml(null)).toBe('')
    })
})
