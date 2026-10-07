<template>
  <MainLayout>
    <div class="mb-8 border-b border-gray-200 pb-6">
        <h2 class="text-3xl font-bold text-gray-800">Central de Ajuda & Manuais</h2>
        <p class="text-gray-500 mt-2 sm:text-lg">Tudo o que você precisa saber para extrair o máximo do Gerenciador Financeiro Pessoal Colaborativo.</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar Navigation (Sumário) -->
        <div class="w-full lg:w-72 flex-shrink-0">
            <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden sticky top-8">
                <div class="p-4 bg-gray-50 border-b border-gray-200 font-bold text-gray-700 text-sm uppercase tracking-wide">
                    Sumário de Guias
                </div>
                <div class="flex flex-col">
                    <button v-for="tab in guideTabs" :key="tab.id" @click="activeTab = tab.id"
                            class="w-full flex items-center gap-3 text-left px-5 py-4 border-b border-gray-100 font-medium transition duration-200 outline-none"
                            :class="activeTab === tab.id ? 'bg-indigo-50 text-indigo-700 border-l-4 border-l-indigo-600' : 'text-gray-600 hover:bg-gray-50 border-l-4 border-l-transparent'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.iconPath"></path></svg>
                        {{ tab.title }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content Window -->
        <div class="flex-1 bg-white rounded-xl shadow-sm border border-gray-200 p-6 sm:p-8 min-h-[500px]">
             <!-- Tab: Onboarding -->
             <div v-show="activeTab === 'onboarding'" class="animate-fadeIn">
                 <h3 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-2 flex items-center gap-2">
                    <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Começando: Guia de Boas-Vindas
                 </h3>
                 
                 <div class="space-y-8">
                    <div class="flex gap-6 items-start">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-black text-xl shrink-0 shadow-lg shadow-indigo-200">1</div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-1">Entenda os Espaços (Workspaces)</h4>
                            <p class="text-gray-600 text-sm leading-relaxed">Tudo no sistema vive dentro de um "Workspace". Ao criar sua conta, você já ganha um espaço pessoal. Se for gerir finanças de um casal ou empresa, crie um novo Workspace e convide os membros!</p>
                        </div>
                    </div>

                    <div class="flex gap-6 items-start">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500 text-white flex items-center justify-center font-black text-xl shrink-0 shadow-lg shadow-indigo-100">2</div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-1">Configure suas Contas (Carteiras)</h4>
                            <p class="text-gray-600 text-sm leading-relaxed">Vá em "Contas Bancárias" e cadastre seus bancos. É aqui que o saldo real fica armazenado. Sem contas, você não terá de onde "tirar" ou "colocar" dinheiro.</p>
                        </div>
                    </div>

                    <div class="flex gap-6 items-start">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-400 text-white flex items-center justify-center font-black text-xl shrink-0 shadow-lg shadow-indigo-50">3</div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-1">Crie Categorias Customizadas</h4>
                            <p class="text-gray-600 text-sm leading-relaxed">Personalize suas categorias (Ex: "Freelance", "Assinaturas", "Saúde"). Isso deixará seus gráficos e relatórios muito mais precisos.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 p-5 bg-indigo-50/50 rounded-3xl border border-indigo-100">
                        <svg class="w-6 h-6 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="text-sm text-indigo-800 font-medium italic">Dica: Com contas e categorias prontas, seu Dashboard começará a ganhar vida assim que você lançar a primeira transação!</p>
                    </div>

                    <div class="mt-8">
                        <h4 class="font-black text-gray-900 mb-4 uppercase tracking-widest text-xs">Sua Central de Comando: Dashboard</h4>
                        <img src="/docs/help_dash_real.png" alt="Dashboard Real" class="w-full rounded-3xl shadow-2xl border border-gray-100" />
                        <p class="mt-4 text-sm text-gray-500 italic text-center">Acompanhe seu patrimônio líquido consolidado e o desempenho de cada conta em tempo real.</p>
                    </div>
                 </div>
             </div>

             <!-- Tab: Introdução -->
             <div v-show="activeTab === 'intro'" class="animate-fadeIn">
                 <h3 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Visão Geral e Pilares</h3>
                 <p class="text-gray-700 mb-4 leading-relaxed">O Gerenciador Financeiro Pessoal Colaborativo foi desenhado para escalabilidade e clareza. Aqui estão os conceitos fundamentais para você dominar a ferramenta.</p>
                 
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                     <div class="bg-gray-50 border border-gray-100 p-5 rounded-3xl flex flex-col justify-between">
                         <div>
                             <h4 class="font-bold text-gray-800 mb-2">Carteiras Bancárias e Saldo</h4>
                             <p class="text-xs text-gray-500 mb-4 leading-relaxed">Mapeie todas as suas origens de saldo. O sistema trata cada conta de forma isolada, permitindo que você saiba exatamente quanto tem no Inter, Nubank ou até na sua carteira física.</p>
                         </div>
                         <img src="/docs/help_accounts_real.png" alt="Gestão de Carteiras Real" class="w-full h-auto rounded-2xl shadow-lg" />
                     </div>
                     <div class="bg-gray-50 border border-gray-100 p-5 rounded-3xl flex flex-col justify-between">
                         <div>
                             <h4 class="font-bold text-gray-800 mb-2">Lançamentos e Categorias</h4>
                             <p class="text-xs text-gray-500 mb-4 leading-relaxed">Organize seus fluxos com categorias inteligentes. Cada lançamento alimenta automaticamente seus gráficos de despesas por setor e ajuda a monitorar seus limites mensais.</p>
                         </div>
                         <img src="/docs/help_transactions_real.png" alt="Lançamentos Reais" class="w-full h-auto rounded-2xl shadow-lg" />
                     </div>
                 </div>
             </div>

             <!-- Tab: Workspaces -->
             <div v-show="activeTab === 'workspaces'" class="animate-fadeIn">
                 <h3 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Gestão em Equipe (Workspaces)</h3>
                 <p class="text-gray-700 mb-6 leading-relaxed">Um 'Workspace' permite que você isole ambientes. Por exemplo: "Pessoal", "Festa de Casamento", "Microempresa".</p>
                 
                  <h4 class="text-lg font-bold text-gray-800 mb-2">Adicionando Colaboradores</h4>
                 <div class="bg-indigo-50 border border-indigo-100 p-5 rounded-3xl mb-8">
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3">
                            <div class="w-6 h-6 bg-indigo-600 text-white rounded-full flex items-center justify-center text-xs font-bold">1</div>
                            <span class="text-gray-700 text-sm">Gere um código de convite na tela de <strong>Workspaces</strong>.</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-6 h-6 bg-indigo-600 text-white rounded-full flex items-center justify-center text-xs font-bold">2</div>
                            <span class="text-gray-700 text-sm">A pessoa convidada entra no link e cola o código para participar.</span>
                        </li>
                    </ul>
                 </div>

                 <div class="mb-6">
                    <h4 class="font-black text-gray-900 mb-4 uppercase tracking-widest text-xs">Gestão de Colaboração</h4>
                    <img src="/docs/help_workspaces_real.png" alt="Workspaces Real" class="w-full rounded-2xl shadow-xl border border-gray-100" />
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="text-xs text-gray-500 p-3 bg-gray-50 rounded-xl"><strong>Dono do Workspace:</strong> Controla permissões, convites e exclusão.</div>
                        <div class="text-xs text-gray-500 p-3 bg-gray-50 rounded-xl"><strong>Convidado:</strong> Pode lançar transações e visualizar relatórios do espaço compartilhado.</div>
                    </div>
                 </div>
             </div>

             <!-- Tab: Lançamentos & Rateios -->
             <div v-show="activeTab === 'rateios'" class="animate-fadeIn">
                 <h3 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Rateios e Divisão de Contas</h3>
                 <p class="text-gray-700 mb-6 leading-relaxed">Exclusivo sistema de rateio entre espaços. Pague algo na sua conta pessoal e "transfira" uma porcentagem da despesa para outro Workspace.</p>
                 
                 <div class="bg-gray-900 text-white p-6 rounded-3xl shadow-2xl mb-8 overflow-hidden relative">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/20 rounded-full blur-3xl"></div>
                    <div class="relative z-10">
                        <h4 class="text-xl font-black mb-4 flex items-center gap-2">
                            <span class="w-2 h-6 bg-indigo-500 rounded-full"></span>
                            Protocolo de Divisão (Shadow Records)
                        </h4>
                        <p class="text-indigo-200 text-sm leading-relaxed mb-6">
                            Nosso algoritmo de <strong>Split Recursivo</strong> permite que uma despesa seja fracionada. Ao definir um rateio, o sistema cria um "Shadow Record" no Workspace de destino. Se você apagar a transação original, o sistema pergunta se deseja remover as propagações, mantendo a integridade total dos dados entre os membros.
                        </p>
                        
                        <img src="/docs/help_transactions_real.png" alt="Rateio na Prática" class="w-full rounded-2xl border border-white/5 shadow-2xl mb-6" />

                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="p-3 bg-white/5 rounded-2xl border border-white/10">
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Passo 1</p>
                                <p class="text-xs font-medium">Lança valor total</p>
                            </div>
                            <div class="p-3 bg-white/5 rounded-2xl border border-white/10">
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Passo 2</p>
                                <p class="text-xs font-medium">Define % Split</p>
                            </div>
                            <div class="p-3 bg-white/5 rounded-2xl border border-white/10">
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Passo 3</p>
                                <p class="text-xs font-medium">Auto-Repasse</p>
                            </div>
                        </div>
                    </div>
                 </div>
             </div>

             <!-- Tab: Contas e Recorrências -->
             <div v-show="activeTab === 'contas'" class="animate-fadeIn">
                 <h3 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Contas a Pagar e Receber</h3>
                 <p class="text-gray-700 mb-6 leading-relaxed">Controle vencimentos e mantenha suas obrigações em dia. O sistema separa transações de "Dinheiro em Mão" de "Compromissos Agendados".</p>
                 
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-green-50/50 border border-green-100 p-5 rounded-3xl">
                        <h4 class="font-bold text-green-800 mb-1 leading-tight">Receitas (Aportes)</h4>
                        <p class="text-xs text-green-700/70">Tudo o que entra na conta (Salário, Freelance, Rendimentos).</p>
                    </div>
                    <div class="bg-red-50/50 border border-red-100 p-5 rounded-3xl">
                        <h4 class="font-bold text-red-800 mb-1 leading-tight">Despesas (Saídas)</h4>
                        <p class="text-xs text-red-700/70">Custos fixos ou variáveis que reduzem seu patrimônio.</p>
                    </div>
                 </div>

                 <div class="mt-8">
                    <h4 class="font-bold text-gray-800 mb-2">Vencimentos e SLAs de Pagamento</h4>
                    <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                        Cada transação pode ter uma data de <strong>Vencimento</strong>. Compromissos futuros aparecem com destaque no dashboard, permitindo que você priorize o que deve ser pago primeiro para evitar juros. O status <strong>Pendente</strong> mudará para <strong>Pago</strong> assim que você realizar a "Baixa" manual.
                    </p>
                    <img src="/docs/help_dash_real.png" alt="Fluxo de Caixa e Vencimentos" class="w-full rounded-3xl shadow-lg border border-gray-100" />
                 </div>
             </div>

             <!-- Tab: Metas e Sonhos -->
             <div v-show="activeTab === 'goals'" class="animate-fadeIn">
                 <h3 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Metas, Sonhos e Favoritos</h3>
                 <p class="text-gray-700 mb-6 leading-relaxed">As metas permitem priorizar seus objetivos financeiros e entender quando eles se tornarão realidade. Agora com suporte a destaque e vínculo bancário.</p>
                 
                 <div class="space-y-6">
                    <div class="bg-gray-50 border border-gray-100 p-6 rounded-3xl">
                        <h4 class="font-bold text-gray-900 mb-4">Metas Favoritas (Cockpit)</h4>
                        <p class="text-sm text-gray-600 leading-relaxed mb-4">
                            Ao favoritar uma meta (usando a estrela), ela ganha prioridade no seu Dashboard principal. Se você tiver apenas uma favorita, o card <strong>"Dinheiro Guardado"</strong> mostrará o progresso dela. Se tiver várias, mostrará o somatório. É a forma perfeita de manter o foco no seu objetivo principal.
                        </p>
                    </div>

                    <div class="bg-indigo-50 border border-indigo-100 p-6 rounded-3xl">
                        <h4 class="font-bold text-gray-900 mb-2">Vínculo com Contas</h4>
                        <p class="text-sm text-gray-600 leading-relaxed mb-4">
                            Você pode vincular uma meta a uma conta bancária específica. Ao realizar um aporte, o sistema agora permite escolher de qual conta o dinheiro está saindo, gerando uma despesa automática de "Aporte" para manter seu saldo real sempre atualizado.
                        </p>
                        <img src="/docs/help_goals_real.png" alt="Simulação de Metas Real" class="w-full rounded-2xl shadow-md" />
                    </div>
                 </div>
             </div>

             <!-- Tab: Extrato Bancário -->
             <div v-show="activeTab === 'statement'" class="animate-fadeIn">
                 <h3 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Extrato Bancário e Saldo Progressivo</h3>
                 <p class="text-gray-700 mb-6 leading-relaxed">
                    O módulo de Extrato é sua ferramenta de auditoria. Ele permite uma visão microscópica de cada centavo que entra e sai de suas contas.
                 </p>
                 
                 <div class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-6 bg-gray-900 text-white rounded-3xl">
                            <h4 class="font-black text-indigo-400 uppercase text-xs mb-3">Timeline Chronológica</h4>
                            <p class="text-sm text-gray-300 leading-relaxed">Diferente da tela de Lançamentos que foca no título, o Extrato foca na <strong>Data e Saldo</strong>. Veja exatamente quanto você tinha em conta após cada cafezinho ou depósito de salário.</p>
                        </div>
                        <div class="p-6 bg-white border-2 border-indigo-50 rounded-3xl">
                            <h4 class="font-black text-gray-500 uppercase text-xs mb-3">Saldo Progressivo</h4>
                            <p class="text-sm text-gray-600 leading-relaxed">O sistema calcula o "Running Balance", permitindo identificar quebras de padrão e entender em qual dia do mês seu saldo costuma ficar mais crítico.</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-lg font-bold text-gray-800 mb-3">Histórico de Metas Integrado</h4>
                        <p class="text-sm text-gray-600 mb-4">Acessando através do atalho <strong>"Ver Histórico"</strong> nas Metas, o extrato é filtrado para mostrar apenas as movimentações da conta vinculada, ajudando você a auditar suas economias.</p>
                        <img src="/docs/help_dash_real.png" alt="Extrato Bancário Real" class="w-full rounded-3xl shadow-2xl border border-gray-100" />
                    </div>
                 </div>
             </div>

             <!-- Tab: Cartões de Crédito -->
             <div v-show="activeTab === 'cards'" class="animate-fadeIn">
                 <h3 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Cartões de Crédito Refinados</h3>
                 <p class="text-gray-700 mb-6 leading-relaxed">
                    Acompanhe faturas, limites e compras parceladas com uma interface moderna e mobile-first.
                 </p>
                 
                 <div class="space-y-8">
                    <div>
                        <h4 class="text-lg font-bold text-gray-800 mb-3">Visão Geral do Limite</h4>
                        <p class="text-sm text-gray-600 mb-4">Cada cartão possui um painel visual que indica o percentual de uso do limite disponível em tempo real.</p>
                        <img src="/docs/help_cards_real.png" alt="Gestão de Cartões Real" class="w-full rounded-3xl shadow-2xl border border-gray-100" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                        <div>
                            <h4 class="text-lg font-bold text-gray-800 mb-2">Controle Mobile (Bottom Sheet)</h4>
                            <p class="text-sm text-gray-600 leading-relaxed">Ao usar o celular, o histórico de compras e resumos de faturas abrem em um modal deslizante otimizado para facilitar o uso com uma mão.</p>
                        </div>
                        <img src="/docs/help_credit_cards_mobile.webp" alt="Mobile UI Cartão" class="w-80 mx-auto rounded-3xl shadow-xl border border-gray-100" />
                    </div>
                 </div>
             </div>

              <!-- Tab: Ferramentas e Orçamentos -->
             <div v-show="activeTab === 'ferramentas'" class="animate-fadeIn">
                 <h3 class="text-2xl font-bold text-gray-900 mb-4 border-b pb-2">Ferramentas e Orçamentos</h3>
                 
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-bold text-gray-800 mb-2">Orçamentos (Budgets)</h4>
                        <p class="text-sm text-gray-600 mb-4">Crie tetos de gastos e visualize o "Burn Rate" em tempo real através das nossas barras termométricas de criticidade.</p>
                        <img src="/docs/help_budgets_real.png" alt="Orçamentos Real" class="w-full rounded-2xl shadow-lg" />
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 mb-2">Relatórios CSV/XLS</h4>
                        <p class="text-sm text-gray-600 mb-4">Extraia seus dados para auditoria externa em planilhas Excel sempre que desejar, mantendo o controle total da sua soberania de dados.</p>
                        <img src="/docs/help_reports_real.png" alt="Relatórios Real" class="w-full rounded-2xl shadow-lg" />
                    </div>
                 </div>
             </div>

             <!-- Tab: Conciliação Bancária (BETA) -->
             <div v-show="activeTab === 'import'" class="animate-fadeIn">
                 <h3 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-2 flex items-center gap-3">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Conciliação e Importação (BETA)
                 </h3>

                 <div class="space-y-8">
                    <p class="text-gray-700 leading-relaxed">
                        Esqueça a digitação manual. Nosso motor de conciliação permite que você suba arquivos diretamente dos aplicativos dos seus bancos.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-4 bg-indigo-50 rounded-2xl border border-indigo-100 text-center">
                            <span class="block text-2xl mb-1">📄</span>
                            <span class="text-xs font-black uppercase text-indigo-700">PDF Nativo</span>
                        </div>
                        <div class="p-4 bg-indigo-50 rounded-2xl border border-indigo-100 text-center">
                            <span class="block text-2xl mb-1">📊</span>
                            <span class="text-xs font-black uppercase text-indigo-700">CSV Comum</span>
                        </div>
                        <div class="p-4 bg-indigo-50 rounded-2xl border border-indigo-100 text-center">
                            <span class="block text-2xl mb-1">🏦</span>
                            <span class="text-xs font-black uppercase text-indigo-700">OFX Padrão</span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="font-bold text-gray-900">Como Funciona a Engine?</h4>
                        <div class="flex gap-4 items-start">
                            <div class="p-2 bg-gray-100 rounded-lg text-gray-500">1</div>
                            <p class="text-sm text-gray-600"><strong>Detecção Automática:</strong> A IA analisa o texto do arquivo e identifica datas, descrições e valores automaticamente, mesmo em bancos desconhecidos via modo Heurístico.</p>
                        </div>
                        <div class="flex gap-4 items-start">
                            <div class="p-2 bg-gray-100 rounded-lg text-gray-500">2</div>
                            <p class="text-sm text-gray-600"><strong>Refino Manual:</strong> Antes de salvar, você pode editar qualquer campo na tabela de revisão. Se um valor veio errado ou a descrição está confusa, ajuste na hora.</p>
                        </div>
                        <div class="flex gap-4 items-start">
                            <div class="p-2 bg-gray-100 rounded-lg text-gray-500">3</div>
                            <p class="text-sm text-gray-600"><strong>Classificação Avançada:</strong> Você pode marcar itens como <em>Transferência</em> (entre suas contas), <em>Aporte em Meta</em> ou <em>Investimento</em>. O sistema cuidará da lógica contábil por trás.</p>
                        </div>
                    </div>

                    <div class="p-5 bg-amber-50 rounded-3xl border border-amber-100">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-amber-600">⚠️</span>
                            <h5 class="font-bold text-amber-800 text-sm italic">Status: BETA em Refinamento</h5>
                        </div>
                        <p class="text-xs text-amber-700 leading-relaxed font-medium">
                            Estamos aprimorando a engine constantemente. Se algum arquivo não for reconhecido, tente usar o formato <strong>CSV</strong> ou peça ajuda no suporte para que possamos "ensinar" o layout do seu banco para a IA.
                        </p>
                    </div>
                 </div>
             </div>

        </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import MainLayout from '@/components/layout/MainLayout.vue'

const activeTab = ref('onboarding')

const guideTabs = [
    { id: 'onboarding', title: 'Como Começar', iconPath: 'M13 10V3L4 14h7v7l9-11h-7z' },
    { id: 'intro', title: 'Visão Geral', iconPath: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'workspaces', title: 'Espaços e Convites', iconPath: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { id: 'rateios', title: 'Rateios e Divisões', iconPath: 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2' },
    { id: 'contas', title: 'Vencimentos e SLAs', iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'goals', title: 'Metas e Favoritos', iconPath: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
    { id: 'statement', title: 'Extrato e Saldo', iconPath: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
    { id: 'cards', title: 'Cartões de Crédito', iconPath: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
    { id: 'import', title: 'Conciliação (BETA)', iconPath: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { id: 'ferramentas', title: 'Limites e Planilhas', iconPath: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
]
</script>

<style scoped>
.animate-fadeIn {
    animation: fadeIn 0.4s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
