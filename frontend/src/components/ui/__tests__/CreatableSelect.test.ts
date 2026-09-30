import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { defineComponent, ref } from 'vue'
import CreatableSelect from '../CreatableSelect.vue'

const options = [
  { id: 1, name: 'Carteira' },
  { id: 2, name: 'Nubank' },
]

const mountWithModel = (initialId: number | string = '', initialName = '') => {
  const id = ref<number | string>(initialId)
  const name = ref(initialName)
  const Host = defineComponent({
    components: { CreatableSelect },
    setup: () => ({ id, name, options }),
    template: `<CreatableSelect v-model="id" v-model:new-name="name" :options="options" />`,
  })
  return { wrapper: mount(Host, { attachTo: document.body }), id, name }
}

describe('CreatableSelect', () => {
  it('mostra o nome do item selecionado', () => {
    const { wrapper } = mountWithModel(2)
    expect((wrapper.find('input').element as HTMLInputElement).value).toBe('Nubank')
  })

  it('seleciona automaticamente um existente ao digitar o nome exato (sem diferenciar maiúsculas)', async () => {
    const { wrapper, id, name } = mountWithModel()
    await wrapper.find('input').setValue('carteira')
    expect(id.value).toBe(1)
    expect(name.value).toBe('')
  })

  it('oferece criar um item novo quando o nome não existe', async () => {
    const { wrapper, id, name } = mountWithModel()
    const input = wrapper.find('input')
    await input.setValue('Inter')

    const createOption = wrapper.findAll('li').find(li => li.text().includes('Criar'))
    expect(createOption).toBeTruthy()
    await createOption!.trigger('mousedown')

    expect(id.value).toBe('')
    expect(name.value).toBe('Inter')
    expect((input.element as HTMLInputElement).value).toBe('Inter')
    expect(wrapper.text()).toContain('será criado automaticamente')
  })

  it('mantém o texto digitado enquanto o usuário ainda não escolheu', async () => {
    const { wrapper } = mountWithModel(1)
    const input = wrapper.find('input')
    await input.setValue('Nu')
    expect((input.element as HTMLInputElement).value).toBe('Nu')
    expect(wrapper.findAll('li').map(li => li.text())).toContain('Nubank')
  })
})
