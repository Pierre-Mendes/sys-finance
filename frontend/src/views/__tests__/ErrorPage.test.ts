import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import ErrorPage from '../ErrorPage.vue'

const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/:p(.*)*', component: { template: '<div/>' } }] })

describe('ErrorPage', () => {
  it('500 mostra a referência e o botão de tentar novamente', async () => {
    const w = mount(ErrorPage, { props: { code: 500, reference: '1a2b3c4d' }, global: { plugins: [router] } })
    expect(w.text()).toContain('Algo deu errado')
    expect(w.text()).toContain('1a2b3c4d')
    expect(w.text()).toContain('Tentar novamente')
  })

  it('404 é o padrão e oferece voltar ao início', () => {
    const w = mount(ErrorPage, { global: { plugins: [router] } })
    expect(w.text()).toContain('Página não encontrada')
    expect(w.text()).toContain('Ir para o início')
    expect(w.text()).not.toContain('Tentar novamente')
  })

  it('403 sugere trocar de espaço', () => {
    const w = mount(ErrorPage, { props: { code: 403 }, global: { plugins: [router] } })
    expect(w.text()).toContain('Acesso não permitido')
    expect(w.text()).toContain('Trocar de espaço')
  })
})
