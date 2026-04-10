<template>
  <MainLayout>
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h2 class="text-2xl sm:text-3xl font-semibold text-gray-800">Categorias</h2>
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
          <div class="relative w-full sm:w-64">
             <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                 <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
             </div>
             <input v-model="searchQuery" type="text" placeholder="Pesquisar categoria..." class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-700 bg-white shadow-sm transition" />
             
             <!-- Clear search button -->
             <div v-if="searchQuery" @click="searchQuery = ''" class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-red-500 transition" title="Limpar busca">
                 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
             </div>
          </div>
          <button @click="openCreateModal" class="w-full sm:w-auto bg-primary hover:bg-blue-600 px-5 py-2.5 rounded-lg text-white font-medium shadow transition cursor-pointer flex items-center justify-center gap-2 whitespace-nowrap">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>Nova Categoria
          </button>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="flex space-x-4 mb-6 border-b border-gray-200">
        <button @click="switchTab('expense')" :class="['py-2 px-4 font-medium transition cursor-pointer border-b-2 outline-none', activeType === 'expense' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700']">De Despesas</button>
        <button @click="switchTab('income')" :class="['py-2 px-4 font-medium transition cursor-pointer border-b-2 outline-none', activeType === 'income' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700']">De Receitas</button>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden overflow-x-auto w-full">
        <table class="w-full text-left min-w-[500px]">
          <thead class="bg-gray-50 text-gray-600 text-sm font-semibold uppercase border-b border-gray-100">
            <tr>
              <th class="p-4 cursor-pointer hover:bg-gray-100 transition group select-none" @click="toggleSort">
                  <div class="flex items-center gap-2">
                      Nome da Categoria
                      <svg v-if="sortOrder === 'asc'" class="w-4 h-4 text-gray-400 group-hover:text-primary transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                      <svg v-else class="w-4 h-4 text-gray-400 group-hover:text-primary transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                  </div>
              </th>
              <th class="p-4 w-40 text-right sm:text-center">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cat in paginatedCategories" :key="cat.id" class="border-b border-gray-50 hover:bg-gray-50 transition">
              <td class="p-4 text-gray-800 font-medium">
                  <span :class="['inline-block w-2- h-2 rounded-full mr-2', activeType === 'expense' ? 'bg-red-500' : 'bg-green-500']"></span>
                  {{ cat.name }}
              </td>
              <td class="p-4 text-right sm:text-center whitespace-nowrap">
                <div class="flex gap-4 justify-end sm:justify-center items-center">
                    <button @click="openEditModal(cat)" class="text-blue-500 hover:text-blue-700 transition cursor-pointer font-medium flex items-center gap-1.5" title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        <span class="hidden sm:inline text-sm">Editar</span>
                    </button>
                    <button @click="deleteCategory(cat)" class="text-red-500 hover:text-red-700 transition cursor-pointer font-medium flex items-center gap-1.5" title="Excluir">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        <span class="hidden sm:inline text-sm">Excluir</span>
                    </button>
                </div>
              </td>
            </tr>
            <TableLoader v-if="isLoading" :columns="2" message="CARREGANDO CATEGORIAS..." />
            <tr v-if="paginatedCategories.length === 0 && !isLoading">
              <td colspan="2" class="p-8 text-center text-gray-500">Nenhuma categoria encontrada de {{ activeType === 'expense' ? 'Despesa' : 'Receita' }}.</td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="p-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between text-sm text-gray-500 bg-gray-50/50 gap-4">
          <div class="flex flex-col sm:flex-row items-center gap-3">
            <span>Mostrando {{ filteredCategories.length === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1 }} a {{ Math.min(currentPage * itemsPerPage, filteredCategories.length) }} de {{ filteredCategories.length }} registros</span>
            <div class="h-4 w-px bg-gray-300 hidden sm:block"></div>
            <select v-model="itemsPerPage" class="border border-gray-200 rounded px-2 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer text-gray-600 shadow-sm">
               <option :value="5">5 / pág</option>
               <option :value="10">10 / pág</option>
               <option :value="15">15 / pág</option>
            </select>
          </div>
          <div class="flex items-center gap-4">
            <button @click="prevPage" :disabled="currentPage === 1" class="px-4 py-1.5 rounded-md border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition font-medium text-gray-600 shadow-sm">Anterior</button>
            <span class="font-medium text-gray-700">Pág {{ currentPage }} de {{ totalPages }}</span>
            <button @click="nextPage" :disabled="currentPage === totalPages" class="px-4 py-1.5 rounded-md border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition font-medium text-gray-600 shadow-sm">Próxima</button>
          </div>
        </div>
      </div>

    <!-- Modal Adicionar/Editar Categoria -->
    <div v-if="openModal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl">
        <h3 class="text-xl font-bold text-gray-800 mb-4">{{ editingCategoryId ? 'Editar Categoria' : 'Adicionar Categoria' }}</h3>
        <form @submit.prevent="saveCategory">
          <label class="block text-sm font-medium text-gray-600 mb-1">Nome da(s) Categoria(s) ({{ activeType === 'expense' ? 'Despesa' : 'Receita' }})</label>
          
          <!-- Modo Edição (Single) -->
          <input v-if="editingCategoryId" v-model="newCategoryName" type="text" class="w-full border border-gray-300 rounded-lg p-2.5 mb-5 focus:outline-none focus:ring-2 focus:ring-primary text-gray-900" required placeholder="Ex: Alimentação" />
          
          <!-- Modo Criação (Bulk/Tags) -->
          <div v-else class="w-full border border-gray-300 rounded-lg p-2.5 mb-5 focus-within:ring-2 focus-within:ring-primary focus-within:border-primary bg-white transition-shadow flex flex-wrap gap-2 items-center cursor-text" @click="tagInput?.focus()">
            <!-- Display Tags -->
            <span v-for="(tag, index) in categoryTags" :key="index" class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-1 rounded-md flex items-center gap-1.5 break-all">
              {{ tag }}
              <button type="button" @click.stop="removeTag(index)" class="hover:bg-blue-200 text-blue-600 rounded-full p-0.5 transition cursor-pointer" title="Remover">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              </button>
            </span>
            
            <!-- Input wrapper to handle sizing and key events -->
            <input 
              ref="tagInput"
              v-model="tagInputValue" 
              @keydown="handleTagKeydown"
              @blur="addTagFromInput"
               type="text" 
              class="flex-1 min-w-[120px] bg-transparent outline-none text-gray-900 placeholder-gray-400 text-sm h-6 m-0.5" 
              :placeholder="categoryTags.length === 0 ? 'Ex: Uber, Mercado (separe com vírgula ou Enter)' : ''" 
            />
          </div>
          <div class="flex justify-end gap-3">
            <button type="button" @click="openModal = false" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition cursor-pointer font-medium">Cancelar</button>
            <button type="submit" class="px-4 py-2 bg-primary hover:bg-blue-600 text-white font-medium rounded-lg shadow transition cursor-pointer">Salvar</button>
          </div>
        </form>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { toast } from 'vue3-toastify'
