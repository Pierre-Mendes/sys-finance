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
| [`site/`](site/index.html) | **Versão estática do canvas**: HTML puro, abre em qualquer navegador sem o claude.ai. Gerada de `canvas/`. |

## Canvas de design

O canvas interativo (marca, avaliação, fundamentos, componentes clicáveis, movimento e o painel redesenhado)
fica no claude.ai: **[https://claude.ai/artifact/EpKQjikiEQyb6b7XGnpEZu](https://claude.ai/artifact/EpKQjikiEQyb6b7XGnpEZu)**.

- É privado por padrão: para outra pessoa abrir, o dono compartilha pelo menu **Share** do próprio canvas.
- A pasta [`canvas/`](canvas) guarda uma cópia do código-fonte de cada quadro, para histórico e revisão em PR;
  esses arquivos são do formato do editor e não abrem sozinhos no navegador.
- Mudou o canvas? Copie os quadros alterados para `canvas/` e rode `cd frontend && npm run design:build-site`
  no mesmo PR da mudança de código. O teste `designSite.test.ts` falha se `site/` ficar desatualizado.

### Versão estática (`site/`)

[`site/index.html`](site/index.html) lista todos os quadros com prévia, e cada quadro tem a própria página. É gerada por
`frontend/scripts/build-design-site.mjs`, que executa a lógica de cada quadro uma vez e grava o resultado em HTML
comum. As animações (Capi, logo, entradas) funcionam; os cliques (alternar, pagar, reproduzir) não, porque isso só
existe no canvas interativo.

Como abrir:

- **No computador:** abra `design-system/site/index.html` no navegador (duplo clique) ou rode
  `npx serve design-system/site`.
- **Pelo GitHub:** o GitHub mostra o código do HTML, não a página. Para ter um link, publique a pasta no
  GitHub Pages (Settings → Pages) ou em qualquer hospedagem de arquivos estáticos.

O site usa Google Fonts e estilos inline, por isso **não** vai para `frontend/public`: a CSP do app bloquearia os dois.

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
