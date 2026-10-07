import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import CapiMascot from '../CapiMascot.vue'
import BrandLogo from '../BrandLogo.vue'
import EmptyState from '../../ui/EmptyState.vue'

describe('CapiMascot', () => {
  it('é decorativa (aria-hidden) quando não recebe label', () => {
    const svg = mount(CapiMascot).find('svg')
    expect(svg.attributes('aria-hidden')).toBe('true')
    expect(svg.attributes('role')).toBeUndefined()
  })

  it('vira imagem acessível com label', () => {
    const svg = mount(CapiMascot, { props: { label: 'Capi comemorando' } }).find('svg')
    expect(svg.attributes('role')).toBe('img')
    expect(svg.attributes('aria-label')).toBe('Capi comemorando')
    expect(svg.attributes('aria-hidden')).toBeUndefined()
  })

  it('mostra os detalhes de cada humor', () => {
    expect(mount(CapiMascot, { props: { mood: 'celebrating' } }).findAll('.capi__spark')).toHaveLength(3)
    expect(mount(CapiMascot, { props: { mood: 'sleeping' } }).findAll('.capi__z')).toHaveLength(3)
    expect(mount(CapiMascot, { props: { mood: 'alert' } }).find('.capi__sweat').exists()).toBe(true)
    expect(mount(CapiMascot, { props: { mood: 'happy' } }).find('.capi__spark').exists()).toBe(false)
  })

  it('desliga a animação com animated=false', () => {
    expect(mount(CapiMascot, { props: { animated: false } }).classes()).not.toContain('capi--animated')
  })
})

describe('BrandLogo', () => {
  it('mostra o nome por extenso e esconde o símbolo dos leitores de tela', () => {
    const w = mount(BrandLogo)
    expect(w.text()).toBe('sysfinance')
    expect(w.find('svg').attributes('aria-hidden')).toBe('true')
  })

  it('no modo compacto o símbolo carrega o nome acessível', () => {
    const w = mount(BrandLogo, { props: { compact: true } })
    expect(w.text()).toBe('')
    expect(w.find('svg').attributes('aria-label')).toBe('sysfinance')
  })
})

describe('EmptyState', () => {
  it('mostra título, descrição e a ação do slot', () => {
    const w = mount(EmptyState, {
      props: { title: 'Nada por aqui', description: 'Cadastre o primeiro item.' },
      slots: { default: '<button>Adicionar</button>' },
    })
    expect(w.find('h3').text()).toBe('Nada por aqui')
    expect(w.text()).toContain('Cadastre o primeiro item.')
    expect(w.find('button').text()).toBe('Adicionar')
    expect(w.find('[data-testid="capi"]').attributes('data-mood')).toBe('sleeping')
  })
})
