# Checklist de lançamento da v1 (out/2026)

Respostas às "20 perguntas antes de decidir se o SaaS está pronto", com base no código do commit desta revisão.
Contexto: a v1 é para **uso próprio e de convidados (família/casal)**, sem cobrança. Itens de pagamento ficam como N/A.

Legenda: ✅ ok · 🔧 corrigido nesta revisão · ⚠️ pendente (não bloqueia o uso) · ❌ bloqueia · N/A não se aplica

## O que mudou nesta revisão

| Área | Correção |
|---|---|
| Segurança (GitGuard, 30 findings → 0) | Actions fixadas por SHA + Dependabot; importação de extrato em memória (PDF via stdin para `pdftotext`, sem arquivo temporário nem `unlink`); `Help.vue` sem `v-html` |
| phpMyAdmin | Estava **público na porta 8084 com login automático como root** (e o de staging, 8083, com `root/root`; MySQL de staging aberto na 3308). Agora só sob demanda, em `127.0.0.1`, via túnel SSH |
| Erros | Controllers devolviam a mensagem crua da exceção (SQL ia para o navegador) e não registravam nada. Agora erro interno vira mensagem genérica + código de referência, registrado no log e no Sentry |
| Integridade | Lançamento com rateio que falhava no meio ficava gravado pela metade (e descontava o saldo). Criar/editar/excluir/pagar/desconsiderar agora são atômicos |
| Backup | Não existia. Serviço `backup` diário com retenção de 14 dias + backup automático antes de cada migration + `restore.sh` |
| Rollback | Deploy agora aceita `ref` (qualquer SHA/tag), faz health check e volta sozinho para a última versão boa se falhar |
| Importação | Extrato de layout desconhecido dava **erro fatal**: `HybridAIEngine.php` tinha uma `}` sobrando e a tabela `bank_statement_templates` só existia num `.sql` nunca aplicado (agora é migration) |
| Pequenos | Botão de cadastro sem duplo envio; chamadas ao Telegram com timeout |

## As 20 perguntas

| # | Pergunta | Status | Resposta |
|---|---|---|---|
| 1 | O que ainda parece protótipo? | ✅/⚠️ | ~~Bot do Telegram~~ **refeito** (out/2026): vincula a conta por código, lança receitas/despesas em texto livre, consulta saldo/mês/contas e manda os lembretes (`guides/telegram.md`). **Importação de extrato** (BETA): adapters fixos para Itaú/Sicoob/CSV/OFX, o resto cai na heurística |
| 2 | O que pode quebrar com o primeiro cliente real? | 🔧/⚠️ | Rateio parcial (corrigido). Ainda: SQL do Dashboard é específico de MySQL e não tem teste (os testes rodam em SQLite); `getFilteredForUser` filtra em memória (fica lento com muitos anos de dados) |
| 3 | Fluxos sem tratamento de erro? | 🔧 | Todos os controllers tratam exceção; erros internos agora são registrados e não vazam detalhes. Exceções não previstas caem no `ErrorMiddleware` (log + Sentry) |
| 4 | E se uma API externa falhar? | ✅ | Brapi (cotações): timeout de 10 s e retorno vazio, a tela segue funcionando. Web Push: falha por dispositivo não derruba o agendador. Telegram: timeout adicionado. Sentry é opcional |
| 5 | O usuário pode ficar preso? | ✅/⚠️ | Sessão expirada redireciona para o login; "esqueci a senha" funciona por pergunta secreta. ⚠️ Quem esquecer **também** a resposta secreta só volta com intervenção manual no banco (não há e-mail) |
| 6 | O onboarding leva rápido ao primeiro resultado? | ✅ | Workspace criado no cadastro; checklist "Primeiros passos" no Dashboard; conta/categoria criadas na hora no primeiro lançamento ("buscar ou criar") |
| 7 | Tela sem loading, empty state ou feedback? | ✅ | Todas as telas de dados têm loading e estado vazio; ações usam toast/SweetAlert. Cadastro ganhou estado "Criando conta…" |
| 8 | Mobile realmente usável? | ✅ | Mobile first desde a PR #23 (navegação inferior, "Ver mais" nos gráficos, PWA instalável). Vale um teste de 1 dia usando só o celular |
| 9 | Checkout com atrito? | N/A | Não há cobrança na v1 |
| 10 | Pagamento aprova sem liberar acesso? | N/A | Idem |
| 11 | Cancelamento sem intervenção manual? | N/A / ⚠️ | Sem assinatura. ⚠️ Não existe "excluir minha conta" (só excluir workspace) — necessário antes de abrir para terceiros (LGPD) |
| 12 | Permissões entre usuários bem separadas? | ✅ | Todo SQL filtra por `WorkspaceId` do middleware; escrita exige `GatekeeperMiddleware::requireEditor`; IDs validados no workspace (`ReferenceResolver`); rateio exige permissão no destino; testes de isolamento (`WorkspaceOwnershipTest`, `ReferenceResolverTest`) |
| 13 | Dado sensível no front-end? | 🔧/❌ | Sessão em cookie `HttpOnly`, sem token no `localStorage`, erros internos não vazam mais. ❌ **Produção roda em HTTP puro** (porta 80): senha e cookie trafegam sem criptografia. Ver "Antes de usar" |
| 14 | Erros importantes registrados? | 🔧 | Monolog em `logs/app.log` (JSON, 14 dias) + **GlitchTip (Sentry self-hosted) no Docker** para API, agendador e navegador (`guides/monitoring.md`), inclusive os erros que os controllers capturavam. O usuário vê telas de erro 400/403/404/500/503 com código de referência |
| 15 | Backup do que importa? | 🔧 | Banco: diário + antes de cada deploy, 14 dias. ⚠️ Os backups ficam na própria VPS: copie para fora (rsync/rclone, ver `guides/vps.md`) |
| 16 | Deu errado em produção, como volto? | 🔧 | Deploy falhou → volta sozinho para a última versão boa. Manual: rodar o workflow com o SHA/tag anterior. Banco: `restore.sh` com o backup feito antes do deploy |
| 17 | O produto deixa claro o próximo passo? | ✅ | Toasts após cada ação, checklist inicial, lembretes de contas a vencer, alerta de saldo previsto negativo |
| 18 | Feature deixando tudo confuso? | ⚠️ | **Investimentos** e **Conciliação/Importação (BETA)** são as menos polidas; o menu também mostra Orçamentos, Calendário, Metas, Relatórios e Extrato ao mesmo tempo. Sugestão: na v1, esconder Investimentos/Importação até usar o núcleo por algumas semanas |
| 19 | O que testar antes de colocar tráfego? | — | Roteiro abaixo |
| 20 | Se tivesse que impedir o lançamento hoje, qual o motivo? | ❌ → 🔧 | Era o **phpMyAdmin público com login automático como root** (qualquer pessoa apagava o banco) — corrigido. O que resta: **HTTPS** |

