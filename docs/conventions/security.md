# Convenções de segurança (OWASP)

Checklist para qualquer mudança que toque entrada do usuário, dados de outro tenant ou saída em HTML/PDF/CSV.

## Autorização (A01)

- Todo SQL de negócio filtra por `WorkspaceId` vindo do middleware, nunca do body/query.
- IDs recebidos (conta, categoria, cartão...) precisam pertencer ao workspace: use `ReferenceResolver` ou
  `findByIdAndWorkspaceId`.
- Rotas de escrita: `GatekeeperMiddleware::requireEditor('<módulo>')`.
- Ação em outro workspace: `WorkspaceService::canEdit($userId, $workspaceId, $module)` antes de gravar.
- Rotas com `{id}` de workspace na URL: o papel (`workspaceRole`) vale para o workspace **ativo** (header), então
  o `{id}` precisa ser igual a `workspaceId` (`WorkspaceController::updateWorkspace/deleteWorkspace`).
- Módulos de permissão: `accounts`, `categories`, `transactions`, `budgets`, `goals`, `credit_cards`,
  `investments`, `reports`.

## Autenticação (A07)

- Tokens: `TokenService` (JWT HS256, expiração `JWT_TTL`). Chave: `JWT_SECRET` se definida; senão gerada e
  guardada em `app_secrets` pelo `SecretStore`. Trocar a chave desloga todos.
- **Sessão no navegador = cookie** (`SessionCookie`): o login devolve o JWT só no cookie `sf_session`
  (`HttpOnly`, `SameSite=Strict`, `Path=/api`, `Secure` em HTTPS) e nunca no corpo. O JS não lê o token.
  - CSRF: double-submit. O cookie `sf_csrf` (legível) é repetido no header `X-CSRF-Token` pelo `HttpClient`;
    escrita autenticada por cookie sem o header = 403. `Authorization: Bearer` continua aceito (clientes de API).
  - `POST /api/auth/logout` apaga os cookies. Frontend em outro domínio: `COOKIE_SAMESITE=None` + HTTPS +
    `CORS_ALLOWED_ORIGINS` explícito (com `*` o navegador não envia cookie).
  - Nunca `localStorage.setItem('token', ...)` nem `Authorization: Bearer` montado no frontend (regra Semgrep).
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

## XSS no frontend (regras em `.semgrep/app-rules.yml`, bloqueiam o PR)

- Nada de `v-html` (exceção revisada: ícones SVG fixos de `Help.vue`).
- SweetAlert2: `title`, `html` e `footer` são HTML. Dado do usuário só em `text`/`titleText` ou via `textContent`.
- ApexCharts: rótulos com nome digitado pelo usuário passam por `escapeHtml`.

## Requisições do servidor para fora (A10, SSRF)

- Web Push: o servidor faz POST no `endpoint` enviado pelo navegador. Só aceite HTTPS nos serviços de push
  conhecidos (`PushEndpointPolicy`: FCM, Mozilla, Apple, WNS); porta 443, sem usuário/senha na URL.
- Payload do push só com texto e URL interna (começa com `/`); o `sw.js` ignora URLs externas.

## Uploads

- Allowlist de extensão (`pdf`, `csv`, `ofx`, `txt`), limite de 10 MB, nome gerado com `random_bytes`,
  arquivo removido no `finally`.

## Configuração (A05)

- Headers: `SecurityHeadersMiddleware` (API) e `public/.htaccess` (estáticos). CSP sem `unsafe-eval` e sem
  `unsafe-inline` em `style-src`; COOP `same-origin`, COEP `require-corp`, CORP `same-origin` (API: `same-site`).
- CSS de libs entra como arquivo importado em `main.ts`, nunca via `<style>` injetado em runtime:
  ApexCharts com `injectStyleSheet: false`, SweetAlert2 pelo build `sweetalert2.esm.js` (alias no Vite).
  No HTML de SweetAlert use classes Tailwind, nunca o atributo `style=` (a CSP bloqueia).
- CORS por `CORS_ALLOWED_ORIGINS`; origens extras do front em `CSP_CONNECT_SRC`.
- SRI: o build do Vite grava `integrity="sha384-…"` nos `<script>`/`<link>` do `index.html`
  (`frontend/build/subresourceIntegrity.ts`, conferido no CI por `build/check-sri.mjs`). Não altere os arquivos de `dist/assets` depois do build
  (minificação/injeção por proxy ou CDN), senão o navegador bloqueia o app.
- Apache sem versão (`ServerTokens Prod`), PHP sem `X-Powered-By`.

## Dependências (A06)

- `composer audit` e `npm audit --audit-level=high` precisam passar (bloqueiam o Quality Gate).
