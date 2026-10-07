<template>
  <div class="min-h-screen flex items-center justify-center bg-ink-900 p-4">
    <div class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-ink-700 bg-ink-800 animate-fade-up">
      <div class="p-8">
        <div class="flex justify-center mb-6"><BrandLogo tone="dark" :size="44" /></div>
        <h2 class="text-2xl font-extrabold text-white mb-2 text-center">Criar Conta</h2>
        <p class="text-gray-400 text-center mb-8">Junte-se ao Gerenciador Financeiro</p>
        
        <form @submit.prevent="handleSignup" class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-1">Nome</label>
              <input type="text" v-model="form.firstName" required 
                class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" 
                placeholder="João" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-1">Sobrenome</label>
              <input type="text" v-model="form.lastName" required 
                class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" 
                placeholder="Silva" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">E-mail</label>
            <input type="email" v-model="form.email" required 
              class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" 
              placeholder="joao@email.com" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Senha</label>
            <input type="password" v-model="form.password" required 
              class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" 
              placeholder="••••••••" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Moeda / Currency</label>
            <select v-model="form.currency" class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition">
              <option value="BRL">BRL - Real</option>
              <option value="USD">USD - Dólar</option>
              <option value="EUR">EUR - Euro</option>
            </select>
          </div>

          <div class="space-y-4 pt-2 border-t border-gray-700">
             <p class="text-xs font-bold text-indigo-400 uppercase tracking-widest">Segurança de Recuperação</p>
             <div>
               <label class="block text-sm font-medium text-gray-300 mb-1">Pergunta de Segurança</label>
               <input type="text" v-model="form.securityQuestion" required 
                 list="security-questions"
                 class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" 
                 placeholder="Crie sua própria pergunta" />
               <datalist id="security-questions">
                 <option value="Qual o nome do seu primeiro animal de estimação?" />
                 <option value="Qual a cidade onde você nasceu?" />
                 <option value="Qual o nome da sua mãe?" />
                 <option value="Qual era o nome da sua primeira escola?" />
               </datalist>
             </div>
             <div>
               <label class="block text-sm font-medium text-gray-300 mb-1">Sua Resposta</label>
               <input type="text" v-model="form.securityAnswer" required 
                 class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" 
                 placeholder="Sua resposta secreta" />
             </div>
          </div>
          
          <button type="submit" 
            class="w-full bg-primary hover:bg-brand-800 text-white font-semibold py-3 px-4 rounded-lg shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5 mt-2">
            Cadastrar
          </button>
        </form>
        
        <p class="mt-8 text-center text-sm text-gray-400">
          Já tem uma conta? 
          <router-link to="/" class="text-brand-400 hover:text-brand-100 font-medium transition">
            Entrar
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue'
import api from '@/data/api/HttpClient'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import BrandLogo from '@/components/brand/BrandLogo.vue'

const router = useRouter()
const form = reactive({
  firstName: '',
  lastName: '',
  email: '',
  password: '',
  currency: 'BRL',
  securityQuestion: '',
  securityAnswer: ''
})

const handleSignup = async () => {
    try {
        const response = await api.post('/api/auth/signup', form);
        
        if (response.data.success) {
            toast.success('Conta criada com sucesso!');
            router.push('/');
        }
    } catch (e: any) {
        toast.error('Erro ao criar conta: ' + (e.response?.data?.error || e.message))
    }
}
</script>
