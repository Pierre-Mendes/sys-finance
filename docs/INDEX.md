# Índice da documentação

Mapa para encontrar **só o trecho necessário**. Não leia a pasta inteira: escolha pelo assunto abaixo
ou busque por palavra-chave:

```bash
python3 scripts/docs_search.py "rate limit login"      # top trechos com arquivo:linha
python3 scripts/docs_search.py "criar migration" -k 3  # -k = quantos trechos
```

Para **código**, prefira CodeGraph/Serena em vez de ler arquivos (veja [conventions/agent-workflow.md](conventions/agent-workflow.md)).

## Convenções (leia antes de alterar a área)

| Arquivo | Quando ler |
|---|---|
| [conventions/agent-workflow.md](conventions/agent-workflow.md) | Sempre que for navegar/editar código: como usar CodeGraph, Serena e esta busca para gastar menos tokens |
| [conventions/architecture.md](conventions/architecture.md) | Entender camadas, fluxo de uma requisição, onde colocar código novo |
| [conventions/backend-php.md](conventions/backend-php.md) | Controllers, Services, Repositories, DTOs, rotas, DI em `public/index.php` |
| [conventions/database.md](conventions/database.md) | Migrations Phinx, nomes de tabelas/colunas, SQL portável MySQL/SQLite |
| [conventions/frontend-vue.md](conventions/frontend-vue.md) | Views, componentes, stores Pinia, repositórios HTTP, UI |
| [conventions/design-system.md](conventions/design-system.md) | Cores, fontes, logo, Capi (mascote), animações e acessibilidade visual. Comece por [`Design.md`](../Design.md) (guia do frontend) e [`design-system/`](../design-system/README.md) |
| [conventions/security.md](conventions/security.md) | Qualquer entrada do usuário, SQL, HTML, upload, auth, permissões |
| [conventions/testing.md](conventions/testing.md) | Escrever/rodar testes PHPUnit e Vitest, testes no navegador |
| [conventions/git-and-ci.md](conventions/git-and-ci.md) | Commits, branches, PRs, pipelines e Quality Gate |

## Guias operacionais

| Arquivo | Assunto |
|---|---|
| [guides/homelab.md](guides/homelab.md) | Servidor de casa com Tailscale: HTTPS só na tailnet, deploy com `scripts/deploy.sh`, Telegram via Funnel |
| [guides/vps.md](guides/vps.md) | Preparar a VPS e deploy |
| [guides/telegram.md](guides/telegram.md) | Configurar o bot do Telegram |
| [guides/monitoring.md](guides/monitoring.md) | Sentry/GlitchTip no Docker: erros da API, do agendador e do navegador |

## Histórico e decisões

| Arquivo | Assunto |
|---|---|
| [REVISAO_E_ROADMAP.md](REVISAO_E_ROADMAP.md) | Revisão de set/2026: correções de segurança, pendências e roadmap |
| [CHECKLIST_LANCAMENTO.md](CHECKLIST_LANCAMENTO.md) | Out/2026: 20 perguntas de prontidão da v1, o que foi corrigido e o que falta antes de usar |
