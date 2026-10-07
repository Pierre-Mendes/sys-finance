<template>
  <MainLayout>
    <div class="mb-8 flex justify-between items-start flex-wrap gap-4">
      <div>
          <h2 class="text-3xl font-bold text-gray-800">Meus Espaços de Trabalho</h2>
          <p class="text-gray-500 mt-2">Crie novas lógicas financeiras ou junte-se a terceiros através de um código de convite corporativo seguro.</p>
      </div>
      <button @click="createWorkspaceModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-bold shadow-md transition flex items-center gap-2">
         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
         Fundar Novo Espaço
      </button>
    </div>

    <!-- Switcher Rápido (Seletor Principal) -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8 shadow-sm flex items-center justify-between flex-wrap gap-4">
        <div>
            <h3 class="text-xl font-bold text-blue-900 mb-1">Visão Ativa Atual</h3>
            <p class="text-blue-700 text-sm">Todo o Dashboard e Cadastros referem-se à este Workspace seleto:</p>
        </div>
        <div class="w-full sm:w-auto">
            <select v-model="activeWorkspaceId" @change="changeWorkspace" class="w-full sm:w-64 border border-blue-200 bg-white text-blue-900 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 font-semibold shadow-sm cursor-pointer outline-none transition mb-3">
                <option v-for="w in workspaces" :key="w.id" :value="w.id">
                    🏢 {{ w.name }} {{ w.role === 'owner' ? '(Criador)' : '(Convidado)' }}
                </option>
            </select>
            <div class="flex gap-3 justify-end" v-if="activeWorkspaceRole === 'owner'">
                <button @click="editWorkspaceName" class="text-sm font-semibold bg-white border border-blue-200 text-blue-700 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition shadow-sm flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> Renomear</button>
                <button @click="deleteWorkspace" class="text-sm font-semibold bg-white border border-red-200 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-50 transition shadow-sm flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> Apagar Espaço</button>
            </div>
            <div class="flex gap-3 justify-end" v-else-if="workspaces.length > 0">
                <button @click="leaveWorkspace" class="text-sm font-semibold bg-white border border-orange-200 text-orange-600 px-3 py-1.5 rounded-lg hover:bg-orange-50 transition shadow-sm flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg> Sair deste Espaço</button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Join Space Card -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
            <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-4">
                <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                Juntar-se à uma Visão
            </h3>
            <form @submit.prevent="joinWorkspace" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Código do Espaço</label>
                    <input v-model="joinForm.code" type="text" autocomplete="off" autocorrect="off" spellcheck="false" placeholder="Ex: FW-12345" required class="w-full border border-gray-300 rounded p-2.5 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-mono uppercase bg-gray-50" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Palavra-passe de Autorização</label>
                    <input v-model="joinForm.passcode" type="password" autocomplete="new-password" placeholder="Chave fornecida pelo criador..." required class="w-full border border-gray-300 rounded p-2.5 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-gray-50" />
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded p-3 transition shadow">Acessar Workspace Colaborativo</button>
            </form>
        </div>

        <!-- Create Invite Card -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 relative transition" :class="activeWorkspaceRole === 'owner' ? 'opacity-100 pointer-events-auto' : 'opacity-50 pointer-events-none'">
            <div v-if="activeWorkspaceRole !== 'owner'" class="absolute inset-0 z-10 flex items-center justify-center p-4 backdrop-blur-[2px] rounded-xl"><span class="bg-gray-800 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow">Apenas Proprietários podem gerar convites</span></div>
            
            <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-4">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Gerar Novo Código de Convite
            </h3>
            <p class="text-sm text-gray-500 mb-4">Emita chaves seguras temporárias para permitir que terceiros acessem esta visão financeira ("{{ activeWorkspaceName }}").</p>
            <form @submit.prevent="generateInvite" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Palavra-passe de Criptografia</label>
                    <input v-model="inviteForm.passcode" type="password" autocomplete="new-password" placeholder="Crie uma senha de acesso..." required class="w-full border border-gray-300 rounded p-2.5 outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Validade do Código</label>
                    <select v-model="inviteForm.expiresDays" class="w-full border border-gray-300 rounded p-2.5 outline-none focus:border-green-500 focus:ring-1 focus:ring-green-500 bg-white">
                        <option :value="1">1 Dia (24h)</option>
                        <option :value="7">7 Dias</option>
                        <option :value="30">30 Dias (1 Mês)</option>
                        <option :value="180">6 Meses</option>
                        <option :value="0">Não Expirar</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold rounded p-3 transition shadow">Emitir Credencial</button>
            </form>
        </div>
    </div>
    
    <!-- Painel de Gestão Combinado -->
    <div class="mt-8 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50 flex flex-wrap">
            <button @click="activeTab = 'members'" :class="activeTab === 'members' ? 'border-indigo-500 text-indigo-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100'" class="flex-1 sm:flex-none border-b-2 py-4 px-6 text-sm sm:text-base font-bold flex justify-center items-center gap-2 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Membros
            </button>
            <button v-if="activeWorkspaceRole === 'owner'" @click="activeTab = 'invites'" :class="activeTab === 'invites' ? 'border-yellow-500 text-yellow-700 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100'" class="flex-1 sm:flex-none border-b-2 py-4 px-6 text-sm sm:text-base font-bold flex justify-center items-center gap-2 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                Convites Emitidos
            </button>
        </div>
        
        <div class="p-0">
            <!-- TAB: MEMBERS -->
            <div v-show="activeTab === 'members'" class="overflow-x-auto">
                <table class="w-full text-left bg-white">
                    <thead class="bg-indigo-50 text-indigo-900 text-sm border-b">
                        <tr>
                            <th class="p-4 font-semibold">Membro Parceiro</th>
                            <th class="p-4 font-semibold">Afiliação</th>
                            <th class="p-4 font-semibold">Código Origem</th>
                            <th class="p-4 font-semibold">Papel</th>
                            <th class="p-4 font-semibold text-right">Revogar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in members" :key="m.id" class="border-b hover:bg-gray-50 transition">
                            <td class="p-4 font-medium text-gray-800">
                               <div class="flex items-center gap-3">
                                   <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center font-bold text-indigo-700">{{ m.firstName.charAt(0) }}</div>
                                   {{ m.firstName }} {{ m.lastName }}
                               </div>
                            </td>
                            <td class="p-4 text-gray-600 text-sm">{{ new Date(m.joinedAt).toLocaleDateString() }}</td>
                            <td class="p-4 font-mono font-bold text-gray-500 text-sm">
                                <span v-if="m.usedInviteCode">{{ m.usedInviteCode }}</span>
                                <span v-else>-</span>
                            </td>
                            <td class="p-4">
                                <span v-if="m.role === 'owner'" class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1 w-max shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                                    Proprietário
                                </span>
                                <span v-else class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider inline-block shadow-sm">
                                    Convidado
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <div v-if="m.role !== 'owner' && activeWorkspaceRole === 'owner'" class="flex items-center justify-end gap-2">
                                    <button @click="openPermissionsModal(m)" class="text-teal-500 hover:text-teal-700 hover:bg-teal-50 p-2 rounded transition" title="Permissões de Acesso (ACL)">
                                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                    <button @click="transferOwner(m)" class="text-blue-500 hover:text-blue-700 hover:bg-blue-50 p-2 rounded transition" title="Tornar Proprietário (Transferir Liderança)">
                                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                    </button>
                                    <button @click="revokeAccess(m)" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded transition" title="Remover Permissão">
                                        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TAB: INVITES -->
            <div v-show="activeTab === 'invites' && activeWorkspaceRole === 'owner'" class="overflow-x-auto">
                <table class="w-full text-left bg-white">
                    <thead class="bg-yellow-50 text-yellow-900 text-sm border-b">
                        <tr>
                            <th class="p-4 font-semibold">Cód. Corporativo</th>
                            <th class="p-4 font-semibold">Emissão</th>
                            <th class="p-4 font-semibold">Expiração</th>
                            <th class="p-4 font-semibold text-center">Usos Efetuados</th>
                            <th class="p-4 font-semibold text-right">Excluir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="invites.length === 0">
                            <td colspan="5" class="p-6 text-center text-gray-500 text-sm">Nenhum convite emitido pendente.</td>
                        </tr>
                        <tr v-for="inv in invites" :key="inv.id" class="border-b hover:bg-gray-50 transition">
                            <td class="p-2 sm:p-4">
                                <button @click="copyCode(inv.code)" class="font-mono font-bold text-indigo-600 tracking-wide hover:text-indigo-800 hover:bg-indigo-50 px-3 py-1.5 rounded-lg transition border border-transparent hover:border-indigo-200 flex items-center gap-2 group w-full text-left" title="Copiar p/ Área de Transferência">
                                    {{ inv.code }}
                                    <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </button>
                            </td>
                            <td class="p-4 text-gray-600 text-sm">{{ new Date(inv.createdAt).toLocaleDateString() }}</td>
                            <td class="p-4">
                                <span v-if="inv.expiresAt" class="text-red-500 text-sm font-semibold">{{ new Date(inv.expiresAt).toLocaleDateString() }}</span>
                                <span v-else class="text-green-500 text-sm font-semibold border border-green-200 bg-green-50 px-2 py-0.5 rounded">Infinito</span>
                            </td>
                            <td class="p-4 text-center font-bold text-gray-600">
                                {{ inv.usages || '-' }}
                            </td>
                            <td class="p-4 text-right">
                                <button @click="deleteInvite(inv)" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded transition" title="Destruir Lote">
                                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Create Workspace Modal -->
    <div v-if="createWorkspaceModal" class="fixed inset-0 bg-gray-900/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-full">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                 <h3 class="text-xl font-bold text-gray-800">Fundar Novo Espaço Compartilhado</h3>
                 <button @click="createWorkspaceModal = false" class="text-gray-500 hover:text-red-500 transition"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            <div class="p-6 overflow-y-auto">
                 <div class="mb-4">
                     <label class="block text-sm font-semibold text-gray-700 mb-1">Nome do Workspace</label>
                     <input v-model="createForm.name" type="text" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" placeholder="Ex: Finanças do Casal" />
                 </div>
                 <div class="mb-4">
                     <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 cursor-pointer w-max">
                         <input type="checkbox" v-model="createForm.withInvites" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                         Desejo convidar terceiros para o Workspace (Opcional)
                     </label>
                 </div>
                 <div v-show="createForm.withInvites" class="mb-2 bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                     <label class="block text-sm font-semibold text-indigo-900 mb-1">Afiliações por Códigos Coorporativos</label>
                     <p class="text-xs text-indigo-700/80 mb-2 font-medium">Você é o Dono automático do espaço, não informe a sua própria chave! Insira as chaves dos convidados separadas por vírgula.</p>
                     <textarea v-model="createForm.invites" class="w-full border border-indigo-200 rounded-lg p-3 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-mono text-sm max-h-32 bg-white" placeholder="Ex: U-ABCDEF, U-123456" rows="2"></textarea>
                 </div>
            </div>
            <div class="p-6 border-t border-gray-100 bg-white flex justify-end gap-3">
                 <button @click="createWorkspaceModal = false" class="px-5 py-2.5 text-gray-600 hover:text-gray-800 font-semibold transition">Cancelar</button>
                 <button @click="createWorkspace" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition disabled:opacity-50 flex items-center gap-2" :disabled="!createForm.name || isCreatingWorkspace">
                      <svg v-if="isCreatingWorkspace" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                      Fundar Workspace
                 </button>
            </div>
        </div>
    </div>
    
    <!-- Permissions Modal -->
    <div v-if="modals.permissions" class="fixed inset-0 bg-gray-900/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-full">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-teal-50">
                 <h3 class="text-xl font-bold text-teal-900">Gerenciar Permissões</h3>
                 <button @click="modals.permissions = false" class="text-teal-600 hover:text-red-500 transition"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            <div class="p-6 overflow-y-auto">
                 <div class="mb-4">
                     <p class="text-sm font-semibold text-gray-700">Membro: <span class="font-normal">{{ selectedMember?.firstName }} {{ selectedMember?.lastName }}</span></p>
                     <p class="text-xs text-gray-500 mt-1">Configure o nível de acesso que este usuário terá para cada área do sistema dentro deste Workspace.</p>
                 </div>
                 
                 <div class="space-y-4">
                     <div v-for="mod in permissionModules" :key="mod.key" class="border border-gray-200 rounded-lg p-3 flex justify-between items-center">
                         <div>
                             <h4 class="font-semibold text-gray-800 text-sm">{{ mod.label }}</h4>
                         </div>
                         <select v-model="draftPermissions[mod.key]" class="text-sm border border-gray-300 rounded outline-none px-2 py-1 bg-white font-medium focus:ring-1 focus:ring-teal-500">
                             <option value="none">Nenhum</option>
                             <option value="viewer">Visualizar</option>
                             <option value="editor">Editar</option>
                         </select>
                     </div>
                 </div>
            </div>
            <div class="p-6 border-t border-gray-100 bg-white flex justify-end gap-3">
                 <button @click="modals.permissions = false" class="px-5 py-2.5 text-gray-600 hover:text-gray-800 font-semibold transition">Cancelar</button>
                 <button @click="savePermissions" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-lg shadow-sm transition">
                      Salvar Permissões
                 </button>
            </div>
        </div>
    </div>
    
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'
import MainLayout from '@/components/layout/MainLayout.vue'
import api from '@/data/api/HttpClient'
import { useWorkspaceStore } from '@/presentation/store/workspaceStore'

