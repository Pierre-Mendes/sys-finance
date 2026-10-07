# Bot do Telegram

Lance receitas e despesas escrevendo como numa conversa e consulte saldo, resumo do mês e contas a pagar.
Os lembretes de contas a vencer também chegam no Telegram de quem vinculou o bot. É gratuito.

## O que o bot faz

| Mensagem | Resultado |
|---|---|
| `mercado 120 nubank` | Despesa de R$ 120,00 "Mercado", conta Nubank, categoria Alimentação, hoje |
| `gastei 35,90 no almoço ontem` | Despesa com data de ontem |
| `recebi 3000 salário` ou `+3000 salário` | Receita (palavras como *recebi, salário, reembolso, venda* ou o sinal `+`) |
| `uber 25 05/10` | Data informada (dd/mm ou dd/mm/aaaa) |
| `/gasto pix 50` · `/receita pix 50` | Força o tipo |
| `/saldo` | Saldo de cada conta e total |
| `/mes` | Receitas, despesas, resultado e maiores gastos do mês |
| `/contas` | Contas a pagar nos próximos 7 dias (e atrasadas) |
| `/ultimos` | Últimos 5 lançamentos |
| `/desfazer` | Apaga o último lançamento feito pelo bot (até 24h) |
| `/espaco` · `/espaco 2` | Lista / troca o espaço (workspace) usado pelo bot |
| `/desvincular` | Desconecta o Telegram |

**Conta**: a que for citada na mensagem (pelo nome cadastrado, sem diferenciar acento/maiúscula). Bancos comuns
(Nubank, Inter, Itaú, Bradesco, Caixa, C6, PicPay, Mercado Pago…) são criados na hora se ainda não existirem.
Sem conta citada: a conta "Carteira/Dinheiro" se houver, senão a primeira em ordem alfabética.

**Categoria**: a categoria cadastrada citada na mensagem; senão uma sugestão por palavra-chave
(mercado → Alimentação, uber → Transporte, aluguel → Moradia, farmácia → Saúde…); senão "Outros".
Tudo pode ser corrigido depois pelo app.

**Permissões**: o bot respeita as mesmas regras do app. Quem é só leitor no espaço consegue consultar, mas não lançar.
Só responde em conversa privada (nunca em grupos) e só para quem vinculou a conta.

## Como ativar (uma vez, por quem administra o servidor)

Pré-requisito: o sistema precisa estar em **HTTPS** com domínio (o Telegram só entrega mensagens em HTTPS).

1. No Telegram, fale com **@BotFather** → `/newbot` → escolha nome e usuário (terminando em `bot`).
   Guarde o **token**.
2. No `.env` da VPS:
   ```env
   TELEGRAM_BOT_TOKEN=123456:ABC...
   TELEGRAM_BOT_USERNAME=meu_financeiro_bot
   APP_URL=https://financas.seudominio.com.br
   ```
3. Rode o deploy de produção (ou `docker compose -f docker-compose.prod.yml up -d`) para o container ler as variáveis.
4. Registre o webhook e o menu de comandos:
   ```bash
   docker exec financas-web-prod php bin/telegram-setup.php https://financas.seudominio.com.br
   docker exec financas-web-prod php bin/telegram-setup.php --info   # conferir (pending_update_count, last_error_message)
   ```

O webhook é protegido por um segredo (`X-Telegram-Bot-Api-Secret-Token`) gerado e guardado no banco (`app_secrets`);
requisições sem ele recebem 401. Para definir o seu: `TELEGRAM_WEBHOOK_SECRET` (e rode o setup de novo).

Staging não recebe o token: um bot só tem um webhook, o de produção.

## Como cada usuário conecta

App → **Configurações** → **Telegram** → **Conectar Telegram** → **Abrir no Telegram** → **Iniciar**.
O código vale 15 minutos e só pode ser usado uma vez. Em outro aparelho, envie ao bot `/start CÓDIGO`.

## Problemas comuns

| Sintoma | Causa provável |
|---|---|
| Bot não responde | Webhook não registrado ou sem HTTPS: rode `telegram-setup.php --info` e veja `last_error_message` |
| "Configurações → Telegram" diz que o bot não foi configurado | `TELEGRAM_BOT_TOKEN`/`TELEGRAM_BOT_USERNAME` ausentes no container |
| "Código inválido ou expirado" | Passaram 15 min ou o código já foi usado: gere outro |
| Lembretes não chegam no Telegram | O agendador (`financas-scheduler-prod`) também precisa do `TELEGRAM_BOT_TOKEN` (já incluso no compose) |
