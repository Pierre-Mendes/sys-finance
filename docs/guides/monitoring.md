# Monitoramento de erros (Sentry / GlitchTip no Docker)

O projeto envia os erros da **API (PHP)**, do **agendador de lembretes** e do **navegador (Vue)** para um servidor
compatível com o Sentry. Recomendado: **GlitchTip self-hosted** (`docker-compose.monitoring.yml`).

| | Sentry self-hosted | GlitchTip (usado aqui) | Sentry.io (SaaS) |
|---|---|---|---|
| RAM | ~16 GB, 20+ containers | ~512 MB, 2 containers | — |
| Custo | VPS grande | grátis, na mesma VPS | grátis até 5 mil erros/mês |
| SDK/DSN | Sentry | **os mesmos do Sentry** | Sentry |

Trocar de um para outro é só trocar o DSN no `.env`.

## Como os erros chegam

```
Navegador (Vue) ──POST /api/monitoring/sentry──▶ API (túnel) ──rede interna──▶ glitchtip:8000
API / agendador (PHP) ─────────────────────────────────────── rede interna──▶ glitchtip:8000
```

- O GlitchTip **não fica exposto na internet**: escuta só em `127.0.0.1:8000` e na rede interna do Docker.
- O navegador fala só com a própria API (túnel): sem domínio extra na CSP e sem bloqueio por adblock.
  O túnel só aceita eventos do `SENTRY_FRONTEND_DSN` e o destino vem da configuração (não vira proxy aberto).
- Erros internos da API aparecem para o usuário como "código para o suporte: `1a2b3c4d`"; procure esse
  código na tag `ref` do evento no GlitchTip.

## Passo a passo na VPS

1. No `.env` da VPS (veja `.env.example`):
   ```env
   GLITCHTIP_SECRET_KEY=   # openssl rand -hex 32
   GLITCHTIP_DB_PASSWORD=  # openssl rand -hex 16
   ```
2. Subir o GlitchTip (o deploy de produção passa a incluí-lo sozinho quando `GLITCHTIP_SECRET_KEY` está preenchido):
   ```bash
   docker compose -f docker-compose.prod.yml -f docker-compose.monitoring.yml up -d glitchtip-db glitchtip
   docker exec -it financas-glitchtip ./manage.py createsuperuser   # seu usuário de acesso
   ```
3. Abrir o painel por túnel SSH, do seu computador:
   ```bash
   ssh -L 8000:127.0.0.1:8000 usuario@vps     # depois abra http://localhost:8000
   ```
4. No painel: crie uma organização e **dois projetos**: `api` (plataforma PHP) e `web` (plataforma Vue).
   Cada projeto mostra um DSN como `http://CHAVE@localhost:8000/1`.
5. Troque `localhost:8000` por `glitchtip:8000` (nome do container na rede interna) e grave no `.env`:
   ```env
   SENTRY_DSN=http://CHAVE_API@glitchtip:8000/1
   SENTRY_FRONTEND_DSN=http://CHAVE_WEB@glitchtip:8000/2
   ```
6. Rode o deploy de produção (o DSN do frontend entra no build).
7. Teste: abra `https://seu-dominio/erro/500` não gera evento (é só a tela). Para um evento real, rode na VPS
   `docker exec financas-web-prod php -r 'require "vendor/autoload.php"; Sentry\init(["dsn"=>getenv("SENTRY_DSN")]); Sentry\captureMessage("teste");'`
   e confira no projeto `api`.

**Alertas**: em cada projeto, *Settings → Alerts* (e-mail exige `GLITCHTIP_EMAIL_URL`; também há webhook,
por exemplo para um canal do Telegram/Discord/Slack).

## Desenvolvimento local

```bash
GLITCHTIP_SECRET_KEY=dev docker compose -f docker-compose.yml -f docker-compose.monitoring.yml up
```

Mesmo fluxo: `createsuperuser`, projetos em http://localhost:8000 e DSNs com host `glitchtip:8000` no `.env`.

## Variáveis

| Variável | Onde | Padrão |
|---|---|---|
| `SENTRY_DSN` | API + agendador | vazio (desligado) |
| `SENTRY_FRONTEND_DSN` | build do Vue + validação do túnel | vazio (desligado) |
| `SENTRY_TRACES_SAMPLE_RATE` | API | `0.1` (10% das requisições com medição de desempenho) |
| `APP_RELEASE` | API | vazio (ex.: SHA do commit, para saber em qual versão o erro apareceu) |
| `GLITCHTIP_RETENTION_DAYS` | GlitchTip | `30` |
| `GLITCHTIP_OPEN_REGISTRATION` | GlitchTip | `false` (contas só por `createsuperuser`) |

Backup: o banco do GlitchTip (`glitchtip-db-data`) guarda só eventos de erro; não entra no backup diário do app.
