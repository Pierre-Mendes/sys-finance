# Gerenciador Financeiro Pessoal (SaaS Multi-Tenant)

> Um ecossistema de ponta para gestão financeira arquitetado tanto para o indivíduo cauteloso quanto para controle orçamentário coletivo através de Rateios e Workspaces Seguros.

---

## 📌 Visão Geral & Introdução
Saindo da premissa de um *tracking* simples, esse projeto foi inteiramente reinventado para escalabilidade. Originalmente desenvolvido num contexto simples, todo o sistema foi movido para uma Arquitetura de Software Monolítica Modular, contando agora com painéis em **Vue.js 3** e uma robusta API em **PHP 8.4 Slim Framework**. 

A mágica core do aplicativo é o recurso de **Workspaces (Tenant-Isolation)** atrelado a algoritmos de  **Rateios de Cobrança Recursiva**, propiciando que contas (como de namorados ou repúblicas universitárias) transacionem montantes matematicamente fracionados em espaços separados com permissões de JWT granulares! 

## ✨ Funcionalidades Incríveis (Features)
1. **Multi-Tenancy Workspaces (Espaços Compartilhados)**:
   - Os usuários não estão presos numa conta. Eles controlam MÚLTIPLOS baldes/visões isoladas.
   - Navegue instantaneamente do seu cenário *Pessoal* para *Acompanhamento Conjugal* ou *Controle da Empresa* usando o Dropdown Global. O App altera escopos na API sem a recarga da tela.
2. **Rateio de Despesas Inter-Espaços Inteligentes**:
   - Comprou o jantar que deu R$ 500 no seu cartão `Pessoal` e deseja jogar 50% nas costas da gestão conjunta `Familiar`?
   - Ao injetar as despesas em transações, crie parcelas de separação que geram e injetam "despesas filhas" (*Shadow Records*) magicamente no balanço associado do Tenant recebedor! 
3. **Visão 360º de Dados Agregados**:
   - Um interruptor mágico no Dashboard que une o bolo do Multi-Tenancy! Descubra graficamente o total de seu Patrimônio entre todos os seus 20 Workspaces combinados.
4. **Alocação via Orçamentos e Budgets Diários**:
   - Determine que em Outubro sua meta para "Passeios" é gastar R$ 800 usando *Budgets Progressivos*. A UI fará cálculos de estouros e indicará criticidade em Barras Termométricas em relação à taxa de queima (Burn).
5. **Acesso Guiado por Chaves Corporativas**:
   - Sem envios pesados de *E-mails*. Convide terceiros gerando UUIDs "Tokens e Chaves Aleatórias" para que amigos autentiquem-se ingressando nos seus espaços sob o perfil MATE (`Convidado`). Um sininho e sino de notificações *real-time* de convite o avisará.
6. **Mecânica de Pagamentos, Vencimentos (SLAs) e Recorrência**:
   - Controle total do seu fluxo de caixa pendente com o recurso "Baixa na Conta". Defina datas de vencimento, adicione Prioridades Críticas aos gastos e automatize lançamentos de "Contas a Pagar" configurando-os com recorrência Mensal/Anual baseada no ciclo temporal!
7. **Integração de Módulos de Investimento (Mock APIs B3/Sicoob)**:
   - Acompanhe variações patrimoniais com um módulo dedicado de Investimentos, buscando cotações diárias via integrações escaláveis com APIs através do padrão Design Patterns `Strategy`.
8. **Módulo de Controle de Cartões de Crédito**:
   - Faça a gestão completa de seus cartões, separando o limite total do saldo em conta.
   - Lance compras parceladas e deixe o sistema gerar automaticamente as faturas (Bills) futuras para que seu fluxo de caixa de longo prazo seja previsível.
9. **Metas, Conquistas & Engine de Simulação**:
   - Defina objetivos financeiros (ex: Enxoval, Carro, Reserva).
   - O sistema utiliza uma engine de simulação que projeta a data exata de conclusão baseada no seu superávit mensal médio e aportes extras.
   - Compartilhe metas entre Workspaces para sonhos coletivos!
10. **Exportações Técnicas de Excel & PDFs**, Agendamento Diário, Tabela Rápida e Mapas de Calor em Calendário Gregoriano.

## 🏗️ Stack Técnológica E Arquitetura
1. **Frontend**: Vite + **Vue 3** (Composition API script setup) guiados por **Clean Architecture** (divisão em `core`, `data` e `presentation`).
   - O núcleo conta com Repositories que isolam chamadas transientes feitas pelo `Axios`.
   - Gerenciamento de Caches instantâneos coordenados magicamente pela **Pinia Store**.
   - Design flexível: TailwindCSS, UI Glassmorphism com SweetAlert2/Vue3-Toastify UX interacional.