const workspaceStore = useWorkspaceStore()

// Aliases reactivos para o template vindos do Store Global
const workspaces = computed(() => workspaceStore.workspaces)
const activeWorkspaceId = computed({
    get: () => workspaceStore.activeWorkspaceId || '',
    set: (val) => workspaceStore.setActiveWorkspace(val ? Number(val) : null)
})

const members = ref<any[]>([])
const invites = ref<any[]>([])
const activeTab = ref<string>('members')

const createWorkspaceModal = ref(false)
const isCreatingWorkspace = ref(false)
const createForm = ref({ name: '', invites: '', withInvites: false })

const inviteForm = ref({ passcode: '', expiresDays: 7 })
const joinForm = ref({ code: '', passcode: '' })

const modals = ref({ permissions: false })
const selectedMember = ref<any>(null)
const draftPermissions = ref<any>({})

const permissionModules = [
    { key: 'accounts', label: 'Contas Bancárias' },
    { key: 'categories', label: 'Categorias' },
    { key: 'transactions', label: 'Lançamentos' },
    { key: 'budgets', label: 'Orçamentos (Mensal)' },
    { key: 'goals', label: 'Metas e Objetivos' },
    { key: 'credit_cards', label: 'Cartões de Crédito' },
    { key: 'investments', label: 'Investimentos' },
    { key: 'reports', label: 'Relatórios' }
]

