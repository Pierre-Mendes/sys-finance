# Convenções de banco de dados

## Migrations (Phinx)

- Arquivo em `db/migrations/AAAAMMDDHHMMSS_descricao_em_snake_case.php`, classe em PascalCase equivalente.
- Use `change()` com a API do Phinx (`$this->table(...)->addColumn(...)->create()`), que funciona em MySQL e SQLite.
- Nunca edite uma migration já aplicada em produção: crie outra.
- Rodar: `vendor/bin/phinx migrate -e development` (no container: `docker exec financas-web php vendor/bin/phinx migrate -e production`).
- Os testes aplicam todas as migrations num SQLite temporário: se a migration não roda em SQLite, os testes quebram.

## Nomes

- Tabelas legadas em PascalCase nas colunas (`AccountId`, `WorkspaceId`, `AccountName`); novas tabelas podem
  usar snake_case (`app_secrets`, `rate_limits`). Mantenha o padrão da tabela que está alterando.
- Toda tabela de dados de negócio tem `WorkspaceId`.

## SQL

- **Sempre** prepared statements com parâmetros ligados. Nada de interpolar valores no SQL.
- Listas `IN (...)`: gere placeholders `?` (veja `DashboardService::select()` com o marcador `{ws}`).
- Datas "agora": passe `date('Y-m-d H:i:s')` como parâmetro em vez de `NOW()` (portável e testável).
- Funções exclusivas de MySQL (`DATE_FORMAT`, `DATE_SUB`, `CONCAT`) quebram no SQLite dos testes;
  se inevitáveis, isole no repositório/serviço e documente que não há cobertura em SQLite.

## Tabelas principais

`user`, `workspaces`, `workspace_users` (Role, Permissions JSON), `account`, `category` (Level 1/2), `assets`,
`bills`, `totals`, `budget`, `credit_cards`, `credit_card_transactions`, `goals`, `goal_contributions`,
`investments`, `investment_transactions`, `notifications`, `workspace_invites`, `rate_limits`, `app_secrets`.
Diagrama ER completo no `README.md`.
