# Arquitetura

SaaS multi-tenant de finanças pessoais. Tudo gira em torno do **workspace** (bolha financeira: pessoal, casal, empresa).

## Visão geral

| Parte | Stack | Pasta |
|---|---|---|
| API | PHP 8.4, Slim 4, PDO, Phinx, Monolog, dompdf | `src/`, `public/index.php`, `db/migrations/` |
| SPA | Vue 3 (Composition API + TS), Vite, Pinia, Tailwind 4, ApexCharts | `frontend/src/` |
| Banco | MySQL 8 (produção), SQLite (testes) | `db/migrations/` |
| Deploy | Docker (Apache + PHP), GitHub Actions via SSH | `Dockerfile`, `docker-compose*.yml`, `.github/workflows/` |

A imagem Docker compila a SPA e serve `public/` pelo Apache: `/api/*` vai para o Slim, o resto para `index.html`.

## Fluxo de uma requisição

```
HTTP → CorsMiddleware → SecurityHeadersMiddleware
     → AuthMiddleware (JWT → attribute userId)
     → WorkspaceMiddleware (X-Workspace-Id → workspaceId, workspaceRole, workspacePermissions)
     → GatekeeperMiddleware::requireEditor('<módulo>')  (só rotas de escrita)
     → Controller → Service → Repository (PDO) → JSON
```

## Camadas do backend (`src/`)

| Pasta | Responsabilidade |
|---|---|
| `Controllers/` | Lê request, monta DTO, chama service, devolve JSON. Sem SQL. |
| `DTO/` | Normaliza e valida a entrada (`isValid()`). |
| `Services/` | Regras de negócio. `ReferenceResolver` faz o "buscar ou criar" de conta/categoria. |
| `UseCases/Transactions/` | Pipeline de criação de transação em *steps* (`AuthorizeSplits → ResolveReferences → Persist → SyncBalance → ProcessSplits`). |
| `Repositories/` | Único lugar com SQL. Sempre filtra por `WorkspaceId`. |
| `Models/` | Objetos simples com getters. |
| `Middleware/` | Auth, workspace, permissões, headers, CORS, rate limit. |
| `Security/` | `TokenService` (JWT HS256) e `SecretStore` (chave gerada e guardada no banco). |
| `Adapters/Bank/` | Parsers de extrato (PDF Itaú/Sicoob, CSV, OFX). |
| `Strategies/` | Cotações de investimento (Brapi, RDC). |

Não há container de DI: as dependências são montadas à mão em `public/index.php`, junto com as rotas.

## Camadas do frontend (`frontend/src/`)

| Pasta | Responsabilidade |
|---|---|
| `core/domain`, `core/repositories` | Tipos e interfaces (sem dependência de framework) |
| `core/security` | Utilitários de segurança (`escapeHtml`) |
| `data/api/HttpClient.ts` | axios com token, `X-Workspace-Id` e logout em 401 |
| `data/repositories` | Implementações HTTP das interfaces de `core/` |
| `presentation/store` | Stores Pinia (cache de contas, categorias, cartões...) |
| `presentation/components/domain` | Modais e componentes de negócio |
| `components/ui`, `components/layout` | Componentes genéricos (`CreatableSelect`, `MainLayout`) |
| `views/` | Telas roteadas (`router.ts`) |

## Conceitos de domínio

- **assets** = receitas, **bills** = despesas (tabelas separadas; `type` `asset`/`bill` na API).
- **category.Level**: 1 = receita, 2 = despesa (`CategoryService::levelForType`).
- **totals**: saldo cacheado por conta, recalculado só pelo `AccountBalanceService`. Regra única: **apenas lançamentos `PAID` contam**; pendentes (a vencer, próximas recorrências, faturas abertas) aparecem só na projeção do Dashboard.
- **Rateio (splits)**: uma despesa espelhada em outros workspaces onde o usuário é editor.
- **Cartões**: compras parceladas geram N transações; a fatura vira uma `bill`.