const activeWorkspaceName = computed(() => {
    return workspaceStore.activeWorkspace?.name || 'Desconhecida'
})

const activeWorkspaceRole = computed(() => {
    return workspaceStore.activeWorkspace?.role || 'viewer'
})

const fetchMembers = async () => {
    if (!activeWorkspaceId.value) return;
    try {
        const res = await api.get('/api/workspaces/members')
        members.value = res.data.data
    } catch(e) { console.error(e) }
}

const fetchInvites = async () => {
    if (!activeWorkspaceId.value || activeWorkspaceRole.value !== 'owner') return;
    try {
        const res = await api.get('/api/workspaces/invite')
        invites.value = res.data.data
    } catch(e) { console.error(e) }
}

const initialize = async () => {
    await workspaceStore.fetchWorkspaces();
    await fetchMembers();
    await fetchInvites();
}

onMounted(initialize)

// Reinicializa membros e convites se o workspace for trocado por outro componente (ex: Navbar)
watch(() => workspaceStore.activeWorkspaceId, (newId) => {
    if (newId) {
        fetchMembers()
        fetchInvites()
    }
})

const changeWorkspace = () => {
    toast.info('Alterando Visão Ativa...')
    // Refresh page to remount everything within the Layouts correctly using new axios headers natively
    setTimeout(() => { window.location.reload() }, 600)
}

