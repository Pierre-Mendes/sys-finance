<template>
  <div class="min-h-screen bg-gray-50 flex">
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
      <div class="h-16 flex items-center justify-between px-4 border-b border-gray-700">
        <h1 v-if="!isCollapsed" class="font-bold text-lg truncate whitespace-nowrap">Banco Digital</h1>
        <h1 v-else class="font-bold text-lg mx-auto">BD</h1>
        <button @click="sidebarOpen = false" class="lg:hidden p-1 text-gray-400 hover:text-white cursor-pointer">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>
      
      <!-- User profile summary area -->
      <div v-if="!isCollapsed" class="p-5 border-b border-gray-700 text-center relative">
        
        <!-- Alertas / System Invites Dropdown Toggle (Desktop) -->
        <button @click="invitesModalOpen = true" class="absolute top-4 right-4 text-gray-400 hover:text-white transition group focus:outline-none" title="Notificações do Sistema">
            <svg class="w-6 h-6 transform group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <span v-if="sysInvites?.length > 0" class="absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full animate-ping"></span>
            <span v-if="sysInvites?.length > 0" class="absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-card"></span>
        </button>

        <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-2 text-white shadow-md cursor-pointer">
           {{ userName.charAt(0).toUpperCase() }}
        </div>
        <p class="font-medium truncate text-gray-100 text-lg">{{ userName }}</p>
        <p class="text-xs text-gray-400">Usuário Autenticado</p>
      </div>

      <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1 px-3">
          <li>
            <router-link to="/dashboard" class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors hover:bg-gray-800 text-gray-300" active-class="bg-blue-600/20 text-blue-400 font-semibold border border-blue-500/20">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
              <span v-if="!isCollapsed">Painel Inicial</span>
            </router-link>
          </li>
          <li>
            <router-link to="/accounts" class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors hover:bg-gray-800 text-gray-300" active-class="bg-blue-600/20 text-blue-400 font-semibold border border-blue-500/20">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
              <span v-if="!isCollapsed">Contas Bancárias</span>
            </router-link>
          </li>
          <li>
            <router-link to="/categories" class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors hover:bg-gray-800 text-gray-300" active-class="bg-blue-600/20 text-blue-400 font-semibold border border-blue-500/20">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
              <span v-if="!isCollapsed">Categorias</span>
            </router-link>
          </li>
          <li>
            <router-link to="/transactions" class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors hover:bg-gray-800 text-gray-300" active-class="bg-blue-600/20 text-blue-400 font-semibold border border-blue-500/20">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
              <span v-if="!isCollapsed">Lançamentos</span>
            </router-link>
          </li>
          <li>
            <router-link to="/budgets" class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors hover:bg-gray-800 text-gray-300" active-class="bg-blue-600/20 text-blue-400 font-semibold border border-blue-500/20">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
              <span v-if="!isCollapsed">Orçamentos</span>
            </router-link>
          </li>
          <li>
            <router-link to="/reports" class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors hover:bg-gray-800 text-gray-300" active-class="bg-blue-600/20 text-blue-400 font-semibold border border-blue-500/20">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
              <span v-if="!isCollapsed">Relatórios</span>
            </router-link>
          </li>
          <li>
            <router-link to="/investments" class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors hover:bg-gray-800 text-emerald-400 font-bold" active-class="bg-emerald-600/20 text-emerald-300 border border-emerald-500/20">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
              <span v-if="!isCollapsed">Investimentos</span>
            </router-link>
          </li>
          <li>
            <router-link to="/credit-cards" class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors hover:bg-gray-800 text-purple-400 font-bold" active-class="bg-purple-600/20 text-purple-300 border border-purple-500/20">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
              <span v-if="!isCollapsed">Cartões de Crédito</span>
            </router-link>
          </li>
          <li>
            <router-link to="/goals" class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors hover:bg-gray-800 text-pink-400 font-bold" active-class="bg-pink-600/20 text-pink-300 border border-pink-500/20">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.563.563 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.563.563 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5z" /></svg>
              <span v-if="!isCollapsed">Metas & Conquistas</span>
            </router-link>
          </li>
          <li>
            <router-link to="/calendar" class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors hover:bg-gray-800 text-gray-300" active-class="bg-blue-600/20 text-blue-400 font-semibold border border-blue-500/20">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
              <span v-if="!isCollapsed">Calendário</span>
            </router-link>
          </li>
          <li>
            <router-link to="/settings" class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors hover:bg-gray-800 text-gray-300" active-class="bg-blue-600/20 text-blue-400 font-semibold border border-blue-500/20">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
              <span v-if="!isCollapsed">Configurações</span>
            </router-link>
          </li>
          
          <li class="mt-4 pt-4 border-t border-gray-700">
            <router-link to="/workspaces" class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors hover:bg-gray-800 text-gray-400" active-class="bg-blue-600/20 text-blue-400 font-semibold border border-blue-500/20">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
              <span v-if="!isCollapsed">Visões (Workspaces)</span>
            </router-link>
          </li>
          
          <li>
            <router-link to="/help" class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors hover:bg-gray-800 text-gray-400" active-class="bg-blue-600/20 text-blue-400 font-semibold border border-blue-500/20">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              <span v-if="!isCollapsed">Ajuda</span>
            </router-link>
          </li>
        </ul>
      </nav>

      <div class="p-4 border-t border-gray-700 flex flex-col gap-3">
         <button @click="isCollapsed = !isCollapsed" class="hidden lg:flex w-full items-center justify-center p-2 bg-gray-800 hover:bg-gray-700 rounded-lg text-gray-400 hover:text-white transition cursor-pointer">
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
             <h1 class="font-bold text-lg text-gray-800">Banco Digital</h1>
         </div>
         <button @click="invitesModalOpen = true" class="p-2 text-gray-400 hover:text-indigo-600 transition relative focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <span v-if="sysInvites?.length > 0 || unreadCount > 0" class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full animate-ping"></span>
            <span v-if="sysInvites?.length > 0 || unreadCount > 0" class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
        </button>
      </header>
      
      <main class="flex-1 p-4 sm:p-6 lg:p-8 w-full max-w-7xl mx-auto overflow-x-hidden">
        <slot />
      </main>
    </div>

    <!-- System Invites Notifications Overlay Modal -->
    <div v-if="invitesModalOpen" class="fixed inset-0 bg-gray-900/40 z-[60] flex items-start justify-center pt-20 px-4 sm:pt-24 backdrop-blur-sm" @click.self="invitesModalOpen = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in-down border border-gray-100 flex flex-col max-h-[80vh]">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    Central de Notificações
                </h3>
                <div class="flex items-center gap-3">
                    <button v-if="unreadCount > 0" @click="readAllNotifications" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Marcar lidas</button>
                    <button @click="invitesModalOpen = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div>
            </div>
            
            <div class="overflow-y-auto p-4 flex-1 bg-gray-50">
                <div v-if="sysInvites?.length === 0 && generalNotifications?.length === 0" class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3"><svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg></div>
                    <p class="text-gray-500 text-sm font-medium">Nenhum alerta ou convite na sua caixa de entrada.</p>
                </div>
                
                <h4 v-if="sysInvites?.length > 0" class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 mt-1">Convites Pendentes</h4>
                <div v-for="inv in sysInvites" :key="'inv'+inv.id" class="bg-white border border-gray-200 rounded-lg p-4 mb-3 shadow-sm hover:shadow transition">
                    <p class="text-sm text-gray-800 mb-2 font-medium"><span class="font-bold text-indigo-600">{{ inv.senderFirstName }}</span> convidou você para o espaço <span class="font-bold text-gray-900 border-b border-indigo-200">{{ inv.workspaceName }}</span>.</p>
                    <p class="text-xs text-gray-400 mb-3 block text-right">{{ new Date(inv.createdAt).toLocaleDateString() }} ás {{ new Date(inv.createdAt).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</p>
                    <div class="flex justify-end gap-2 text-sm mt-2">
                         <button @click="resolveInvite(inv.id, 'reject')" class="px-3 py-1.5 text-red-600 hover:bg-red-50 rounded-lg border border-transparent font-medium transition">Recusar</button>
                         <button @click="resolveInvite(inv.id, 'accept')" class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white rounded-lg font-bold shadow-sm transition disabled:opacity-50" :disabled="isResolving">Aceitar Visão</button>
                    </div>
                </div>

                <h4 v-if="generalNotifications?.length > 0" class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 mt-4 block">Alertas</h4>
                <div v-for="notif in generalNotifications" :key="'notif'+notif.id" :class="['border rounded-lg p-4 mb-3 shadow-sm transition relative', !notif.read_at ? 'bg-orange-50 border-orange-200' : 'bg-white border-gray-200']">
                    <div v-if="!notif.read_at" class="w-3 h-3 bg-red-500 absolute -top-1 -right-1 rounded-full shadow-sm animate-pulse border border-white"></div>
                    <div class="flex gap-3">
                        <div class="mt-1">
                            <svg v-if="notif.type === 'SLA_WARNING'" class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <svg v-else class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="flex-1">
                            <h5 :class="['text-sm font-bold', !notif.read_at ? 'text-gray-900' : 'text-gray-600']">{{ notif.title }}</h5>
                            <p :class="['text-xs mt-1', !notif.read_at ? 'text-gray-800' : 'text-gray-500']">{{ notif.message }}</p>
                            <div class="flex justify-between items-center mt-3">
                                <span class="text-[10px] text-gray-400 font-medium">{{ new Date(notif.created_at).toLocaleDateString() }}</span>
                                <button v-if="!notif.read_at" @click="readNotification(notif.id)" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">Marcar Lido</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { computed } from 'vue'
import api from '@/data/api/HttpClient'
import { toast } from 'vue3-toastify'

const router = useRouter()
const sidebarOpen = ref(false)
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
        const { data } = await api.get('/api/system-invites')
        sysInvites.value = data.data || [];
    } catch(e) {}
}

const fetchGeneralNotifications = async () => {
    try {
        const { data } = await api.get('/api/notifications')
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
    } catch (e) {}
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
})

const logout = () => {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    router.push('/')
}
</script>
