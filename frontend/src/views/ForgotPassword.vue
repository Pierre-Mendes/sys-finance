<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 to-gray-800 p-4">
    <div class="w-full max-w-md bg-card rounded-2xl shadow-2xl overflow-hidden border border-gray-700">
      <div class="p-8">
        <h2 class="text-3xl font-bold text-white mb-2 text-center">Recuperar Conta</h2>
        <p class="text-gray-400 text-center mb-8">Siga as instruções para redefinir sua senha</p>
        
        <!-- Step 1: Email -->
        <form v-if="step === 1" @submit.prevent="getQuestion" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">E-mail da Conta</label>
            <input type="email" v-model="form.email" required 
              class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" 
              placeholder="seu@email.com" />
          </div>
          <button type="submit" :disabled="isSubmitting"
            class="w-full bg-primary hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
            <span>Continuar</span>
          </button>
        </form>

        <!-- Step 2: Answer -->
        <form v-if="step === 2" @submit.prevent="step = 3" class="space-y-6">
          <div class="p-4 bg-gray-800/50 border border-indigo-500/30 rounded-xl mb-4">
             <p class="text-xs text-indigo-400 font-bold uppercase tracking-widest mb-1">Pergunta de Segurança:</p>
             <p class="text-white font-medium">{{ recoveryQuestion }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Sua Resposta</label>
            <input type="text" v-model="form.answer" required autofocus
              class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" 
              placeholder="Digite sua resposta aqui" />
          </div>
          <button type="submit"
            class="w-full bg-primary hover:bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg shadow-lg transition-all flex items-center justify-center gap-2">
            <span>Validar Resposta</span>
          </button>
          <button @click="step = 1" type="button" class="w-full text-gray-500 text-sm hover:text-white transition">Voltar</button>
        </form>

        <!-- Step 3: New Password -->
        <form v-if="step === 3" @submit.prevent="handleReset" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Nova Senha</label>
            <input type="password" v-model="form.newPassword" required 
              class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" 
              placeholder="••••••••" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Confirmar Nova Senha</label>
            <input type="password" v-model="form.confirmPassword" required 
              class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" 
              placeholder="••••••••" />
          </div>
          <button type="submit" :disabled="isSubmitting"
            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg shadow-lg transition-all flex items-center justify-center gap-2">
            <span>Redefinir Senha</span>
          </button>
        </form>
        
        <p class="mt-8 text-center text-sm text-gray-400">
          Lembrou a senha? 
          <router-link to="/" class="text-primary hover:text-blue-400 font-medium transition">
            Voltar ao Login
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'

const router = useRouter()
const step = ref(1)
const isSubmitting = ref(false)
const recoveryQuestion = ref('')

const form = reactive({
  email: '',
  answer: '',
  newPassword: '',
  confirmPassword: ''
})

const getQuestion = async () => {
  isSubmitting.value = true
  try {
    const response = await axios.get('/api/auth/recovery-question', { params: { email: form.email } })
    recoveryQuestion.value = response.data.question
    step.value = 2
  } catch (e: any) {
    toast.error(e.response?.data?.error || 'Erro ao buscar pergunta.')
  } finally {
    isSubmitting.value = false
  }
}

const handleReset = async () => {
  if (form.newPassword !== form.confirmPassword) {
    toast.warning('As senhas não conferem!')
    return
  }

  isSubmitting.value = true
  try {
    await axios.post('/api/auth/reset-password', {
      email: form.email,
      answer: form.answer,
      newPassword: form.newPassword
    })
    toast.success('Senha atualizada com sucesso!')
    setTimeout(() => {
      router.push('/')
    }, 2000)
  } catch (e: any) {
    toast.error(e.response?.data?.error || 'Falha ao redefinir.')
  } finally {
    isSubmitting.value = false
  }
}
</script>