## Antes de usar (ações suas, fora do código)

1. **HTTPS (bloqueante)**: apontar um domínio para a VPS e colocar um proxy com certificado na frente (Caddy ou
   Nginx + Let's Encrypt, ou Cloudflare com "Full (strict)"). Depois: `VITE_API_BASE_URL=https://seu-dominio`,
   `CORS_ALLOWED_ORIGINS=https://seu-dominio` e novo deploy. Sem HTTPS também não há Web Push.
2. **Firewall da VPS**: liberar só 22, 80 e 443. Lembre que portas publicadas pelo Docker ignoram o `ufw` — por isso
   phpMyAdmin e o MySQL de staging agora escutam só em `127.0.0.1`.
3. **Trocar a senha do root do MySQL de produção** (`DB_ROOT_PASSWORD`), já que o phpMyAdmin esteve exposto com ela.
   Se houver qualquer dúvida de acesso indevido, trate o banco como comprometido.
4. **`SENTRY_DSN`** no `.env` da VPS.
5. **Cópia dos backups fora da VPS** e **um teste de restauração** no staging.
6. Proteger a branch `main` com os checks `Quality Gate`, `backend-tests` e `frontend-tests`.

## Roteiro de teste antes de usar de verdade (pergunta 19)

1. Cadastro → login → logout → "esqueci a senha" no celular e no computador.
2. Lançar receita e despesa criando conta e categoria na hora; conferir saldo da conta e Dashboard.
3. Conta a pagar recorrente: pagar, reagendar e desconsiderar; conferir que a próxima ocorrência aparece.
4. Cartão: compra parcelada antes e depois do fechamento; pagar a fatura e ver o limite voltar.
5. Convidar a(o) parceira(o) como **viewer**, confirmar que não consegue editar; depois como editor.
6. Rateio entre dois workspaces (e um rateio inválido: nada pode ficar gravado).
7. Relatório mensal/anual + exportar CSV e PDF.
8. Importar um extrato real do seu banco (PDF/OFX/CSV).
9. Deploy de teste: rodar o workflow, depois rodar de novo com o SHA anterior (rollback) e restaurar um backup no staging.
