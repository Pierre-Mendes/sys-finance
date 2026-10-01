# Convenções do frontend (Vue 3)

## Componentes

- `<script setup lang="ts">` com Composition API. Tipos para props/emits (`defineProps<{...}>()`).
- Textos da UI em português; nomes de variáveis/funções em inglês.
- Estilo com classes Tailwind; mobile-first.
- Feedback: `toast` (vue3-toastify) para sucesso/erro; `Swal` para confirmação destrutiva.

## Dados e HTTP

- Use o `api` de `@/data/api/HttpClient` (injeta token e `X-Workspace-Id`, trata 401).
  Views antigas usam `axios` direto com header manual: **não copie esse padrão**.
- Acesso a recursos: interface em `core/repositories`, implementação em `data/repositories`, estado em `presentation/store`.
- Stores fazem cache (`fetchX` retorna cedo se já carregou); depois de criar algo "na hora", chame `forceRefreshX()`.

## Formulários

- Conta/categoria: use `CreatableSelect` com `v-model` (id) e `v-model:new-name` (nome novo). O backend
  cria o registro se não existir; não obrigue o usuário a cadastrar antes.
- Envie `accountId`/`categoryId` **ou** `accountName`/`categoryName`, nunca IDs fixos.

## Segurança na UI

- Interpolação `{{ }}` já escapa. **Nunca** passe dado do usuário para `v-html`, `innerHTML`, SweetAlert `html`,
  títulos do SweetAlert ou labels do ApexCharts sem `escapeHtml()` (`@/core/security/escapeHtml`).
- Sem handlers inline em HTML gerado (`onclick="..."`): o CSP bloqueia. Use `didOpen` + `addEventListener`.
- Navegação vinda de dados do servidor: só rotas internas que começam com `/`.

## Comandos

```bash
cd frontend
npm install
npm run dev            # Vite; proxy /api → http://localhost:8081
npx vue-tsc -b         # typecheck
npx vitest run         # testes
npm run build
```

## Gráficos (ApexCharts)

- Não desmonte um `<apexchart>` com `v-if` enquanto os dados recarregam: se o elemento sai do DOM no meio do
  desenho, o ApexCharts rejeita com `Element not found`. Mantenha montado e sinalize a recarga (ex.: `opacity-50`).
- Nomes vindos do usuário em `labels`/tooltip sempre com `escapeHtml` (o ApexCharts usa `innerHTML`).
- Valores em R$ com `formatBRL`/`formatBRLCompact` (`core/domain/money.ts`); variação sem base é `null` → "—".
