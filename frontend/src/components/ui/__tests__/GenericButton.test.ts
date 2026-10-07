import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import GenericButton from '../GenericButton.vue'

describe('GenericButton', () => {
  it('é type="button" por padrão para não enviar formulários sem querer', () => {
    expect(mount(GenericButton).attributes('type')).toBe('button')
  })

  it('carregando: desabilita, marca aria-busy e mostra o indicador', () => {
    const w = mount(GenericButton, { props: { loading: true }, slots: { default: 'Salvar' } })
    expect(w.attributes('disabled')).toBeDefined()
    expect(w.attributes('aria-busy')).toBe('true')
    expect(w.find('.animate-spin').exists()).toBe(true)
  })

  it('aplica a variante pedida', () => {
    expect(mount(GenericButton, { props: { variant: 'danger' } }).classes()).toContain('bg-expense')
    expect(mount(GenericButton, { props: { variant: 'ghost' } }).classes()).toContain('text-brand-600')
  })
})