import Swal from 'sweetalert2'
import MainLayout from '@/components/layout/MainLayout.vue'
import TableLoader from '@/components/ui/TableLoader.vue'

const router = useRouter()
const categories = ref<any[]>([])
const isLoading = ref(true)
const activeType = ref('expense') // 'expense' or 'income'

const openModal = ref(false)
const newCategoryName = ref('')
const tagInputValue = ref('')
const categoryTags = ref<string[]>([])
const tagInput = ref<HTMLInputElement | null>(null)
const editingCategoryId = ref<number | null>(null)
const searchQuery = ref('')
const currentPage = ref(1)
const itemsPerPage = ref(5)
const sortOrder = ref('asc')

const switchTab = (type: string) => {
    activeType.value = type;
    currentPage.value = 1;
    searchQuery.value = '';
    fetchCategories();
}

const filteredCategories = computed(() => {
    let result = categories.value.slice()
    if (searchQuery.value) {
        const lower = searchQuery.value.toLowerCase()
        result = result.filter(c => c.name.toLowerCase().includes(lower))
    }
    result.sort((a, b) => {
        if (sortOrder.value === 'asc') return a.name.localeCompare(b.name)
        return b.name.localeCompare(a.name)
    })
    return result
})

const toggleSort = () => {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
}

const totalPages = computed(() => {
    return Math.ceil(filteredCategories.value.length / itemsPerPage.value) || 1
})

