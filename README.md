<p align="center">
  <img src="design-system/brand/logo-light.svg" alt="sysfinance" width="280">
</p>

# sysfinance · Gerenciador Financeiro Pessoal (SaaS Multi-Tenant)

> Um ecossistema de elite para gestão financeira, projetado para o controle absoluto do seu patrimônio, seja ele individual ou compartilhado via visões colaborativas.

---

## 📌 A Nova Era do Controle Financeiro
O sistema transcende o simples rastreamento de gastos. Ele é construído sobre uma base de **Soberania de Dados** e **Arquitetura Industrial (Clean Arch)**, unindo a agilidade do **Vue.js 3** com a robustez e segurança de uma API **PHP 8.4 (Slim Framework)**.

O coração do projeto é o **Cockpit Financeiro**, uma engine inteligente que sintetiza seu fluxo de caixa, investimentos e metas em uma visão 360º unificada por Workspaces. O sistema conta ainda com um **Módulo de Conciliação Bancária (BETA)** robusto, capaz de processar PDF, CSV e OFX com aprendizado contínuo de layouts.

---

## 🏗️ Arquitetura do Projeto

### 🖥️ Arquitetura Frontend
Construído com foco em fluidez de estado e tipagem, o frontend utiliza o padrão **Clean Architecture**, isolando as lógicas de negócio dos componentes visuais.
*   **Core / Engine**: Vue 3 (Composition API) orquestrado pelo Vite, entregando Hot-Module-Replacement quase instantâneo na fase de desenvolvimento.
*   **Data & API Layer**: `axios` configurado como HTTP Client (no `HttpClient.ts`) que atua através de Interceptors injetando o JWT (Token) e identificador do Workspace atual (`X-Workspace-Id`) em absolutamente cada requisição. O roteamento de erros também é unificado nesta camada, escrevendo falhas globais direto no Console do DevTools para monitoria constante.
*   **Presentation / UI**: **Tailwind CSS 4** sobre os tokens do [design system](#-design-system), modelo `Mobile-First`, modo claro/escuro, animações que respeitam `prefers-reduced-motion` e gráficos com **ApexCharts**. Guia completo de desenvolvimento do frontend em [`Design.md`](Design.md).

### ⚙️ Arquitetura Backend
O Backend é uma API RESTful de alta resposta orientada a Injeção de Dependências.
*   **Server & Router**: PHP 8.4 impulsionando o microframework **Slim 4**. A API abraça um ecossistema estrito através de "RouteCollectorProxy", centralizando checagens de autorização.
*   **Fluxo de Requisitos (Pattern)**: O fluxo obedece à tríade de Separação de Preocupações (`Controllers` -> `Services` -> `Repositories`).
    *   **Controllers** blindam a borda, validando parâmetros HTTP.
    *   **Services** comportam todas as regras financeiras complexas (Ex: Recalcular `projectedBalance` no Dashboard).
    *   **Repositories** executam a persistência isolando a conexão PDO, permitindo facilmente a adoção de mockups nos Testes.
*   **Gatekeepers / Segurança**: Middlewares (`AuthMiddleware`, `WorkspaceMiddleware`) filtram acessos baseados na força do Token JWT.
*   **Migrations**: A estrutura evolutiva do banco está sob custódia oficial do **Phinx**, garantindo integridade das mutações do DB da Produção sem perigo de quebra (Idempotência nativa aplicada nas últimas versões).

---

## 🎨 Design System

| <img src="design-system/capi/capi-happy.svg" width="90" alt=""> | <img src="design-system/capi/capi-celebrating.svg" width="90" alt=""> | <img src="design-system/capi/capi-thinking.svg" width="90" alt=""> | <img src="design-system/capi/capi-alert.svg" width="90" alt=""> | <img src="design-system/capi/capi-sleeping.svg" width="90" alt=""> |
|:-:|:-:|:-:|:-:|:-:|

A interface segue o design system **sysfinance**: Azul Cofre como cor principal, âmbar para conquistas,
Manrope + Bricolage Grotesque, modo escuro e a **Capi**, a capivara mascote que aparece em estados vazios,
carregamentos, alertas e quando você paga uma conta ou conclui uma meta.

| Onde | O que tem |
|---|---|
| [`design-system/`](design-system/README.md) | Tokens (`tokens.json`), logo, Capi em SVG e o código do canvas de design |
| [`design-system/site/`](design-system/site/index.html) | Canvas de design em HTML estático: abra `index.html` no navegador |
| [`Design.md`](Design.md) | Como desenvolver o frontend: estrutura, tela nova, estados, cores, modo escuro, movimento, acessibilidade |
| [`docs/conventions/design-system.md`](docs/conventions/design-system.md) | Regras detalhadas de cor, tipo, forma e movimento |

Os tokens em `design-system/tokens.json` são a fonte da verdade; um teste do frontend falha se
`frontend/src/style.css` divergir deles.

---

## 🗄️ Estrutura do Banco de Dados (DER)

Este projeto opera um ecossistema SQL baseamente desenhado num modelo estrela focado na entidade **Workspace** (`SaaS / Multi-Tenant`). Isso garante que dados Pessoais sejam física e logicamente separados de dados Famliares/Conta Conjunta, bastando apenas uma troca de contexto na sessão.

### 📝 Dicionário de Tabelas (O que cada uma faz?)
1.  **`user`**: Detém as chaves da vida do cliente (Autenticação via senha e Perguntas de Recuperação de segurança isoladas).
2.  **`workspaces`**: Núcleo isolador do SaaS. Representa uma "bolha financeira" autônoma (Pode ser do tipo *Pessoal*, *Empresa*, *Casal*). Em volta dele transitam quase todos os registros.
3.  **`workspace_users`**: Tabela pivô de Relação que define *quem* pode entrar nos Workspaces e com *qual poder* (`owner`, `editor`, `viewer`).
4.  **`account` / `bank_accounts`**: Suas caixas-fortes. Contas Correntes, Carteira Pessoal, Contas Digitais dentro de um determinado Workspace.
5.  **`category`**: Classificador universal termométrico da saúde financeira (Alimentação, Lazer, etc).
6.  **`assets`**: Representa todas as **Receitas / Entradas** de fluxo financeiro vinculadas ou não a categorias e contas (Também usado em investimentos).
7.  **`bills`**: Representa todas as **Despesas / Saídas** de fluxo de caixa (incluindo status dinâmico entre Pendente ou Paga).
8.  **`budget`**: O Limitador termométrico. Atribui teto de gastos (`amount`) estrito a categorias num dado mês.
9.  **`credit_cards`**: Entidade matriz para abstrair cartões plásticos de faturas fechadas (Dias de Fechamento/Vencimento e Limite de Crédito).
10. **`credit_card_transactions`**: Compras efetuadas no crédito, segmentadas por cartões, podendo calcular recursivamente projeções de *parcelamentos* em faturas virtuais através dos anos.
11. **`goals`**: Suas Metas de Vida. Uma poupança paralela travada ("Comprar Carro", "Viagem Europa") com `TargetAmount` (Alvo) a ser alcançado e vinculação possível à sua conta corrente preferida.
12. **`goal_contributions`**: "Pingos d'água" de investimentos mensais e discretos sendo alocados contra a Tabela `goals` para avanço quantificado de progresso.
13. **`notifications`**: Ponto central de alarme multi-serviços assíncrono.
14. **`totals`**: Um snapshot computado local que mantém o saldo cacheado da conta para aliviar agregações imensas e leituras do Cockpit.
15. **`bank_statement_templates`**: Cérebro da engine de importação. Armazena padrões de Regex e mapeamentos de colunas aprendidos pela IA para automatizar a leitura de extratos de novos bancos.


### 📊 Diagrama Entidade-Relacionamento (Mermaid ERD)

```mermaid
erDiagram
    user ||--o{ workspace_users : "ingressa em"
    workspaces ||--o{ workspace_users : "comporta"
    user ||--o{ notifications : "recebe"
    workspaces ||--o{ account : "agrega"
    workspaces ||--o{ category : "consolida"
    workspaces ||--o{ assets : "abrange (receitas)"
    workspaces ||--o{ bills : "abrange (despesas)"
    workspaces ||--o{ goals : "possui"
    workspaces ||--o{ credit_cards : "detem"

    account ||--o{ assets : "recebe depósitos"
    account ||--o{ bills : "paga contas"
    account ||--o{ goals : "ampara progresso"
    account ||--o{ goal_contributions : "lastreia"

    category ||--o{ assets : "classifica (+)"
    category ||--o{ bills : "classifica (-)"
    category ||--o{ budget : "recebe teto"
    
    credit_cards ||--o{ credit_card_transactions : "processa compra"
    
    goals ||--o{ goal_contributions : "é alimentada por"

    user {
        int UserId PK
        string FirstName
        string Email
        string recovery_question
    }
    workspaces {
        int WorkspaceId PK
        string WorkspaceName
    }
    account {
        int AccountId PK
        string AccountName
    }
    assets {
        int AssetsId PK
        double Amount
        date Date
        string status
    }
    bills {
        int BillsId PK
        double Amount
        date Dates
        string status
    }
    goals {
        int GoalId PK
        double TargetAmount
        double AccumulatedAmount
        boolean IsFavorite
    }
    credit_cards {
        int CreditCardId PK
        string Name
        int Limit
    }
```

---

## 🚀 Pipeline Automatizada de Deploy (CI/CD)
O ecossistema é mantido vivo de forma automática através das `GitHub Actions`:
*   **Workflow Staging (Sandbox)**: Gatilho automático ou manual focado na porta SSH, espelha para o provedor Hostinger e reergue o container Sandbox (`porta 8082`), rodando também a injeção do `PHPMyAdmin (8083)` para debug manual interno.
*   **Workflow Produção**: Uma rotina controlada manualmente que refaz o espelhamento mas de forma sólida isolando para o tráfego da rede (`porta 80` padrão).
*   **CI (`verify.yml`)**: PHPUnit (com cobertura), Vitest e build do frontend.
*   **Security & Quality Gate (`security.yml`)**: SAST (Semgrep OWASP Top 10 + CodeQL), SCA (`composer audit`, `npm audit`, OWASP Dependency-Check semanal), Gitleaks, DAST (OWASP ZAP contra a app em Docker) e SonarCloud opcional, consolidados no job **Quality Gate**.

> Documentação e convenções: [`docs/INDEX.md`](docs/INDEX.md) · Design e frontend: [`Design.md`](Design.md) · Revisão, pendências e roadmap: [`docs/REVISAO_E_ROADMAP.md`](docs/REVISAO_E_ROADMAP.md).
> **Deploy:** a chave do JWT é gerada e guardada no banco automaticamente; `JWT_SECRET` é opcional (tem prioridade se definido).

---

## 💻 Instalação Local (Developer Setup)

**Requisitos**: Docker (com Docker Compose v2). Node e PHP na máquina são opcionais.

```bash
docker compose up -d --build   # primeira vez (ou depois de mudar o Dockerfile)
docker compose up -d           # nas próximas
docker compose logs -f backend frontend
docker compose down            # para tudo (use -v para apagar também o banco)
```

| Serviço | Endereço | O que faz |
|---|---|---|
| `frontend` | http://localhost:5173 | Vite com hot reload; **use o app por aqui** (`/api` é repassado ao backend) |
| `backend` | http://localhost:8081/api/health | PHP 8.4 + Apache; instala o Composer e roda as migrations ao subir |
| `db` | localhost:3307 (root/root) | MySQL 8 com volume persistente |
| `scheduler` | — | lembretes de contas a vencer a cada 15 min |

O código é montado por volume: alterações em `src/` e `frontend/src/` aparecem sem rebuild.
Mudou `composer.lock` ou `package-lock.json`? Basta reiniciar o container (`docker compose restart backend frontend`).
Testes rodam dentro dos containers: `docker compose exec backend vendor/bin/phpunit` e
`docker compose exec frontend npx vitest run`.

Sem Docker: `vendor/bin/phinx migrate -e development`, `php -S 127.0.0.1:8081 -t public public/index.php`
e `cd frontend && npm install && npm run dev` (acesse `http://localhost:5173`).

---

*Desenvolvido com foco em precisão matemática e design de alta fidelidade. MIT License.*
