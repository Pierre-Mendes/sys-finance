# Convenções do backend (PHP)

## Geral

- PHP 8.4, PSR-4 `App\` → `src/`. Tipos em parâmetros e retornos.
- Identificadores em inglês; comentários e mensagens ao usuário em português.
- Comente o **porquê** (regra de negócio, decisão de segurança), não o óbvio.

## Nova funcionalidade: onde colocar

1. SQL → método no `Repository` (sempre com `WorkspaceId` no `WHERE`).
2. Regra → `Service` (lança `Exception` com mensagem legível em PT-BR).
3. Entrada → `DTO` com normalização no construtor e `isValid()`.
4. HTTP → método no `Controller`.
5. Wiring → instanciar em `public/index.php` e registrar a rota no grupo certo.

## Controllers

- Pegue o contexto dos atributos: `workspaceId`, `userId`, `workspaceRole`. Nunca aceite `workspaceId` do body.
- Formato de resposta:
  ```php
  $response->getBody()->write(json_encode(["success" => true, "data" => $data]));
  return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
  ```
  Erro: `["success" => false, "error" => "mensagem"]` com 400/403/404/415/422.
- Criação responde 201.

## Rotas (`public/index.php`)

- Grupo protegido: `->add($workspaceMiddleware)->add($authMiddleware)` (a ordem é invertida: auth roda primeiro).
- **Toda rota de escrita** (POST/PUT/DELETE) recebe `->add(GatekeeperMiddleware::requireEditor('<módulo>'))`.
  Módulos: `accounts`, `categories`, `transactions`, `budgets`, `goals`, `credit_cards`, `reports`.
- Rotas públicas sensíveis (login, cadastro, recuperação) levam `RateLimiterMiddleware`.

## Services

- Recebem repositórios pelo construtor; não criam PDO.
- Conta/categoria vindas do usuário passam pelo `ReferenceResolver`: ID precisa pertencer ao workspace,
  nome faz buscar-ou-criar. Nunca use IDs fixos (ex.: `categoryId: 1`).
- Operações em outro workspace (rateio) exigem `WorkspaceService::canEdit()` **antes** de persistir.

## DTOs

- Converta tipos (`(int)`, `(float)`), limite enums com `in_array`, remova tags com `strip_tags`.
- Aceite alternativas por nome quando fizer sentido (`accountName`, `categoryName`).

## Erros e logs

- Não exponha stack trace: em produção o `ErrorMiddleware` esconde detalhes.
- Use o `$logger` (Monolog) para falhas inesperadas.

## Referências

- Segurança: [security.md](security.md) · Banco: [database.md](database.md) · Testes: [testing.md](testing.md)
