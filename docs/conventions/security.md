# Convenções de segurança (OWASP)

Checklist para qualquer mudança que toque entrada do usuário, dados de outro tenant ou saída em HTML/PDF/CSV.

## Autorização (A01)

- Todo SQL de negócio filtra por `WorkspaceId` vindo do middleware, nunca do body/query.
- IDs recebidos (conta, categoria, cartão...) precisam pertencer ao workspace: use `ReferenceResolver` ou
  `findByIdAndWorkspaceId`.
- Rotas de escrita: `GatekeeperMiddleware::requireEditor('<módulo>')`.
- Ação em outro workspace: `WorkspaceService::canEdit($userId, $workspaceId, $module)` antes de gravar.
- **Pendente**: rotas de `/api/investments` e parte de `/api/workspaces` ainda sem gatekeeper (ver REVISAO_E_ROADMAP).

## Autenticação (A07)

- Tokens: `TokenService` (JWT HS256, expiração `JWT_TTL`). Chave: `JWT_SECRET` se definida; senão gerada e
  guardada em `app_secrets` pelo `SecretStore`. Trocar a chave desloga todos.
- Senhas com `password_hash`/`password_verify`; comparações de segredo com `hash_equals`.
- Endpoints públicos sensíveis com `RateLimiterMiddleware`.

## Injeção (A03)

- SQL: prepared statements sempre; `IN (...)` com placeholders gerados.
- HTML no PDF: `htmlspecialchars` em todo dado do usuário (`ReportController::html()`); dompdf com
  `isRemoteEnabled`/`isPhpEnabled`/`isJavascriptEnabled` desligados.
- CSV: `ReportController::csvCell()` neutraliza células que começam com `= + - @ TAB CR`.
- Comandos externos: `proc_open` com array de argumentos, nunca string de shell.
- URLs externas com dado do usuário: validar formato + `rawurlencode` (ex.: tickers da Brapi).
- Frontend: ver seção "Segurança na UI" em [frontend-vue.md](frontend-vue.md).

## Uploads

- Allowlist de extensão (`pdf`, `csv`, `ofx`, `txt`), limite de 10 MB, nome gerado com `random_bytes`,
  arquivo removido no `finally`.

## Configuração (A05)

- Headers: `SecurityHeadersMiddleware` (API) e `public/.htaccess` (estáticos). CSP sem `unsafe-eval`.
- CORS por `CORS_ALLOWED_ORIGINS`; origens extras do front em `CSP_CONNECT_SRC`.
- Apache sem versão (`ServerTokens Prod`), PHP sem `X-Powered-By`.

## Dependências (A06)

- `composer audit` e `npm audit --audit-level=high` precisam passar (bloqueiam o Quality Gate).
