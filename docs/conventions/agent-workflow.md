# Fluxo de trabalho para agentes (economia de tokens)

O projeto tem três fontes de contexto indexadas. Use-as **antes** de abrir arquivos inteiros.

## 1. CodeGraph: "onde está e o que depende disso?"

Grafo de código local (SQLite em `.codegraph/`, ignorado pelo git), atualizado sozinho pelo servidor MCP.

- **MCP**: `codegraph_explore` com uma pergunta em linguagem natural. Devolve os símbolos relevantes, o código
  atual com número de linha e o *blast radius* (quem chama o quê). Uma chamada costuma substituir vários Grep/Read.
- **CLI** (subagentes ou sem MCP):
  ```bash
  npx -y @colbymchenry/codegraph@1.6.1 explore "como o login gera o token"
  npx -y @colbymchenry/codegraph@1.6.1 callers resolveAccount
  npx -y @colbymchenry/codegraph@1.6.1 impact TransactionService
  npx -y @colbymchenry/codegraph@1.6.1 affected src/Services/AuthService.php   # testes afetados
  ```
- Se a resposta avisar que um arquivo está pendente de sincronização, leia esse arquivo diretamente.

## 2. Serena: "ler/editar por símbolo"

Servidor MCP com language servers (PHP e Vue/TS). Trabalha por símbolo, não por arquivo:

- `get_symbols_overview` para ver a estrutura de um arquivo sem ler o corpo.
- `find_symbol` (com `include_body` só quando precisar do código) e `find_referencing_symbols`.
- `replace_symbol_body`, `insert_after_symbol`, `rename_symbol` para editar um método sem reescrever o arquivo.
- `get_diagnostics_for_file` para checar erros depois de editar.

Não use as memórias do Serena para convenções: a fonte única é esta pasta `docs/`.

## 3. Documentação: "qual é a regra?"

- Comece por [docs/INDEX.md](../INDEX.md) e leia só o arquivo do assunto.
- Ou busque trechos: `python3 scripts/docs_search.py "<pergunta>"` (BM25 local, sem rede).

## Ordem recomendada

1. Tarefa chega → `docs_search` ou INDEX para a regra aplicável.
2. `codegraph_explore` para localizar o código e o impacto.
3. Serena para ler/editar só os símbolos envolvidos.
4. Rodar só os testes afetados (`codegraph affected ...`), depois a suíte completa antes do push.

## O que evitar

- Ler arquivos grandes inteiros (`CreditCards.vue`, `public/index.php`) quando só um trecho importa.
- Grep amplo em `vendor/` ou `frontend/node_modules/`.
- Reexplicar convenções em código ou PR: aponte para o arquivo em `docs/conventions/`.
