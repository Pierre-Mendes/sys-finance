# Design system (sysfinance)

Tokens em `frontend/src/style.css` (bloco `@theme` do Tailwind 4). Canvas de referência com marca, componentes e
movimento: artefato "sys-finance Design System" no claude.ai.

## Marca

- Nome exibido: `BRAND_NAME` em `src/core/domain/brand.ts`. Ao trocar, ajuste também `index.html`,
  `public/manifest.webmanifest` e `public/sw.js`.
- Logo: `<BrandLogo />` (`tone="dark"` sobre fundo escuro, `compact` só o símbolo, `animated` só no login).
- Ícones do app em `public/app-icons/` (o `maskable-512.png` deixa margem para o recorte do sistema).

## Capi (mascote)

`<CapiMascot mood="..." />`, com estes humores e momentos:

| mood | quando |
|---|---|
| `happy` | saudação do painel, saldo positivo |
| `celebrating` | meta batida, conta paga, conquista |
| `thinking` | carregando (`TableLoader`), dicas, previsão |
| `alert` | déficit, contas vencidas, orçamento estourado |
| `sleeping` | listas vazias (`EmptyState`) |

Sem `label` ela é decorativa (`aria-hidden`); passe `label` quando ela for a única informação visual.

## Cores

- `brand-*` (Azul Cofre, #2346D8 no 600): ações primárias e links. As escalas `blue-*` e `indigo-*` do Tailwind
  apontam para ela, então não use nenhuma das duas como uma "segunda cor principal".
- `capi-*` (âmbar): destaques e conquistas. `ink-*`: texto e superfícies.
- `income` / `expense` / `warning` (+ `-50` para fundos): sempre acompanhados de sinal (+/−), seta ou ícone.
- Texto secundário sobre branco: `text-ink-500` ou `text-gray-500`. **Nunca `text-gray-400` sobre fundo claro**
  (contraste 2,5:1). Tamanho mínimo de texto: `text-xs` (12px), nada de `text-[10px]`.
- Sobre fundo escuro (sidebar, login), links em `text-brand-400`: o `brand-600` não tem contraste ali.

## Tipo, forma e espaço

- `font-sans` = Manrope; `font-display` = Bricolage Grotesque (h1–h3 já usam). Fontes empacotadas via `@fontsource`:
  a CSP só permite `font-src 'self'`, então nada de Google Fonts.
- O body usa `tabular-nums`, assim os valores em R$ se alinham nas colunas.
- Raios: `rounded-lg` (8px, controles), `rounded-xl` (12px, itens), `rounded-2xl`/`rounded-3xl` (20px, cards).
  Não use raios arbitrários (`rounded-[2rem]`).
- Botões: `GenericButton` com `variant` primary | secondary | ghost | danger, `size` md | sm, `loading`.
- Menu lateral: dados em `components/layout/navigation.ts` (grupos Dinheiro, Planejamento, Análise, Conta).
  Rota nova entra lá; o teste `navigation.test.ts` falha se uma tela interna ficar fora do menu.

## Movimento

- Curva padrão `ease-(--ease-out)`; mola (`--ease-spring`) só em toggle e celebração.
- Utilitários: `animate-fade-up` (entrada; escalone com `[animation-delay:60ms]`), `animate-pop`, `.skeleton`.
- Números de destaque: `useCountUp(ref)` (`presentation/composables/useCountUp.ts`).
- `prefers-reduced-motion` zera animações globalmente (style.css). Nada pisca nem pulsa em loop para chamar
  atenção (`animate-pulse` em alertas, não).
- Foco: anel global de `:focus-visible`; não remova com `outline-none` sem pôr outro no lugar.
