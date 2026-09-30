# Revisão do sys-finance — setembro/2026

Este documento reúne a revisão técnica do projeto, o que foi corrigido/implementado nesta branch,
o que ficou pendente e um roadmap de funcionalidades inspirado em apps modernos de finanças (como o Recta).

> **Sobre o Recta:** o site `beta.recta.app` estava inacessível a partir do ambiente onde a revisão foi feita
> (bloqueio de rede), então as ideias do roadmap vêm de padrões comuns em apps de finanças pessoais e
> precisam ser confirmadas contra as telas do Recta que você quer reproduzir.

---

## 1. O que mudou nesta branch

### 1.1 Fluxo "buscar ou criar" (sem cadastro obrigatório prévio)

Antes: o usuário precisava seguir o fluxo **conta → cartão → transação**, cadastrando cada coisa em uma tela diferente.
Agora cada formulário valida se o registro já existe e, se não existir, cria na hora:

| Onde | O que o usuário faz | O que acontece |
|---|---|---|
| Novo lançamento (receita/despesa) | Digita o nome da conta e/ou categoria | Se existir (sem diferenciar maiúsculas/minúsculas) é reaproveitada; senão é criada junto com o lançamento |
| Novo cartão / editar cartão | Digita o nome da conta vinculada (ex.: "Nubank") | Conta criada automaticamente se não existir |
| Nova compra / editar compra no cartão | Escolhe ou digita a categoria | Categoria de despesa criada se não existir (antes era sempre `categoryId: 1`, fixo) |
| Dashboard | Checklist "Primeiros passos" | Aparece enquanto o workspace não tem conta/categoria, com atalhos para lançar, adicionar cartão e importar extrato |

**Backend**
- `ReferenceResolver` (`src/Services/ReferenceResolver.php`): resolve conta/categoria por **ID** (que precisa pertencer ao workspace) ou por **nome** (buscar ou criar).
- `ResolveReferencesStep` no pipeline de criação de transações; `CreditCardService` usa o mesmo resolver.
- DTOs aceitam `accountName` / `categoryName` como alternativa a `accountId` / `categoryId`.
- `GET /api/setup/status`: diz o que o workspace já tem (contas, cartões, categorias).

**Frontend**
- Componente `CreatableSelect.vue`: select com busca que oferece "+ Criar …" quando o nome não existe.
- Usado no `TransactionModal` e nas modais de cartão/compra; `SetupChecklist.vue` no Dashboard.

### 1.2 Correções de segurança (OWASP Top 10)

