# 🤖 Guia de Configuração: Bot do Telegram

Siga estes passos para ativar seu assistente financeiro no Telegram. Esta integração é **totalmente gratuita**.

## 1. Criar o Bot
1. No seu Telegram, procure pelo usuário **@BotFather** (o bot oficial do Telegram para criar outros bots).
2. Envie o comando `/newbot`.
3. Escolha um **nome** para o seu assistente (ex: *Meu Gestor Financeiro*).
4. Escolha um **username** único que deve terminar em `bot` (ex: *salatiel_finance_bot*).
5. O @BotFather enviará uma mensagem com o seu **API Token**.

## 2. Configurar no Sistema
1. Abra o arquivo `.env` na raiz do projeto.
2. Adicione ou edite a linha:
   ```env
   TELEGRAM_BOT_TOKEN=seu_token_aqui
   ```
3. Salve o arquivo.

## 3. Ativar o Webhook (Opcional)
Assim que você configurar o token, o sistema tentará enviar automaticamente a URL de Webhook para o Telegram. Isso permite que o sistema receba suas mensagens instantaneamente.

---

## 💡 O que você poderá fazer?
- **Lançamentos rápidos**: Digite *"Gastei 50 no almoço"* e a IA classificará automaticamente.
- **Consulta de saúde**: Pergunte *"Quanto gastei esse mês?"* e receba um resumo.
- **Alertas de virada de mês**: Receba notificações automáticas no dia 01 com o resumo do mês anterior e as projeções do mês atual.
