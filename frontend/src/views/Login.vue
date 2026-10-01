<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 to-gray-800 p-4">
    <div class="w-full max-w-md bg-card rounded-2xl shadow-2xl overflow-hidden border border-gray-700">
      <div class="p-8">
        <h2 class="text-3xl font-bold text-white mb-2 text-center">Gerenciador Financeiro</h2>
        <p class="text-gray-400 text-center mb-8">Faça login para continuar</p>
        
        <form @submit.prevent="handleLogin" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">E-mail</label>
            <input type="email" v-model="email" required 
              class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" 
              placeholder="seu@email.com" />
          </div>
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-sm font-medium text-gray-300">Senha</label>
              <router-link to="/forgot-password" class="text-xs text-primary hover:text-blue-400 transition">
                Esqueci minha senha
              </router-link>
            </div>
            <input type="password" v-model="password" required 
              class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" 
              placeholder="••••••••" />
          </div>
          
          <button type="submit" :disabled="isSubmitting"
            class="w-full bg-primary hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
            <svg v-if="isSubmitting" class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            {{ isSubmitting ? 'Verificando...' : 'Entrar' }}
          </button>
        </form>
        
        <p class="mt-8 text-center text-sm text-gray-400">
          Ainda não tem conta? 
          <router-link to="/signup" class="text-primary hover:text-blue-400 font-medium transition">
            Cadastre-se
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import api from '@/data/api/HttpClient'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

const email = ref('')
const password = ref('')
const router = useRouter()

const isSubmitting = ref(false)

const handleLogin = async () => {
    isSubmitting.value = true

    try {
        const response = await api.post('/api/auth/login', {
            email: email.value,
            password: password.value
        });
        
        if (response.data.success) {
            toast.success('Login efetuado com sucesso!');
            // A sessão fica num cookie HttpOnly definido pela API; aqui só guardamos dados de exibição.
            localStorage.removeItem('token');
            localStorage.setItem('user', JSON.stringify(response.data.user));
            
            // Aguarda o toast de sucesso aparecer e muda de tela
            setTimeout(() => {
                router.push('/dashboard');
            }, 1200);
        }
    } catch (e: any) {
        isSubmitting.value = false
        toast.error('Erro: ' + (e.response?.data?.error || e.message))
    }
}
</script>