| # | Problema | Risco | Correção |
|---|---|---|---|
| 1 | Token de login era `base64("id:email")`, sem assinatura | **Crítico (A07).** Qualquer pessoa forjava o token de qualquer usuário | JWT HS256 assinado com `JWT_SECRET`, com expiração (`TokenService`) |
| 2 | Rateio (`splits`) gravava despesas em **qualquer** `workspaceId` enviado | **Alto (A01).** Injeção de lançamentos em workspaces de terceiros | `AuthorizeSplitsStep` valida owner/editor no destino **antes** de persistir |
| 3 | `accountId`/`categoryId` não eram validados contra o workspace | **Alto (A01, IDOR).** Referência a dados de outro tenant | Resolver exige que o ID pertença ao workspace |
| 4 | Upload de extrato confiava na extensão enviada, sem limite de tamanho; `exec()` via shell | Médio (A04/A03) | Allowlist `pdf/csv/ofx/txt`, limite de 10 MB, nome aleatório, `proc_open` sem shell |
| 5 | `recovery-question` / `reset-password` sem rate limit | Médio (A07). Força bruta da resposta secreta | `RateLimiterMiddleware` nas duas rotas |
| 6 | CSP com `unsafe-eval`, CORS `*` fixo, `X-Powered-By`/versão do Apache expostos, estáticos sem headers | Médio (A05) | Headers endurecidos na API e no `.htaccess`; CORS via `CORS_ALLOWED_ORIGINS`; `ServerTokens Prod`, `expose_php=Off` |
| 7 | Dependências vulneráveis (guzzle, psr7, slim, dompdf, cakephp/database, vite, axios…) | Alto (A06) | `composer update`/lockfile do npm regenerado: `composer audit` e `npm audit` zerados |
| 8 | Senha legada comparada com `===` | Baixo (timing) | `hash_equals` |
| 9 | Relatório PDF (dompdf) inseria título/categoria/conta sem escape | Médio (A03). Injeção de HTML no PDF | Saída escapada; dompdf com `isRemoteEnabled`/`isPhpEnabled`/`isJavascriptEnabled` desligados e `chroot` |
| 10 | Exportação CSV sem tratamento de fórmulas | Médio (A03). CSV/Formula Injection no Excel | Células iniciadas por `= + - @ TAB CR` recebem `'` |
| 11 | SweetAlert do convite usava `onclick` inline e interpolava HTML | Baixo (XSS / quebrava com o CSP) | Código inserido via `textContent` e evento via `addEventListener` |
| 12 | Nomes de categoria iam crus para o ApexCharts (tooltip usa `innerHTML`) | Médio (A03). XSS armazenado | `escapeHtml` nos labels do gráfico |
| 13 | `IN (...)` montado por concatenação no Dashboard e em Investimentos | Baixo (só inteiros), mas frágil | Placeholders `?` com parâmetros ligados |
| 14 | Ticker do usuário ia direto na URL da Brapi | Baixo (A10, injeção de path/query) | Validação `^[A-Z0-9.]{1,15}$` + `rawurlencode` |
| 15 | Notificações navegavam para qualquer `action_url` | Baixo (open redirect) | Só rotas internas iniciadas por `/` |

### 1.3 Bugs funcionais

- **Categorias de despesa nunca apareciam no modal de lançamento**: `type=bill` era mapeado para nível 1 (receita) e o store buscava só receitas. Agora `bill`/`expense` → nível 2 e o store usa `?type=all`.
- `axios` e `vue-router` estavam em `devDependencies`, mas vão no bundle de produção: movidos para `dependencies`.
- Relatório PDF somava receitas como despesas (comparava o tipo com `income` em vez de `asset`).
- Importação de extrato usava `categoryId: 1` fixo em transferências/metas (agora categorias "Transferências", "Metas" e "Outros" por nome).
- `ai-review.yml` usava o evento inexistente `synchronized` (o correto é `synchronize`).
- CI rodava o Vitest com `continue-on-error: true`, então teste quebrado nunca reprovava o PR.

---

## 2. Pipeline de segurança (GitHub Actions)

Arquivo: `.github/workflows/security.yml`

| Job | Ferramenta | Bloqueia o PR? |
|---|---|---|
| SAST · Semgrep | `p/owasp-top-ten`, `p/php`, `p/javascript`, `p/typescript`, `p/secrets` | Sim, achados de severidade **ERROR** |
| SAST · CodeQL | JS/TS `security-extended` | Não (publica em Code scanning; exige GHAS em repo privado) |
| SCA · composer/npm audit | `composer audit`, `npm audit --audit-level=high` | Sim |
| SCA · OWASP Dependency-Check | NVD, `--failOnCVSS 7` | Semanal/manual (lento sem API key) |
| Secrets · Gitleaks | histórico do PR | Sim |
| DAST · OWASP ZAP | baseline + Ajax spider contra a app em Docker (`.github/dast/`) | Sim, regras marcadas como `FAIL` em `.zap/rules.tsv` |
| Quality · SonarCloud | Quality Gate do Sonar (`sonar-project.properties`) | Sim, **se** `SONAR_TOKEN` estiver configurado |
| **Quality Gate** | Consolida todos os jobs acima no resumo do run | **Sim**, é este que deve ser obrigatório |

`verify.yml` (CI) continua rodando PHPUnit (agora com cobertura), Vitest e o build.

### Configuração necessária no GitHub

