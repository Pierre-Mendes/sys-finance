#!/usr/bin/env bash
# Checagens de DAST da sessão (OWASP ASVS V3 / V4): roda contra a app no ar e falha o job se algo regredir.
# Também exporta o cookie de sessão para o ZAP varrer as telas autenticadas (ZAP_AUTH_HEADER*).
#   uso: .github/dast/session-checks.sh http://localhost:8080
set -euo pipefail
BASE="${1:-http://localhost:8080}"
TMP="$(mktemp -d)"
EMAIL="dast$(date +%s)@example.com"
PASS='Dast@12345'
fail() { echo "::error::$1"; exit 1; }
ok() { echo "OK  $1"; }

curl -sS -o /dev/null -X POST "$BASE/api/auth/signup" -H 'Content-Type: application/json' \
  -d "{\"firstName\":\"Dast\",\"lastName\":\"Bot\",\"email\":\"$EMAIL\",\"password\":\"$PASS\",\"securityQuestion\":\"q\",\"securityAnswer\":\"a\"}"

curl -sS -D "$TMP/h" -o "$TMP/b" -c "$TMP/jar" -X POST "$BASE/api/auth/login" -H 'Content-Type: application/json' \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASS\"}"

SESSION_LINE="$(grep -i '^set-cookie: sf_session=' "$TMP/h" || true)"
[ -n "$SESSION_LINE" ] || fail "Login não definiu o cookie sf_session"
echo "$SESSION_LINE" | grep -qi 'httponly' || fail "Cookie de sessão sem HttpOnly"
echo "$SESSION_LINE" | grep -qi 'samesite=\(strict\|lax\)' || fail "Cookie de sessão sem SameSite=Strict/Lax"
ok "cookie de sessão HttpOnly + SameSite"
grep -q '"token"' "$TMP/b" && fail "Resposta do login expõe o token ao JavaScript"
ok "login não devolve o token no corpo"

CSRF="$(awk '$6=="sf_csrf"{print $7}' "$TMP/jar")"
[ -n "$CSRF" ] || fail "Login não definiu o cookie sf_csrf"

BODY='{"name":"Conta DAST"}'
code=$(curl -sS -o /dev/null -w '%{http_code}' -b "$TMP/jar" -X POST "$BASE/api/accounts" -H 'Content-Type: application/json' -d "$BODY")
[ "$code" = "403" ] || fail "Escrita sem header CSRF deveria ser 403 (veio $code)"
ok "escrita sem CSRF recusada (403)"
code=$(curl -sS -o /dev/null -w '%{http_code}' -b "$TMP/jar" -X POST "$BASE/api/accounts" -H 'Content-Type: application/json' -H "X-CSRF-Token: $CSRF" -d "$BODY")
[[ "$code" =~ ^20[01]$ ]] || fail "Escrita com CSRF válido deveria passar (veio $code)"
ok "escrita com CSRF aceita ($code)"

code=$(curl -sS -o /dev/null -w '%{http_code}' -b 'sf_session=forjado' "$BASE/api/auth/me")
[ "$code" = "401" ] || fail "Sessão forjada deveria ser 401 (veio $code)"
ok "sessão forjada recusada (401)"

SESSION_VALUE="$(awk '$6=="sf_session"{print $7}' "$TMP/jar")"
if [ -n "${GITHUB_ENV:-}" ]; then
  {
    echo "ZAP_AUTH_HEADER=Cookie"
    echo "ZAP_AUTH_HEADER_VALUE=sf_session=$SESSION_VALUE; sf_csrf=$CSRF"
    echo "ZAP_AUTH_HEADER_SITE=localhost"
  } >> "$GITHUB_ENV"
  echo "::add-mask::$SESSION_VALUE"
  ok "cookie exportado para o ZAP varrer as rotas autenticadas"
fi

curl -sS -D "$TMP/lh" -o /dev/null -b "$TMP/jar" -X POST "$BASE/api/auth/logout"
grep -i '^set-cookie: sf_session=' "$TMP/lh" | grep -qi 'max-age=0' || fail "Logout não apagou o cookie de sessão"
ok "logout apaga o cookie"