const editWorkspaceName = async () => {
    const { value: name } = await Swal.fire({
        title: 'Renomear Workspace',
        input: 'text',
        inputValue: activeWorkspaceName.value,
        showCancelButton: true,
        inputValidator: (val) => (!val) ? 'Nome é obrigatório!' : null
    });
    if (name) {
        try {
            await api.put(`/api/workspaces/${activeWorkspaceId.value}`, { name })
            toast.success('Workspace renomeado!');
            await workspaceStore.fetchWorkspaces();
        } catch(e: any) {
            toast.error(e.response?.data?.error || 'Erro ao renomear.');
        }
    }
}

const deleteWorkspace = async () => {
    const r = await Swal.fire({
        title: 'Excluir Definitivamente',
        text: `Isso apagará o workspace "${activeWorkspaceName.value}", todas suas transações, contas e categorias para sempre. Deseja prosseguir?`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#C2410C',
        confirmButtonText: 'Sim, Apagar Tudo!'
    })
    
    if (r.isConfirmed) {
        try {
            await api.delete(`/api/workspaces/${activeWorkspaceId.value}`)
            toast.success('Workspace destruído com sucesso.')
            localStorage.removeItem('workspaceId');
            setTimeout(() => { window.location.reload() }, 600)
        } catch(e: any) {
            toast.error(e.response?.data?.error || 'Não foi possível excluir o workspace.')
        }
    }
}

