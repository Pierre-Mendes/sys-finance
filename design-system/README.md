<p align="center">
  <img src="brand/logo-light.svg" alt="sysfinance" width="300">
</p>

# Design system sysfinance

Identidade visual e base de interface do sysfinance. O guia de como aplicar isto no código está em
[`../Design.md`](../Design.md); as regras detalhadas, em [`../docs/conventions/design-system.md`](../docs/conventions/design-system.md).

| Pasta / arquivo | O que tem |
|---|---|
| [`tokens.json`](tokens.json) | Cores, fontes, raios, espaçamento e movimento. **Fonte da verdade**: `frontend/src/style.css` e `frontend/src/styles/dark.css` precisam bater com ele (o teste `designTokens.test.ts` confere). |
| [`brand/`](brand) | Símbolo, logo claro/escuro/monocromático e ícone do app. |
| [`capi/`](capi) | A mascote nos cinco humores (SVG estático exportado de `CapiMascot.vue`). |
| [`canvas/`](canvas) | Código-fonte do canvas de design (marca, avaliação, fundamentos, componentes, movimento e painel). |

## Marca

<p>
  <img src="brand/symbol.svg" alt="Símbolo" width="64">
  &nbsp;
  <img src="brand/logo-dark.svg" alt="Logo sobre fundo escuro" width="240">
  &nbsp;
  <img src="brand/logo-mono.svg" alt="Logo monocromático" width="240">
</p>

Uma linha que sobe e termina numa moeda: o progresso que o app quer mostrar. No código: `<BrandLogo />`
(`tone="dark"` sobre fundo escuro, `compact` só o símbolo, `animated` desenha a linha na abertura).

## Capi, a mascote

| <img src="capi/capi-happy.svg" width="110" alt=""> | <img src="capi/capi-celebrating.svg" width="110" alt=""> | <img src="capi/capi-thinking.svg" width="110" alt=""> | <img src="capi/capi-alert.svg" width="110" alt=""> | <img src="capi/capi-sleeping.svg" width="110" alt=""> |
|:-:|:-:|:-:|:-:|:-:|
| `happy` | `celebrating` | `thinking` | `alert` | `sleeping` |
| saudação, saldo positivo | conta paga, meta concluída | carregando, dicas | déficit, contas vencidas | listas vazias |

No app ela é animada (respira, pisca, a moeda gira) e para quando o sistema pede menos movimento.
No código: `<CapiMascot mood="..." />`, `<EmptyState />` e `useCelebrationStore().celebrate(...)`.

## Cores

| Grupo | Uso | Principais |
|---|---|---|
| Azul Cofre (`brand`) | ações primárias, links, item ativo | `#2346D8` · `#1A34A6` · `#E8EDFC` |
| Âmbar Capi (`capi`) | destaques, conquistas | `#F2A541` · `#C97C12` · `#FDE7C4` |
| Tinta (`ink`) | texto, superfícies, bordas | `#0B1324` · `#5B6B85` · `#F6F7F9` |
| Semânticas | receita, despesa, aviso (sempre com sinal ou ícone) | `#0E7A55` · `#C2410C` · `#B45309` |
| Escuro (`dark`) | superfícies e texto do modo escuro | `#17223A` · `#2A3754` · `#EEF1F5` |

## Tipografia, forma e movimento

- **Bricolage Grotesque** nos títulos, **Manrope** no texto (mínimo 12px), números tabulares em valores.
- Raios: 8 (controles), 12 (itens), 20 (cards), pílula.
- Movimento: `cubic-bezier(0.2, 0.8, 0.2, 1)` como padrão; mola só em toggle e celebração; 120 / 200 / 480 / 600 ms.

## Como alterar

1. Edite `tokens.json`.
2. Replique o valor em `frontend/src/style.css` (ou `styles/dark.css`) e rode `cd frontend && npx vitest run`.
3. Mudou a Capi? Edite `frontend/src/components/brand/CapiMascot.vue` e exporte de novo os SVGs de `capi/`.
4. Atualize o canvas de design e copie os arquivos para `canvas/`.

Os arquivos `canvas/*.dc.html` são o formato do editor de design do claude.ai e não abrem sozinhos no
navegador; servem de histórico e de referência visual para mudanças futuras.
