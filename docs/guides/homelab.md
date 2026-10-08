# Homelab com Tailscale

Como colocar o sysfinance no ar num servidor de casa, acessível só pelos aparelhos da sua **tailnet** (você e a
família), com HTTPS de verdade e sem abrir nenhuma porta no roteador.

```
celular/notebook (Tailscale) ──HTTPS──> tailscale serve (no servidor) ──> 127.0.0.1:8080 ──> container web
Telegram (opcional) ──HTTPS:8443──> tailscale funnel (só /api/telegram/webhook) ──┘
```

## 1. Pré-requisitos

- Servidor com Docker e Docker Compose, e o Tailscale instalado e logado (`tailscale status`).
- No [painel do Tailscale](https://login.tailscale.com/admin/dns): **MagicDNS** e **HTTPS Certificates** ligados.
  O endereço do app será `https://<nome-da-máquina>.<sua-tailnet>.ts.net` (veja com `tailscale status --json | grep DNSName`).
  Dica: renomeie a máquina para algo como `financas` em *Machines*.

## 2. Código e `.env`

```bash
git clone https://github.com/Pierre-Mendes/sys-finance.git ~/sys-finance
cd ~/sys-finance
cp .env.example .env
```

Edite o `.env` (troque `financas.tail1234.ts.net` pelo seu endereço):

```bash
# senhas: gere com  openssl rand -base64 24
DB_ROOT_PASSWORD=...
DB_PASS=...

WEB_BIND=127.0.0.1:8080                 # só o tailscale serve alcança o app; nada exposto na rede local
TRUSTED_PROXIES=127.0.0.1,172.16.0.0/12 # limite de login por pessoa, não por proxy
COOKIE_SECURE=true                      # sessão só via HTTPS

VITE_API_BASE_URL=https://financas.tail1234.ts.net
CORS_ALLOWED_ORIGINS=https://financas.tail1234.ts.net
APP_TIMEZONE=America/Sao_Paulo
```

`VITE_API_BASE_URL` entra no build do frontend: se mudar, rode o deploy de novo.

## 3. Subir (e atualizar)

```bash
scripts/deploy.sh          # publica o main
scripts/deploy.sh <sha>    # publica uma versão específica (ou volta para a anterior)
```

O script faz backup do banco, build, migrations e confere a saúde do app. Se algo falhar, volta o código
sozinho para a última versão boa e mostra o comando para restaurar o backup feito antes.

## 4. HTTPS pela tailnet

```bash
sudo tailscale serve --bg http://127.0.0.1:8080
tailscale serve status
```

Abra `https://financas.tail1234.ts.net` num aparelho da tailnet e crie sua conta. O certificado é emitido e
renovado pelo Tailscale. A configuração do `serve` sobrevive a reinicializações.

## 5. Família

- Cada pessoa instala o Tailscale no celular e entra na sua tailnet (convite em *Users*), ou você compartilha só
  esta máquina com ela (*Machines → Share*), sem dar acesso ao resto da rede.
- No celular, abra o endereço e use "Adicionar à tela inicial": o app vira PWA.
- **Notificações (Web Push)** funcionam: o HTTPS do `ts.net` é válido. O aviso chega pelo serviço de push do
  Apple/Google mesmo com o Tailscale desligado; para abrir o app, o Tailscale precisa estar ligado.

## 6. Bot do Telegram (opcional)

O Telegram precisa alcançar o servidor pela internet. Com o **Funnel**, só a rota do webhook fica pública, na porta
8443; o app continua só na tailnet. O webhook recusa qualquer chamada sem o token secreto registrado no Telegram.

1. Libere o Funnel para a máquina na política da tailnet (*Access controls*):
   ```json
   "nodeAttrs": [{ "target": ["autogroup:member"], "attr": ["funnel"] }]
   ```
2. Exponha só o webhook:
   ```bash
   sudo tailscale funnel --bg --https=8443 --set-path=/api/telegram/webhook http://127.0.0.1:8080/api/telegram/webhook
   tailscale funnel status
   ```
3. No `.env`: `TELEGRAM_BOT_TOKEN`, `TELEGRAM_BOT_USERNAME` e `APP_URL=https://financas.tail1234.ts.net:8443`;
   rode `scripts/deploy.sh` e registre o webhook:
   ```bash
   docker exec financas-web-prod php bin/telegram-setup.php https://financas.tail1234.ts.net:8443
   docker exec financas-web-prod php bin/telegram-setup.php --info   # confira se não há "last_error_message"
   ```

Detalhes do bot (vincular a conta, comandos): [telegram.md](telegram.md).

## 7. Backups fora do servidor

O backup diário (14 dias) fica em `~/sys-finance/backups`, **no mesmo disco do banco**. Copie para outro lugar,
por exemplo para um NAS ou nuvem com `rclone`, no `crontab -e`:

```cron
30 3 * * * rclone copy ~/sys-finance/backups remoto:sysfinance-backups --max-age 48h
```

Teste uma restauração ao menos uma vez (veja "Backup e restauração" em [vps.md](vps.md)).

## 8. Administração

- phpMyAdmin só sob demanda e só em `127.0.0.1:8084`: `docker compose -f docker-compose.prod.yml --profile admin up -d phpmyadmin`
  e acesse por túnel (`ssh -L 8084:127.0.0.1:8084 servidor`). Desligue depois (`docker rm -f financas-pma-prod`).
- Logs: `docker compose -f docker-compose.prod.yml logs -f web` e `logs/app.log`.
- Erros com GlitchTip no próprio servidor: [monitoring.md](monitoring.md).
- O workflow "Deploy to Production" do GitHub entra por SSH e não alcança um homelab sem porta aberta. Use o
  `scripts/deploy.sh` no servidor (é o mesmo roteiro que o workflow executa).