const leaveWorkspace = async () => {
    const r = await Swal.fire({
        title: 'Deixar Workspace',
        text: `Tem certeza que quer abandonar o espaço "${activeWorkspaceName.value}"? Só poderá voltar com um novo convite.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f97316',
        confirmButtonText: 'Sim, sair'
    });
    
    if (r.isConfirmed) {
        try {
            await api.delete('/api/workspaces/members/leave')
            toast.success('Você se desvinculou do espaço.')
            localStorage.removeItem('workspaceId')
            setTimeout(() => { window.location.reload() }, 600)
        } catch(e: any) {
            toast.error(e.response?.data?.error || 'Ação falhou.')
        }
    }
}

const transferOwner = async (member: any) => {
    const r = await Swal.fire({
        title: 'Transferir Liderança',
        text: `Transferir o título de Dono para ${member.firstName}? Você será rebaixado para um membro comum.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2346D8',
        confirmButtonText: 'Sim, Transferir Cargo'
    })
    
    if (r.isConfirmed) {
        try {
            await api.put(`/api/workspaces/members/${member.id}/transfer-owner`)
            toast.success('Você transferiu a titularidade do espaço.')
            setTimeout(() => { window.location.reload() }, 600)
        } catch(e: any) {
            toast.error(e.response?.data?.error || 'Ação falhou.')
        }
    }
}

const openPermissionsModal = (member: any) => {
    selectedMember.value = member;
    
    let currentPerms = {};
    if (typeof member.permissions === 'string') {
        try { currentPerms = JSON.parse(member.permissions) } catch(e) {}
    } else if (member.permissions) {
        currentPerms = member.permissions;
    }

    // Default to viewer for everything if never seeded
    permissionModules.forEach(m => {
        draftPermissions.value[m.key] = (currentPerms as any)[m.key] || 'viewer';
    });

    modals.value.permissions = true;
}

const savePermissions = async () => {
    if (!selectedMember.value) return;
    try {
        await api.put(`/api/workspaces/members/${selectedMember.value.id}/permissions`, {
            permissions: draftPermissions.value
        });
        toast.success(`Permissões atualizadas para ${selectedMember.value.firstName}.`);
        modals.value.permissions = false;
        fetchMembers();
    } catch (e: any) {
        toast.error(e.response?.data?.error || 'Não foi possível salvar as permissões.')
    }
}