1. **Settings → Branches → main → Require status checks**: marcar `Quality Gate`, `backend-tests` e `frontend-tests`.
2. Secrets opcionais:
   - `SONAR_TOKEN` + variável `SONAR_ORGANIZATION` para ligar o SonarCloud (ajuste `sonar.projectKey`).
   - `NVD_API_KEY` para acelerar o OWASP Dependency-Check.
   - `GITLEAKS_LICENSE` só se o repositório passar a pertencer a uma organização.

---

## 3. ⚠️ Antes do próximo deploy

- Defina **`JWT_SECRET`** (mín. 32 caracteres, ex.: `openssl rand -base64 48`) no `.env` da VPS.
  Os compose de staging/produção **não sobem** sem ele (proposital).
- Produção agora roda com `APP_ENV=production` (erros detalhados desligados).
- Todos os usuários serão deslogados uma vez: os tokens antigos são rejeitados e o frontend redireciona para o login.
- Se o frontend chamar a API por uma origem diferente da página (ex.: página em domínio e `VITE_API_BASE_URL` em IP),
  inclua essa origem em `CSP_CONNECT_SRC` e `CORS_ALLOWED_ORIGINS`.

---

## 4. Pendências encontradas (não corrigidas nesta branch)

| Prioridade | Item |
|---|---|
| Alta | Rotas `/api/investments` (POST/PUT/DELETE) e parte de `/api/workspaces` não passam pelo `GatekeeperMiddleware`: um *viewer* consegue alterar dados. |
| Alta | Token em `localStorage` fica exposto a qualquer XSS. Avaliar cookie `HttpOnly` + `SameSite` com proteção CSRF. |
| Média | Controllers devolvem `$e->getMessage()` cru ao cliente (vaza detalhes internos). Padronizar erros de domínio vs. 500 genérico. |
| Média | `recovery-question` revela se um e-mail está cadastrado (enumeração de usuários). |
| Média | Rate limit de login conta também tentativas bem-sucedidas; tabela `rate_limits` cresce sem limpeza. |
| Média | Criação de transação + rateio não roda em transação de banco (`BEGIN/COMMIT`): uma falha no meio deixa dados parciais. |
| Baixa | `TransactionService::getFilteredForUser` filtra em memória e `CreditCardService::getAllCards` faz N+1. |
| Baixa | Views antigas (`Categories.vue`, `Budgets.vue`…) usam `axios` direto em vez do `HttpClient`, duplicando headers. |
| Baixa | SQL do Dashboard é específico de MySQL; os testes (SQLite) não cobrem esse serviço. |

---

## 5. Roadmap de funcionalidades (inspirado em apps como o Recta)

Ordenado por impacto na experiência de "registrar rápido, organizar depois":

1. **Lançamento rápido em linguagem natural**: um campo único do tipo "mercado 120 nubank" que preenche valor, conta e categoria (o `HybridAIEngine` já existente pode ser reaproveitado). Com o *buscar ou criar* desta branch, nada precisa existir antes.
2. **Onboarding guiado por banco**: escolher "Nubank / Inter / Itaú…" cria de uma vez conta + cartão com cor, dia de fechamento e vencimento padrão.
3. **Categorias padrão por workspace** (Alimentação, Moradia, Transporte…) criadas no cadastro, editáveis depois.
4. **Regras de categorização automática** aprendidas da importação de extratos ("UBER*" → Transporte).
5. **Detecção de recorrências/assinaturas** a partir do histórico, com alerta de aumento de preço.
6. **Fatura do cartão por ciclo real** (fechamento/vencimento) em vez de mês civil, com pagamento da fatura debitando a conta.
7. **Transferência entre contas** como tipo próprio (hoje `transfer` existe só no tipo do frontend).
8. **Open Finance** (agregadores como Pluggy/Belvo) para sincronizar saldos e transações sem importar arquivo.
9. **Metas e orçamentos com alertas** via notificação/Telegram quando atingir 80%/100% do teto.
10. **Modo offline/PWA** para lançar no celular sem conexão e sincronizar depois.