const paginatedCategories = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value
    const end = start + itemsPerPage.value
    return filteredCategories.value.slice(start, end)
})

watch([searchQuery, itemsPerPage, activeType, sortOrder], () => {
    currentPage.value = 1
})

watch(openModal, (isOpen) => {
    if (isOpen && !editingCategoryId.value) {
        // Use nextTick via a microtask alternative or just wait for render
        setTimeout(() => {
            tagInput.value?.focus();
        }, 0);
    }
}, { flush: 'post' })

const nextPage = () => {
    if (currentPage.value < totalPages.value) currentPage.value++
}
const prevPage = () => {
    if (currentPage.value > 1) currentPage.value--
}

const fetchCategories = async () => {
    isLoading.value = true;
    try {
        const token = localStorage.getItem('token')
        const response = await axios.get('/api/categories?type=' + activeType.value, { headers: { Authorization: `Bearer ${token}` }})
        categories.value = response.data.data
    } catch (e: any) {
        if (e.response?.status === 401) { 
            toast.error('Sessão expirada.');
            router.push('/'); 
            return;
        }
        toast.error('Erro ao listar categorias.')
    } finally {
        isLoading.value = false;
    }
}

onMounted(fetchCategories)

const openCreateModal = () => {
    editingCategoryId.value = null;
    newCategoryName.value = '';
    tagInputValue.value = '';
    categoryTags.value = [];
    openModal.value = true;
}

const openEditModal = (cat: any) => {
    editingCategoryId.value = cat.id;
    newCategoryName.value = cat.name;
    openModal.value = true;
}

const addTagFromInput = () => {
    const val = tagInputValue.value.trim();
    if (!val) return;

    // Split by common delimiters (comma, newline)
    const values = val
        .split(/[,\n\r]+/)
        .map(v => v.trim())
        .filter(v => v !== '');

    values.forEach(v => {
        // Prevent exact duplicates in the UI
        if (!categoryTags.value.includes(v)) {
            categoryTags.value.push(v);
        }
    });

    tagInputValue.value = '';
}

const handleTagKeydown = (e: KeyboardEvent) => {
    if (e.key === ',' || e.key === 'Enter') {
        e.preventDefault();
        addTagFromInput();
    } else if (e.key === 'Backspace' && tagInputValue.value === '' && categoryTags.value.length > 0) {
        // Remove last tag if backspace pressed on empty input
        categoryTags.value.pop();
    }
}

const removeTag = (index: number) => {
    categoryTags.value.splice(index, 1);
}

const saveCategory = async () => {
    try {
        const token = localStorage.getItem('token')
        if (editingCategoryId.value) {
            await axios.put(`/api/categories/${editingCategoryId.value}`, { name: newCategoryName.value }, { headers: { Authorization: `Bearer ${token}` }})
            toast.success('Categoria atualizada!')
        } else {
            // Process tags + any pending input
            addTagFromInput(); 
            
            if (categoryTags.value.length === 0) {
                toast.warning('Adicione pelo menos uma categoria.');
                if(tagInput.value) tagInput.value.focus();
                return;
            }
            
            const payloadArray = categoryTags.value; // Send array directly

            const response = await axios.post('/api/categories', { name: payloadArray, type: activeType.value }, { headers: { Authorization: `Bearer ${token}` }})
            toast.success(response.data.message || 'Categoria(s) adicionada(s)!')
        }
        openModal.value = false
        fetchCategories()
    } catch (e: any) {
        toast.error(e.response?.data?.error || 'Erro ao salvar categoria.')
    }
}

const deleteCategory = async (cat: any) => {
    const result = await Swal.fire({
        title: 'Você tem certeza?',
        text: `Deseja realmente excluir a categoria "${cat.name}"? Isso pode afetar os lançamentos associados.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    });
    if (!result.isConfirmed) return;

    try {
        const token = localStorage.getItem('token')
        await axios.delete(`/api/categories/${cat.id}`, { headers: { Authorization: `Bearer ${token}` }})
        toast.success('Categoria removida.')
        fetchCategories()
    } catch (e: any) {
        toast.error('Erro ao excluir.')
    }
}
</script>