const generateInvite = async () => {
    try {
        const payload = { passcode: inviteForm.value.passcode, expiresDays: inviteForm.value.expiresDays }
        const { data } = await api.post('/api/workspaces/invite', payload)
        
        await fetchInvites() // Atualiza tabela de pendentes da view
        
        // Sem onclick inline nem interpolação em HTML: o CSP bloqueia handlers inline e o código
        // é inserido como texto (textContent), nunca como HTML.
        Swal.fire({
            title: 'Código Criado!',
            html: `Use a credencial de segurança abaixo:<br><br>
                   <button type="button" id="invite-code-copy" class="bg-gray-100 hover:bg-indigo-50 border border-gray-200 cursor-pointer inline-block px-6 py-4 rounded-xl transition" title="Clique para copiar">
                      <b id="invite-code-value" class="text-[28px] text-indigo-700 font-mono tracking-[2px]"></b>
                      <br><small class="text-indigo-500 font-bold">📋 Clicar aqui para copiar</small>
                   </button>
                   <br><br><span class="text-[13px] text-gray-500">Passe verbalmente sua palavra-passe para o convidado autenticar!</span>`,
            icon: 'success',
            didOpen: (popup) => {
                const code = String(data.inviteCode ?? '')
                const valueEl = popup.querySelector('#invite-code-value')
                if (valueEl) valueEl.textContent = code
                popup.querySelector('#invite-code-copy')?.addEventListener('click', () => {
                    navigator.clipboard.writeText(code)
                    toast.success('Código copiado para a Área de Transferência!')
                })
            }
        })
        inviteForm.value.passcode = ''
    } catch (e: any) {
        toast.error(e.response?.data?.error || 'Erro ao gerar convite.')
    }
}

const joinWorkspace = async () => {
    try {
        const payload = { inviteCode: joinForm.value.code.toUpperCase(), passcode: joinForm.value.passcode }
        const { data } = await api.post('/api/workspaces/join', payload)
        
        toast.success(data.message)
        joinForm.value.code = ''
        joinForm.value.passcode = ''
        
        // Refresh to fetch newly joined workspace
        setTimeout(() => { window.location.reload() }, 1000)
    } catch (e: any) {
        toast.error(e.response?.data?.error || 'Acesso negado para Credencial / Senha fornecidas.')
    }
}

const createWorkspace = async () => {
    isCreatingWorkspace.value = true;
    try {
        const inviteArray = createForm.value.invites.split(',').map(s => s.trim()).filter(s => s.length > 0)
        const payload = { name: createForm.value.name, invites: inviteArray }
        
        const { data } = await api.post('/api/workspaces', payload)
        toast.success(data.message)
        
        createWorkspaceModal.value = false;
        createForm.value = { name: '', invites: '', withInvites: false }
        
        // Auto select new and refresh
        localStorage.setItem('workspaceId', String(data.workspaceId))
        setTimeout(() => { window.location.reload() }, 800)
        
    } catch(e: any) {
        toast.error(e.response?.data?.error || 'Erro ao fundar espaço de trabalho.')
    } finally {
        isCreatingWorkspace.value = false;
    }
}

const revokeAccess = async (member: any) => {
    const r = await Swal.fire({
        title: 'Revogar Acesso',
        text: `Remover o membro ${member.firstName} definitivamente da sua Visão?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#C2410C',
        confirmButtonText: 'Sim, expulsar!'
    })
    
    if (r.isConfirmed) {
        try {
            await api.delete(`/api/workspaces/members/${member.id}`)
            toast.success('Acesso revogado permanentemente.')
            fetchMembers()
        } catch(e: any) {
            toast.error(e.response?.data?.error || 'Ação falhou.')
        }
    }
}

const deleteInvite = async (inv: any) => {
    const r = await Swal.fire({
        title: 'Excluir Lote de Convite',
        text: `Ninguém que possua a chave ${inv.code} conseguirá entrar na Visão. Apagar?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#C2410C',
        confirmButtonText: 'Sim, desativar código'
    })
    
    if (r.isConfirmed) {
        try {
            await api.delete(`/api/workspaces/invite/${inv.id}`)
            toast.success('Código desativado com sucesso.')
            fetchInvites()
        } catch(e: any) {
            toast.error(e.response?.data?.error || 'Ocorreu um erro ao excluir.')
        }
    }
}

const copyCode = (code: string) => {
    navigator.clipboard.writeText(code);
    toast.success('Código '+code+' copiado!');
}
</script>
