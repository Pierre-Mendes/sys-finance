# Git, PRs e CI

## Commits

- Conventional Commits em inglês: `feat:`, `fix:`, `ci:`, `docs:`, `chore:`, `refactor:`, `test:`; escopo opcional
  (`fix(security): ...`). Linha de assunto curta; corpo explica o porquê.
- Um commit por mudança lógica (segurança, feature e CI em commits separados).

## PRs

- Base `main`. Descrição em português: resumo, passos de deploy, tabela do que mudou, como foi testado e o que
  **não** foi testado.
- Antes do push: `vendor/bin/phpunit`, `npx vitest run`, `npx vue-tsc -b`, `npm run build` (em `frontend/`).

## Pipelines (`.github/workflows/`)

| Workflow | Quando | O quê |
|---|---|---|
| `verify.yml` (CI Pipeline) | push/PR na main | PHPUnit com cobertura, Vitest, build |
| `security.yml` (Security & Quality Gate) | push/PR na main, semanal | Semgrep, CodeQL, composer/npm audit, Gitleaks, OWASP ZAP, SonarCloud opcional, Dependency-Check semanal, **Quality Gate** |
| `ai-review.yml` | desativado (só manual) | Revisão por IA; reative com uma `OPENAI_API_KEY` válida (instruções no arquivo) |
| `deploy-sandbox.yml` | push na main/sandbox | Deploy de staging via SSH |
| `deploy-prod.yml` | manual | Deploy de produção via SSH |

## Quality Gate

- Reprova se qualquer verificação bloqueante falhar: Semgrep (severidade ERROR), audits High/Critical,
  Gitleaks, regras `FAIL` do ZAP (`.zap/rules.tsv`), SonarCloud (se `SONAR_TOKEN` existir).
- Achado do Semgrep que é falso positivo: `// nosemgrep` **na linha do achado**, com comentário do motivo acima.
- Checks obrigatórios na proteção da `main`: `Quality Gate`, `backend-tests`, `frontend-tests`.
