# Convenções de testes

## Backend (PHPUnit 10)

- `tests/Unit/`: classes puras ou com mocks (Mockery), estendem `PHPUnit\Framework\TestCase` ou `Tests\TestCase`.
- `tests/Integration/`: estendem `Tests\TestCase`, que cria um SQLite temporário e roda **todas** as migrations.
  Use `$this->db` e os repositórios reais.
- Chamadas HTTP externas usam php-vcr (cassetes em `tests/fixtures/vcr/`).
- Nome do teste descreve o comportamento: `test_split_into_workspace_without_permission_is_refused`.
- Bug corrigido = teste que falha sem a correção.

```bash
vendor/bin/phpunit                                  # suíte completa
vendor/bin/phpunit --filter ReferenceResolverTest   # um arquivo/teste
```

## Frontend (Vitest + @vue/test-utils)

- Testes em `__tests__/` ao lado do código (`components/ui/__tests__/CreatableSelect.test.ts`).
- Ambiente jsdom; alias `@` → `src`.

```bash
cd frontend && npx vitest run
cd frontend && npx vitest run src/components/ui/__tests__/CreatableSelect.test.ts
```

## Descobrir o que testar

```bash
npx -y @colbymchenry/codegraph@1.6.1 affected src/Services/TransactionService.php
```

## Limites conhecidos

- SQL específico de MySQL (Dashboard, parte de convites) não roda no SQLite: cubra a lógica isolada
  (ex.: helper de placeholders) e valide no navegador contra MySQL.
- Teste no navegador: Playwright com o Chromium do ambiente (`executablePath: '/opt/pw-browsers/chromium'`),
  API via `php -S` e SPA via `npx vite` (o proxy do Vite aponta para `localhost:8081`).
