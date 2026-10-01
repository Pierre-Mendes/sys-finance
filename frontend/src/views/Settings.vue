<template>
  <MainLayout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Configurações e Perfil</h2>
        <p class="text-gray-500 mt-1 sm:text-lg">Gerencie as informações da sua conta e preferências financeiras.</p>
    </div>

    <!-- Loader Overlay -->
    <div v-if="isLoadingData" class="py-12 flex flex-col items-center justify-center text-primary">
        <svg class="w-10 h-10 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span class="mt-4 font-semibold tracking-widest text-sm text-gray-500 animate-pulse">LENDO PREFERÊNCIAS...</span>
    </div>

    <div v-else class="bg-card w-full max-w-4xl rounded-2xl shadow-lg border border-gray-100 overflow-hidden text-gray-800 relative shadow-gray-200">
        
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
             <div class="w-12 h-12 bg-blue-100 text-primary rounded-full flex items-center justify-center font-bold text-xl shadow-sm">
                {{ form.firstName.charAt(0).toUpperCase() }}
             </div>
             <div>
                <h3 class="font-semibold text-lg">{{ form.firstName }} {{ form.lastName }}</h3>
                <h4 class="text-sm text-gray-500">{{ form.email }}</h4>
             </div>
             <div class="ml-auto text-right hidden sm:block">
                 <span class="text-xs font-bold text-gray-400 uppercase tracking-widest block mb-1">Cód. Corporativo Pessoal</span>
                 <button @click="copyUserCode" class="font-mono font-bold text-indigo-600 bg-indigo-50 border border-indigo-100 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition shadow-sm flex items-center gap-2 group w-max ml-auto">
                     {{ form.userCode || 'Gerando...' }}
                     <svg class="w-4 h-4 opacity-50 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                 </button>
             </div>
        </div>

        <form @submit.prevent="updateProfile" class="p-6 md:p-8 space-y-6 bg-white">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nome -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Primeiro Nome</label>
                    <input v-model="form.firstName" type="text" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition shadow-sm" placeholder="Seu nome" />
                </div>
                <!-- Sobrenome -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Sobrenome</label>
                    <input v-model="form.lastName" type="text" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition shadow-sm" placeholder="Seu sobrenome" />
                </div>
                
                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">E-mail de Acesso</label>
                    <input v-model="form.email" type="email" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition shadow-sm" placeholder="contato@site.com" />
                </div>

                <!-- Currency -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Moeda Base (Símbolo)</label>
                    <select v-model="form.currency" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition shadow-sm font-medium">
                        <option value="R$">Real (R$)</option>
                        <option value="$">US Dollar ($)</option>
                        <option value="€">Euro (€)</option>
                        <option value="£">Pound (£)</option>
                        <option value="¥">Yen (¥)</option>
                        <option value="₽">Ruble (₽)</option>
                    </select>
                </div>
            </div>

            <hr class="border-gray-200 my-6">

            <div class="mb-4">
                <h4 class="font-bold text-gray-800 mb-1 italic text-indigo-600">Recuperação de Conta</h4>
                <p class="text-sm text-gray-500">Configure uma pergunta de segurança para recuperar sua senha caso a esqueça.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pergunta de Segurança</label>
                    <input v-model="form.securityQuestion" type="text" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition shadow-sm" placeholder="Ex: Qual o nome do meu primeiro pet?" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Resposta de Segurança</label>
                    <input v-model="form.securityAnswer" type="password" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition shadow-sm" placeholder="Sua resposta secreta" />
                    <p class="text-[10px] text-gray-400 mt-1">Preencha apenas se quiser alterar a resposta atual.</p>
                </div>
            </div>

            <hr class="border-gray-200 my-6">

            <div class="mb-4">
                <h4 class="font-bold text-gray-800 mb-1">Segurança e Senha</h4>
                <p class="text-sm text-gray-500 transition-all" :class="form.password ? 'text-primary' : 'text-gray-500'">Deixe os campos em branco se não quiser alterar a sua senha atual.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nova Senha</label>
                    <input v-model="form.password" type="password" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition shadow-sm" placeholder="••••••••" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Confirmar Nova Senha</label>
                    <input v-model="form.rpassword" type="password" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition shadow-sm" placeholder="••••••••" />
                </div>
            </div>

            <div class="flex justify-end pt-4 mt-6">
                 <button type="submit" :disabled="isSubmitting" class="bg-primary hover:bg-blue-600 active:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3 px-8 rounded-lg shadow-md transition-all flex items-center justify-center gap-3">
                     <svg v-if="isSubmitting" class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                     <span v-else>Salvar Alterações</span>
                 </button>
            </div>
        </form>

    </div>

    <ReminderSettings v-if="!isLoadingData" />
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import MainLayout from '@/components/layout/MainLayout.vue'
import ReminderSettings from '@/presentation/components/domain/ReminderSettings.vue'
import { useRouter } from 'vue-router'
import api from '@/data/api/HttpClient'

const router = useRouter()
const isLoadingData = ref(true)
const isSubmitting = ref(false)

const form = ref({
    firstName: '',
    lastName: '',
    email: '',
    currency: 'R$',
    password: '',
    rpassword: '',
    userCode: '',
    securityQuestion: '',
    securityAnswer: ''
})

onMounted(async () => {
    isLoadingData.value = true;
    try {
        const response = await api.get('/api/auth/me')
        const u = response.data.data
        form.value.firstName = u.firstName
        form.value.lastName = u.lastName
        form.value.email = u.email
        form.value.currency = u.currency || 'R$'
        form.value.userCode = u.userCode
        form.value.securityQuestion = u.securityQuestion || ''
    } catch (e: any) {
        if (e.response?.status === 401) { 
            toast.error('Sessão expirada.'); router.push('/'); 
        } else {
            toast.error('Geral erro')
        }
    } finally {
        isLoadingData.value = false;
    }
})

const updateProfile = async () => {
    if (form.value.password !== form.value.rpassword) {
        toast.warning('As senhas não conferem!');
        return;
    }

    isSubmitting.value = true;
    try {
        await api.put('/api/auth/profile', {
            firstName: form.value.firstName,
            lastName: form.value.lastName,
            email: form.value.email,
            currency: form.value.currency,
            password: form.value.password || undefined,
            securityQuestion: form.value.securityQuestion || undefined,
            securityAnswer: form.value.securityAnswer || undefined
        })

        toast.success("Perfil sincronizado com sucesso!")
        
        // Update local session
        let u: any = localStorage.getItem('user');
        if (u) {
            u = JSON.parse(u);
            u.firstName = form.value.firstName;
            localStorage.setItem('user', JSON.stringify(u));
        }

        form.value.password = ''
        form.value.rpassword = ''

    } catch (e: any) {
        toast.error(e.response?.data?.error || 'Erro ao salvar configurações.')
    } finally {
        isSubmitting.value = false;
    }
}

const copyUserCode = () => {
    if(!form.value.userCode) return;
    navigator.clipboard.writeText(form.value.userCode);
    toast.success('Seu ID Corporativo '+form.value.userCode+' copiado!');
}
</script>
