<template>
  <div class="min-h-screen bg-ink-50 flex">
    <!-- Overlay for mobile sidebar -->
    <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-gray-900/50 z-40 lg:hidden"></div>

    <!-- Sidebar -->
    <aside 
      :class="[
        'fixed lg:sticky top-0 h-screen bg-card text-white flex flex-col transition-all duration-300 z-50',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        isCollapsed ? 'w-20' : 'w-64'
      ]"
    >
      <div class="h-16 flex items-center justify-between px-4 border-b border-ink-700">
        <router-link to="/dashboard" class="rounded-lg" :class="{ 'mx-auto': isCollapsed }" :aria-label="`${BRAND_NAME}: painel inicial`">
          <BrandLogo tone="dark" :compact="isCollapsed" :size="34" />
        </router-link>
        <button @click="sidebarOpen = false" class="lg:hidden p-1 text-gray-400 hover:text-white cursor-pointer">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>
      
      <!-- User profile summary area -->
      <div v-if="!isCollapsed" class="p-5 border-b border-ink-700 text-center relative">
        
        <!-- Alertas / System Invites Dropdown Toggle (Desktop) -->
        <button @click="invitesModalOpen = true" class="absolute top-4 right-4 text-gray-400 hover:text-white transition group focus:outline-none" title="Notificações do Sistema">
            <svg class="w-6 h-6 transform group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <div v-if="sysInvites?.length > 0 || unreadCount > 0" class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white ring-2 ring-card">
                {{ sysInvites.length + unreadCount }}
            </div>
        </button>

        <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-2 text-white shadow-md cursor-pointer">
           {{ userName.charAt(0).toUpperCase() }}
        </div>
        <p class="font-medium truncate text-gray-100 text-lg">{{ userName }}</p>
        <p class="text-xs text-gray-400">Usuário Autenticado</p>
      </div>

      <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-4" aria-label="Menu principal">
        <div v-for="group in NAV_GROUPS" :key="group.title || 'inicio'">
          <p v-if="group.title && !isCollapsed" class="px-3 pb-1 text-xs font-extrabold uppercase tracking-wider text-[#9AA6BA]">{{ group.title }}</p>
          <div v-else-if="group.title" class="mx-3 mb-2 border-t border-ink-700" aria-hidden="true"></div>
          <ul class="space-y-0.5">
            <li v-for="item in group.items" :key="item.to">
              <router-link
                :to="item.to"
                :title="isCollapsed ? item.label : undefined"
                class="flex items-center gap-3 px-3 min-h-10 rounded-lg text-sm font-semibold text-ink-200 transition-colors duration-150 hover:bg-ink-800 hover:text-white"
                :class="{ 'justify-center': isCollapsed }"
                active-class="!bg-brand-600 !text-white"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :stroke-width="item.strokeWidth ?? 1.75" :d="item.icon"></path></svg>
                <span v-if="!isCollapsed">{{ item.label }}</span>
              </router-link>
            </li>
          </ul>
        </div>
      </nav>

      <div class="p-4 border-t border-ink-700 flex flex-col gap-3">
         <ThemeToggle :compact="isCollapsed" />
         <button @click="isCollapsed = !isCollapsed" class="hidden lg:flex w-full items-center justify-center p-2 bg-ink-800 hover:bg-ink-700 rounded-lg text-gray-400 hover:text-white transition cursor-pointer">
            <svg v-if="!isCollapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
         </button>
         <button @click="logout" class="flex items-center justify-center gap-3 p-3 bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white rounded-lg transition cursor-pointer w-full font-medium shadow">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            <span v-if="!isCollapsed">Encerrar Sessão</span>
         </button>
      </div>
    </aside>

    <!-- Main Content wrapper -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Mobile Header -->
      <header class="lg:hidden h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sticky top-0 z-30 shadow-sm">
         <div class="flex items-center gap-3">
             <button @click="sidebarOpen = true" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg cursor-pointer transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
             </button>
             <router-link to="/dashboard" :aria-label="`${BRAND_NAME}: painel inicial`"><BrandLogo :size="30" /></router-link>
         </div>
         <button @click="invitesModalOpen = true" class="p-2 text-gray-500 hover:text-indigo-600 transition relative focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <div v-if="sysInvites?.length > 0 || unreadCount > 0" class="absolute top-0 right-0 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white ring-2 ring-white">
                {{ sysInvites.length + unreadCount }}
            </div>
        </button>
      </header>
      
      <!-- pb extra no celular para o conteúdo não ficar atrás da barra inferior -->
      <main class="flex-1 p-4 pb-28 sm:p-6 sm:pb-28 lg:p-8 w-full max-w-7xl mx-auto overflow-x-hidden">
        <slot />
      </main>
    </div>

    <!-- Barra inferior (celular): atalhos do dia a dia + botão de lançamento rápido -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t border-gray-200 shadow-[0_-2px_10px_rgba(0,0,0,0.05)] pb-[env(safe-area-inset-bottom)]" aria-label="Navegação principal">
      <ul class="grid grid-cols-5 h-16 text-[11px] font-medium text-gray-500">
        <li>
          <router-link to="/dashboard" class="h-full flex flex-col items-center justify-center gap-1" active-class="text-primary">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Início
          </router-link>
        </li>
        <li>
          <router-link to="/transactions" class="h-full flex flex-col items-center justify-center gap-1" active-class="text-primary">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
            Lançamentos
          </router-link>
        </li>
        <li class="flex items-start justify-center">
          <router-link :to="{ path: '/transactions', query: { new: '1' } }" class="-mt-5 w-14 h-14 rounded-full bg-primary text-white shadow-lg flex items-center justify-center active:scale-95 transition" aria-label="Novo lançamento">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14m-7-7h14"></path></svg>
          </router-link>
        </li>
        <li>
          <router-link to="/credit-cards" class="h-full flex flex-col items-center justify-center gap-1" active-class="text-primary">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
            Cartões
          </router-link>
        </li>
        <li>
          <button type="button" @click="sidebarOpen = true" class="w-full h-full flex flex-col items-center justify-center gap-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            Mais
          </button>
        </li>
      </ul>
    </nav>

    <!-- System Invites Notifications Overlay Modal -->
    <div v-if="invitesModalOpen" class="fixed inset-0 bg-gray-900/40 z-[60] flex items-start justify-center pt-20 px-4 sm:pt-24 backdrop-blur-sm" @click.self="invitesModalOpen = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in-down border border-gray-100 flex flex-col max-h-[80vh]">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    Notificações
                </h3>
                <button @click="invitesModalOpen = false" class="p-1 hover:bg-gray-200 rounded-lg transition-colors text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="px-4 pt-4 bg-gray-50/50">
                <div v-if="generalNotifications.length > 0" class="flex items-center justify-between bg-white p-1.5 rounded-xl border border-gray-200 shadow-sm">
                    <button @click="readAllNotifications" class="flex-1 flex items-center justify-center gap-1.5 text-xs uppercase font-extrabold text-indigo-700 hover:bg-indigo-50 rounded-lg py-2 transition-all">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Lidas
                    </button>
                    <div class="w-px h-4 bg-gray-100 mx-1"></div>
                    <button @click="deleteAllNotifications" class="flex-1 flex items-center justify-center gap-1.5 text-xs uppercase font-extrabold text-red-600 hover:bg-red-50 rounded-lg py-2 transition-all">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Limpar
                    </button>
                </div>
            </div>
            
            <div class="overflow-y-auto p-4 flex-1 bg-gray-50">
                <EmptyState v-if="sysInvites?.length === 0 && generalNotifications?.length === 0" title="Tudo em dia" description="Nenhum alerta ou convite na sua caixa de entrada." :size="96" />
                
                <h4 v-if="sysInvites?.length > 0" class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 mt-1">Convites Pendentes</h4>
                <div v-for="inv in sysInvites" :key="'inv'+inv.id" class="bg-white border border-gray-200 rounded-lg p-4 mb-3 shadow-sm hover:shadow transition">
                    <p class="text-sm text-gray-800 mb-2 font-medium"><span class="font-bold text-indigo-600">{{ inv.senderFirstName }}</span> convidou você para o espaço <span class="font-bold text-gray-900 border-b border-indigo-200">{{ inv.workspaceName }}</span>.</p>
                    <p class="text-xs text-gray-500 mb-3 block text-right">{{ new Date(inv.createdAt).toLocaleDateString() }} ás {{ new Date(inv.createdAt).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</p>
                    <div class="flex justify-end gap-2 text-sm mt-2">
                         <button @click="resolveInvite(inv.id, 'reject')" class="px-3 py-1.5 text-red-600 hover:bg-red-50 rounded-lg border border-transparent font-medium transition">Recusar</button>
                         <button @click="resolveInvite(inv.id, 'accept')" class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white rounded-lg font-bold shadow-sm transition disabled:opacity-50" :disabled="isResolving">Aceitar Visão</button>
                    </div>
                </div>

                <h4 v-if="generalNotifications?.length > 0" class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 mt-4 block">Alertas</h4>
                <TransitionGroup name="list" tag="div" class="space-y-3">
                    <div v-for="notif in generalNotifications" :key="'notif'+notif.id" :class="['border rounded-xl p-4 transition-all duration-300 relative group', !notif.read_at ? 'bg-indigo-50/40 border-indigo-100' : 'bg-white border-gray-100']">
                        <div v-if="!notif.read_at" class="w-3 h-3 bg-indigo-500 absolute -top-1 -right-1 rounded-full shadow-sm animate-pulse border-2 border-white z-10"></div>
                        
                        <!-- Individual Delete Button -->
                        <button @click.stop="deleteNotification(notif.id)" class="absolute top-2 right-2 p-1 text-gray-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100" title="Excluir">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                        
                        <div class="flex gap-3">
                            <div class="mt-1">
                                <span v-if="notif.type === 'SLA_WARNING'" class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center text-red-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </span>
                                <span v-else class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </span>
                            </div>
                            <div class="flex-1">
                                <h5 :class="['text-sm font-bold', !notif.read_at ? 'text-gray-900 line-clamp-1' : 'text-gray-600 line-clamp-1']">{{ notif.title }}</h5>
                                <p :class="['text-xs mt-1 leading-relaxed', !notif.read_at ? 'text-gray-800' : 'text-gray-500']">{{ notif.message }}</p>
                                
                                <div v-if="notif.action_url" class="mt-3">
                                    <button @click="navigateToAction(notif.action_url, notif.id)" class="w-full text-[11px] font-bold uppercase tracking-wider bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-md hover:bg-indigo-700 hover:shadow-lg transition-all flex items-center justify-center gap-2 active:scale-95">
                                        {{ notif.type === 'SLA_WARNING' ? 'Ver contas' : 'Configurar Agora' }}
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                    </button>
                                </div>

                                <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-100/50">
                                    <span class="text-xs text-gray-500 font-medium">{{ new Date(notif.created_at).toLocaleDateString([], {day:'2-digit', month:'short'}) }}</span>
                                    <button v-if="!notif.read_at" @click="readNotification(notif.id)" class="text-xs font-bold text-gray-500 hover:text-indigo-600 transition tracking-wider uppercase">Lido</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </TransitionGroup>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { computed } from 'vue'
import api, { logout as apiLogout } from '@/data/api/HttpClient'
import { toast } from 'vue3-toastify'
import Swal from 'sweetalert2'
import BrandLogo from '@/components/brand/BrandLogo.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'
import { BRAND_NAME } from '@/core/domain/brand'
import { NAV_GROUPS } from './navigation'

const router = useRouter()
const sidebarOpen = ref(false)
// Cada tela monta o próprio MainLayout: remove o hook ao desmontar para não acumular.
const removeCloseOnNavigate = router.afterEach(() => { sidebarOpen.value = false })
onUnmounted(removeCloseOnNavigate)
const isCollapsed = ref(false)
const userName = ref('')

const invitesModalOpen = ref(false)
const sysInvites = ref<any[]>([])
const generalNotifications = ref<any[]>([])
const isResolving = ref(false)

const unreadCount = computed(() => {
    return generalNotifications.value.filter((n: any) => !n.read_at).length
})

const fetchSysInvites = async () => {
    try {
        const { data } = await api.get('/api/system-invites', { skipErrorPage: true })
        sysInvites.value = data.data || [];
    } catch(e) {}
}

const fetchGeneralNotifications = async () => {
    try {
        const { data } = await api.get('/api/notifications', { skipErrorPage: true })
        generalNotifications.value = data.data || [];
    } catch (e) {}
}

const readNotification = async (id: number) => {
    try {
        await api.put(`/api/notifications/${id}/read`);
        const item = generalNotifications.value.find(i => i.id === id);
        if (item) item.read_at = new Date().toISOString();
    } catch (e) {}
}

const readAllNotifications = async () => {
    try {
        await api.put(`/api/notifications/read-all`);
        generalNotifications.value.forEach(item => {
            if (!item.read_at) item.read_at = new Date().toISOString();
        });
        toast.info('Tudo marcado como lido.');
    } catch (e) {}
}

const deleteAllNotifications = async () => {
    const result = await Swal.fire({
        title: 'Limpar Notificações?',
        text: 'Isso apagará permanentemente todo o seu histórico de alertas.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2346D8',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Sim, limpar tudo!',
        cancelButtonText: 'Cancelar',
        background: '#ffffff',
        customClass: {
            popup: 'rounded-[2rem]',
            confirmButton: 'rounded-xl px-6 py-3 font-bold',
            cancelButton: 'rounded-xl px-6 py-3 font-bold'
        }
    });

    if (result.isConfirmed) {
        try {
            await api.delete(`/api/notifications`);
            generalNotifications.value = [];
            toast.info('Histórico limpo.');
        } catch (e) {}
    }
}

const deleteNotification = async (id: number) => {
    try {
        await api.delete(`/api/notifications/${id}`);
        generalNotifications.value = generalNotifications.value.filter(n => n.id !== id);
    } catch (e) {
        toast.error('Erro ao excluir notificação.');
    }
}

const navigateToAction = async (url: string, notifId: number) => {
    await readNotification(notifId);
    invitesModalOpen.value = false;
    // Só navega para rotas internas (evita open redirect / esquemas como javascript:).
    if (typeof url === 'string' && url.startsWith('/') && !url.startsWith('//')) {
        router.push(url);
    }
}

const resolveInvite = async (id: number, action: 'accept' | 'reject') => {
    isResolving.value = true;
    try {
        await api.post(`/api/system-invites/${id}/resolve`, { action })
        toast.success(action === 'accept' ? 'Oba! Workspace adicionado.' : 'Convite recusado.')
        
        await fetchSysInvites()
        
        if (action === 'accept') {
            setTimeout(() => { window.location.reload() }, 800)
        }
    } catch(e: any) {
        toast.error(e.response?.data?.error || 'Falha ao processar convite.')
    } finally {
        isResolving.value = false;
    }
}

onMounted(() => {
    const user = localStorage.getItem('user')
    if (user) {
        try {
            const parsed = JSON.parse(user)
            userName.value = parsed.firstName || parsed.email
        } catch (e) {}
    }
    fetchSysInvites()
    fetchGeneralNotifications()
    
    // Retry fetch after a small delay to capture notifications triggered by the initial /me call in active sessions
    setTimeout(() => {
        fetchGeneralNotifications()
    }, 2000)
})

// Pede à API para apagar o cookie HttpOnly de sessão e limpa os dados locais.
const logout = async () => {
    await apiLogout().catch(() => {})
    router.push('/')
}
</script>
<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.4s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

@keyframes fade-in-down {
    0% { opacity: 0; transform: translateY(-10px); }
    100% { opacity: 1; transform: translateY(0); }
}

.animate-fade-in-down {
    animation: fade-in-down 0.3s ease-out;
}
</style>
