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
  títulos do SweetAlert sem `escapeHtml()` (`@/core/security/escapeHtml`).
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

## Gráficos (Apache ECharts)

- Use `<BaseChart :option="..." label="..." :height="...">` (`components/ui/BaseChart.vue`): registra só os tipos
  usados (linha, barra, pizza), redimensiona sozinho e expõe `label` para leitores de tela (o canvas não tem texto).
- Monte a opção com os helpers de `presentation/charts/chartOptions.ts` (`cartesian`, `areaSeries`, `barSeries`,
  `donutOption`, `axisTooltip`, `referenceLine`) dentro de um `computed` que lê `chartTheme(isDark)`: assim cores,
  eixos e tooltip seguem o design system e o modo escuro sem código repetido.
- Tooltip só pelos helpers: eles montam DOM com `textContent` (nome do usuário nunca vira HTML) e sem `style=`
  (a CSP bloqueia). String com HTML num `formatter` é barrada pelo Semgrep (`echarts-html-string-formatter`).
- Ao recarregar dados, mantenha o gráfico montado e sinalize a recarga (ex.: `opacity-50`) em vez de piscar.
- Valores em R$ com `formatBRL`/`formatBRLCompact` (`core/domain/money.ts`); variação sem base é `null` → "—".