2. **Backend**: C-Like API usando **PHP 8.4 c/ Slim Framework (PSR HTTP Router)** + PDO nativo de banco de dados e PHPUnit para testes orgânicos.
3. **Persistência**: **MySQL via Docker (`mariadb`)**. Migrações incrementadas seguras via Phinx. As chaves primárias continuam Integer auto-escaláveis, porém expostas através de rotas associativas ou hashes limitados.

---

## 🚀 Primeiros Passos (Onboarding)
Se esta é sua primeira vez no sistema, siga este fluxo para uma configuração perfeita:

1.  **Entenda o Workspace**: Você começa no seu espaço pessoal. Se precisar de um espaço compartilhado (família/empresa), crie um novo em "Workspaces".
2.  **Cadastre suas Contas**: Vá em `Contas Bancárias` e adicione seus bancos (NuBank, Inter, etc). Sem contas, você não terá saldo para transacionar.
3.  **Defina Categorias**: Personalize suas categorias em `Categorias` para mapear exatamente para onde seu dinheiro flui.
4.  **Lance a Primeira Transação**: Adicione uma receita ou despesa. O dashboard ganhará vida instantaneamente!
5.  **Configure o Crédito**: Adicione seus cartões em `Cartões de Crédito` para começar a provisionar faturas futuras.

---

## 🎯 Guia de Core Flows 
Se precisar expandir o aplicativo, eis fluxos centrais:

### 1. Injeção de Roteamento Multi-Tenant por Cabeçalho (WorkspaceMiddleware)
Para que o front-end carregue contas do "Casal", todas as conexões Axios enviam um cabeçalho local `X-Workspace-Id: {id}`.
No backend, o Middleware PSR inspeciona o token `JWT de Sessão`, depois cruza com a tabela MySQL `workspace_users`. Se o elo se confirma, a rota ganha as propriedades `$request->withAttribute('workspaceId', $id)` destravando injeção nos Services Subsequentes. Impedindo vazamento de dados sensíveis entre tenants!

### 2. O Flúxo Lógico de Divisões em Recursão (The Split Protocol)
Quando postamos no `TransactionController` enviando a flag `splits: [{}]`, o `TransactionService::create` primeiramente cadastra a Fatura (Bill) Pai.
Imediatamente, ele captura a ID do Pai. E a iteração sobre 'Splits' gera uma transação idêntica nas Conta/Workspace Destino apontado, associando a coluna `ParentTransactionId = T_PAI`.  

### 3. Lifecycle do Token Interativo
- O usuário gera um novo Código de Workspace na UI (Passcode é criptografado).
- O backend injeta registro `system_invitations` com `Status = pendente`.
- Front-end exibe pingente vermelho (Bells Notifications).
- Usuário clica => Confirma o request -> Backend faz Update da Role pra Member => Apaga invite provisório => Novo Painel disponível sem f5!

---

## 💻 Instalação & Setup Rápido (Local Development)

Necessário: Docker, Docker Compose, NodeJS v20+

1. **Suba o Motor de Banco de Dados**
```bash
docker compose up -d    # Sobe o MySQL db
``` 
2. **Rode as Migrations de Construção (Schema)**
O banco não tem as tabelas por defautl, construa via Phinx CLI no contêiner do vendor ou via CLI.
```bash
vendor/bin/phinx migrate -e development
```
3. **Execute o Backend (Servidor Embutido do PHP)**
```bash
php -S 0.0.0.0:8081 -t public/
```
4. **Levante o SPA Vue (Front)**
```bash
cd frontend && npm install && npm run dev
```
Inicie no Navegador em `http://localhost:5173`

> Se ocorrer problemas de conexão, certifique se no arquivo `frontend/vite.config.ts`, o `server.proxy` base confere com a porta do servidor PHP, por padrão `8081`. 

## 🗺️ Visão Geral das APIs
`GET/POST` em `/api/auth` (Cadastro e Login)  
`GET/POST/PUT/DEL` em `/api/accounts` (Carteiras bancárias isoladas no workspace atual)  
`GET/POST` em `/api/transactions` (CRUD e orquestração de Splitting recursivos)  
`GET/POST/DEL` em `/api/workspaces` (Troca de dono, exclusões protegidas de ambientes SaaS)  
`GET/POST` em `/api/credit-cards` (Gestão de plásticos e faturas)  
`GET/POST` em `/api/goals` (Metas, conquistas e aportes)  
`GET` em `/api/simulations/forecast` (Engine de projeção de metas)  

*Gerenciador criado sob alta pressão e qualidade. Software Livre. MIT License.*
