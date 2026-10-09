<template>
  <div class="relative" ref="root">
    <input
      :id="inputId"
      v-model="query"
      type="text"
      autocomplete="off"
      :placeholder="placeholder"
      :class="inputClass"
      role="combobox"
      :aria-expanded="open"
      @focus="open = true"
      @keydown.down.prevent="move(1)"
      @keydown.up.prevent="move(-1)"
      @keydown.enter.prevent="confirmHighlighted"
      @keydown.esc="open = false"
      @input="onInput"
    />

    <span
      v-if="newName"
      class="absolute right-2 top-1/2 -translate-y-1/2 text-xs font-bold uppercase bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full pointer-events-none"
    >Novo</span>

    <ul
      v-if="open && (filtered.length > 0 || canCreate)"
      class="absolute z-50 mt-1 w-full max-h-56 overflow-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg text-sm"
      role="listbox"
    >
      <li
        v-for="(opt, i) in filtered"
        :key="opt.id"
        role="option"
        :aria-selected="opt.id == modelValue"
        class="px-3 py-2 cursor-pointer text-gray-700 dark:text-gray-200"
        :class="i === highlighted ? 'bg-blue-50 dark:bg-gray-700' : 'hover:bg-gray-50 dark:hover:bg-gray-700'"
        @mousedown.prevent="selectExisting(opt)"
      >{{ opt.name }}</li>
      <li
        v-if="canCreate"
        role="option"
        class="px-3 py-2 cursor-pointer font-semibold text-primary border-t border-gray-100 dark:border-gray-700"
        :class="highlighted === filtered.length ? 'bg-blue-50 dark:bg-gray-700' : 'hover:bg-gray-50 dark:hover:bg-gray-700'"
        @mousedown.prevent="selectNew"
      >+ {{ createLabel }} "{{ query.trim() }}"</li>
    </ul>

    <p v-if="newName" class="text-xs text-emerald-600 mt-1">
      "{{ newName }}" será criado automaticamente ao salvar.
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'

interface Option { id: number | string; name: string }

/**
 * Select com busca que também permite criar um item novo digitando o nome.
 * - v-model: id do item existente selecionado ('' quando nenhum/novo)
 * - v-model:newName: nome do item a ser criado pelo backend ('' quando um existente foi escolhido)
 */
const props = withDefaults(defineProps<{
  modelValue: number | string | null
  newName?: string
  options: Option[]
  placeholder?: string
  createLabel?: string
  inputClass?: string
  inputId?: string
}>(), {
  newName: '',
  placeholder: 'Selecione ou digite para criar...',
  createLabel: 'Criar',
  inputClass: 'w-full border border-gray-300 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-primary bg-white text-gray-700',
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: number | string): void
  (e: 'update:newName', value: string): void
}>()

const root = ref<HTMLElement | null>(null)
const open = ref(false)
const highlighted = ref(0)
const query = ref('')

const normalize = (s: string) => s.trim().toLocaleLowerCase('pt-BR')

const selectedOption = computed(() => props.options.find(o => o.id == props.modelValue) || null)

const filtered = computed(() => {
  const q = normalize(query.value)
  if (!q || (selectedOption.value && normalize(selectedOption.value.name) === q)) return props.options
  return props.options.filter(o => normalize(o.name).includes(q))
})

const exactMatch = computed(() => props.options.find(o => normalize(o.name) === normalize(query.value)) || null)
const canCreate = computed(() => query.value.trim().length > 0 && !exactMatch.value)

const syncQueryFromProps = () => {
  if (props.newName) query.value = props.newName
  else query.value = selectedOption.value?.name ?? ''
}

// Mudanças que nós mesmos emitimos não devem sobrescrever o texto que o usuário está digitando.
let emittingInternally = false
const emitSelection = (id: number | string, name: string) => {
  emittingInternally = true
  emit('update:modelValue', id)
  emit('update:newName', name)
  nextTick(() => { emittingInternally = false })
}

watch(() => [props.modelValue, props.newName, props.options], () => {
  if (!emittingInternally) syncQueryFromProps()
}, { immediate: true })

const selectExisting = (opt: Option) => {
  emitSelection(opt.id, '')
  query.value = opt.name
  open.value = false
}

const selectNew = () => {
  const name = query.value.trim()
  if (!name) return
  emitSelection('', name)
  open.value = false
}

const onInput = () => {
  open.value = true
  highlighted.value = 0
  // Digitar invalida a seleção anterior até o usuário escolher/criar algo.
  emitSelection(exactMatch.value ? exactMatch.value.id : '', '')
}

const move = (delta: number) => {
  open.value = true
  const max = filtered.value.length + (canCreate.value ? 1 : 0)
  if (max === 0) return
  highlighted.value = (highlighted.value + delta + max) % max
}

const confirmHighlighted = () => {
  if (highlighted.value < filtered.value.length) selectExisting(filtered.value[highlighted.value])
  else if (canCreate.value) selectNew()
}

const onClickOutside = (e: MouseEvent) => {
  if (!root.value || root.value.contains(e.target as Node)) return
  open.value = false
  // Texto digitado sem confirmar vira "criar novo" para não perder o que o usuário escreveu.
  if (canCreate.value && !props.newName) selectNew()
  else if (!canCreate.value) syncQueryFromProps()
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
</script>
