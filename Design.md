# Design e desenvolvimento do frontend

Ponto de partida para quem vai mexer na interface do sysfinance (pessoas ou agentes). Diz **onde está** cada parte
do design system, **quais regras** valem e **como** desenvolver uma tela nova sem sair do padrão.

## Onde está cada coisa

| O quê | Onde |
|---|---|
| Design system (tokens, logo, Capi, canvas de design) | [`design-system/`](design-system/README.md) |
| Canvas de design, versão estática (abre sem o claude.ai) | [`design-system/site/index.html`](design-system/site/index.html) |
| Canvas de design interativo (claude.ai, compartilhe pelo menu Share) | [https://claude.ai/artifact/EpKQjikiEQyb6b7XGnpEZu](https://claude.ai/artifact/EpKQjikiEQyb6b7XGnpEZu) |
| Regras de cor, tipo, forma, movimento e acessibilidade | [`docs/conventions/design-system.md`](docs/conventions/design-system.md) |
| Convenções do Vue (componentes, HTTP, stores, formulários, gráficos) | [`docs/conventions/frontend-vue.md`](docs/conventions/frontend-vue.md) |
| Segurança na UI (escape, CSP) | [`docs/conventions/security.md`](docs/conventions/security.md) |
| Testes (Vitest, Playwright) | [`docs/conventions/testing.md`](docs/conventions/testing.md) |
| Tokens no código | `frontend/src/style.css` (`@theme`) e `frontend/src/styles/dark.css` |
| Marca e mascote | `frontend/src/components/brand/` (`BrandLogo`, `CapiMascot`, `CelebrationToast`) |
| Componentes base | `frontend/src/components/ui/` (`GenericButton`, `EmptyState`, `TableLoader`, `ThemeToggle`, `CreatableSelect`) |
| Layout e menu | `frontend/src/components/layout/` (`MainLayout.vue`, `navigation.ts`) |
| Composables visuais | `frontend/src/presentation/composables/` (`useTheme`, `useCountUp`) |

## Princípios

1. **Clareza antes de enfeite.** Dinheiro gera ansiedade: a tela responde "quanto tenho, quanto sai, o que vence".
2. **Uma cor principal.** Azul Cofre (`brand`) para ação; âmbar só para destaque e conquista; vermelho/verde só com sinal ou ícone.
3. **Legível para todo mundo.** Contraste mínimo de 4,5:1, texto a partir de 12px, foco visível, alvo de toque de 44px.
4. **Movimento com motivo.** Anima para explicar (algo entrou, mudou, deu certo), nunca para chamar atenção em loop.
5. **A Capi tem a voz da marca.** Aparece em vazios, carregamento, alertas e conquistas, nunca no meio de um formulário.

## Desenvolvendo o frontend

### Ambiente

```bash
docker compose up -d            # app em http://localhost:5173 (veja README.md)
# ou, sem Docker:
cd frontend && npm install && npm run dev
```

Antes de abrir PR, rode na pasta `frontend/`:

```bash
npx vitest run     # testes (inclui a checagem de tokens do design system)
npx vue-tsc -b     # tipos
npm run build      # build de produção
```

### Estrutura

```
frontend/src/
  core/            regras puras, sem Vue (domain/: money, dueDates, goals, brand; security/escapeHtml)
  data/            acesso à API (api/HttpClient, repositories/)
  presentation/    stores Pinia, composables, componentes de domínio (domain/, reports/)
  components/      peças de UI reutilizáveis (brand/, ui/, layout/)
  views/           uma tela por rota (sempre dentro de <MainLayout>)
  styles/          dark.css (tradução das classes para o modo escuro)
  style.css        tokens (@theme), base e utilitários do design system
```

Lógica que dá para testar sem tela vai para `core/domain` (ex.: `contributionReachesGoal`). Estado compartilhado
vai para uma store em `presentation/store`.

### Criando uma tela nova

1. Crie `views/MinhaTela.vue` com `<script setup lang="ts">` e envolva o conteúdo em `<MainLayout>`.
2. Registre a rota em `router.ts` **e** o item em `components/layout/navigation.ts`, no grupo certo
   (Dinheiro, Planejamento, Análise ou Conta). O teste `navigation.test.ts` falha se a tela ficar fora do menu.
3. Cabeçalho: `h2` (`text-3xl font-extrabold`) e uma linha de apoio em `text-ink-500`.
4. Cubra os quatro estados:
   - **carregando**: `<TableLoader>` em tabelas ou `.skeleton` em cards (nada de spinner solto com texto piscando);
   - **vazio**: `<EmptyState title="..." description="...">` com o próximo passo no slot;
   - **erro**: `toast.error(...)` com a mensagem da API;
   - **sucesso**: `toast.success(...)`; para conquista real (conta paga, meta concluída), `useCelebrationStore().celebrate(...)`.
5. Ações: `<GenericButton>` (`primary` para a ação principal, uma por área; `secondary`, `ghost`, `danger`).
6. Valores em R$: `formatBRL` (`core/domain/money.ts`); números grandes de destaque podem usar `useCountUp`.

### Cores, tipo e forma

- Use os tokens: `brand-*`, `capi-*`, `ink-*`, `income`, `expense`, `warning` (e `-50` para fundos).
  `blue-*` e `indigo-*` já apontam para `brand`; não introduza outra cor principal.
- Texto secundário em fundo claro: `text-ink-500` ou `text-gray-500`, **nunca** `text-gray-400`. Nada abaixo de `text-xs`.
- Títulos usam a fonte display automaticamente (`h1`–`h3`); para outros elementos, `font-display`.
- Raios: `rounded-lg` controles, `rounded-xl` itens, `rounded-2xl` cards. Sem raio arbitrário.
- Degradês decorativos e borda lateral colorida em card não fazem parte do sistema.

### Modo escuro

O tema (Claro, Escuro ou Sistema) fica no rodapé do menu e é salvo no navegador (`useTheme`). Ele liga a classe
`.dark` no `<html>`.

- As classes comuns (`bg-white`, `text-gray-*`, `border-gray-*`, fundos `*-50`) já são traduzidas por `styles/dark.css`.
- Combinação nova que não fica boa no escuro: use `dark:` no próprio componente (ex.: `dark:bg-ink-800`).
- Confira cada tela nova nos dois temas antes do PR.

### Movimento

- Entrada de cards: `animate-fade-up` com `[animation-delay:60ms]` entre itens (no máximo 6 animados).
- Curvas: `ease-(--ease-out)` padrão; `--ease-spring` só para toggle e celebração.
- `prefers-reduced-motion` já zera tudo globalmente; não use `animate-pulse` para chamar atenção.

### Acessibilidade (checklist)

- Botão é `<button>`, link é `<a>`/`<router-link>`; botão só com ícone leva `aria-label`.
- `<label for>` em todo campo; erro de campo com `aria-invalid` e `aria-describedby`.
- Não remova o anel de foco (`outline-none`) sem colocar outro no lugar.
- Status que muda sozinho (aviso, comemoração): `role="status"`; alerta crítico: `role="alert"`.
- A Capi é decorativa por padrão; passe `label` só quando ela for a única informação visual.

### Segurança e CSP

A CSP de produção (`public/.htaccess`) permite só recursos do próprio domínio:

- nada de Google Fonts, CDN ou `<style>`/`<script>` inline em HTML gerado; fontes vêm de `@fontsource`;
- dado do usuário em `v-html`, SweetAlert `html`, ApexCharts e PDF/CSV passa por `escapeHtml()`.

### Testes

- Componente de UI ou composable novo ganha teste em `__tests__/` ao lado (Vitest + `@vue/test-utils`).
- Bug corrigido vem com teste que falha sem a correção.
- Testes que dependem de tempo usam `vi.useFakeTimers()` ou stub de `requestAnimationFrame` (veja `useCountUp.test.ts`).

## Alterando o design system

1. Mude o valor em [`design-system/tokens.json`](design-system/tokens.json) e replique em `style.css`/`dark.css`;
   `designTokens.test.ts` acusa qualquer divergência.
2. Mudou a Capi? Edite `CapiMascot.vue` e rode `npm run design:export-capi` para atualizar `design-system/capi/`.
3. Mudou o logo? Atualize `BrandLogo.vue`, `design-system/brand/`, `frontend/public/favicon.svg` e `public/app-icons/`.
4. Atualize o canvas de design, copie os arquivos para `design-system/canvas/` e rode `npm run design:build-site`
   para regenerar `design-system/site/` (`designSite.test.ts` confere).
5. Registre a decisão em `docs/conventions/design-system.md` se ela mudar uma regra.
