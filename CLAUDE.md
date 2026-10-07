# sys-finance

Gerenciador financeiro multi-tenant: API PHP 8.4 (Slim 4) em `src/` + SPA Vue 3/TS em `frontend/`.
UI e documentação em português; código (identificadores) e commits em inglês.

## Contexto sob demanda (economize tokens)

1. **Regras/convenções**: `docs/INDEX.md` → leia só o arquivo do assunto, ou busque trechos:
   `python3 scripts/docs_search.py "<pergunta>"`.
2. **Código**: use a ferramenta MCP `codegraph_explore` antes de Grep/Read (símbolos + impacto numa chamada).
   Sem MCP (subagentes): `npx -y @colbymchenry/codegraph@1.6.1 explore "<pergunta>"`.
3. **Leitura/edição por símbolo**: ferramentas do Serena (`get_symbols_overview`, `find_symbol`,
   `find_referencing_symbols`, `replace_symbol_body`) em vez de abrir/reescrever arquivos inteiros.

Detalhes: `docs/conventions/agent-workflow.md`.

## Comandos

```bash
vendor/bin/phpunit                         # testes backend (SQLite temporário + migrations)
vendor/bin/phpunit --filter NomeDoTeste
cd frontend && npx vitest run              # testes frontend
cd frontend && npx vue-tsc -b              # typecheck
cd frontend && npm run build
npx -y @colbymchenry/codegraph@1.6.1 affected <arquivos>   # testes afetados por uma mudança
```

## Regras que não podem ser quebradas

- SQL só em `Repositories/`, sempre com parâmetros ligados e filtro por `WorkspaceId` do middleware.
- Rotas de escrita com `GatekeeperMiddleware::requireEditor(...)`; IDs do usuário validados contra o workspace
  (`ReferenceResolver`). Nunca IDs fixos.
- Dado do usuário em HTML/PDF/CSV/SweetAlert sempre escapado; gráficos só pelos helpers de
  `presentation/charts/chartOptions.ts` (tooltip em DOM com `textContent`) (`docs/conventions/security.md`).
- Bug corrigido vem com teste que falha sem a correção.
- Não versionar `.codegraph/`, `.serena/cache/`, `vendor/`, `node_modules/`.
