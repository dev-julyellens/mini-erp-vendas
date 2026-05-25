# Implementação: API segura

**Prompt de origem:** `.cursor/prompts/13-api-segura.md`  
**Data de referência:** maio/2026  
**Escopo:** autenticação JWT, middlewares, rate limit, logs de API, tratamento de erros e padronização JSON — mantendo compatibilidade com sessão PHP nos endpoints existentes.

---

## Visão geral

A API REST passou a contar com camada de segurança dedicada. Endpoints existentes (`/api/products`, `/api/orders`, `/api/orders/cancel`) **continuam funcionando com cookie de sessão** (fluxo do front-end). Integrações externas podem autenticar via **JWT Bearer token**.

### Fluxo resumido

```
Request /api/*
  → ApiErrorHandlerMiddleware (exceções → JSON)
  → ApiMiddleware (rate limit + log de requisição)
  → JwtAuthMiddleware (Authorization: Bearer)
  → AuthMiddleware (sessão ou JWT)
  → PermissionMiddleware (ACL)
  → Router → Controller → Service → Repository
  → shutdown: atualiza status_code no api_logs
```

---

## Autenticação JWT

### Obter token

`POST /api/auth/login` (público, sem autenticação)

```json
{
  "email": "admin@mini-erp.local",
  "password": "Admin@123"
}
```

Resposta:

```json
{
  "success": true,
  "data": {
    "token": "<jwt>",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "name": "Administrador",
      "email": "admin@mini-erp.local",
      "role": "admin"
    }
  }
}
```

### Usar token

Incluir em requisições protegidas:

```
Authorization: Bearer <jwt>
```

### Compatibilidade

- Sessão PHP (cookie `mini_erp_session`) continua válida para todos os endpoints existentes.
- JWT e sessão são aceitos; JWT tem prioridade quando o header `Authorization` está presente.

---

## Rate limit

- Padrão: **60 requisições por minuto** por IP + método + endpoint.
- Configurável via `.env`:
  - `API_RATE_LIMIT` (padrão: 60)
  - `API_RATE_LIMIT_WINDOW` (segundos, padrão: 60)
- `POST /api/auth/login` não entra no rate limit global (evita bloqueio no login).
- Resposta HTTP **429** com header `Retry-After`.

---

## Logs de API

Tabela `api_logs` registra:

| Campo | Descrição |
|-------|-----------|
| `user_id` | Usuário autenticado (sessão ou JWT) |
| `ip_address` | IP do cliente |
| `http_method` | GET, POST, etc. |
| `endpoint` | Caminho da rota |
| `payload` | Body JSON sanitizado (senhas/tokens redigidos) |
| `status_code` | Código HTTP da resposta |
| `created_at` | Timestamp |

---

## Padronização JSON

### Sucesso (novos endpoints)

```json
{ "success": true, "data": { ... }, "meta": { ... } }
```

### Erro

```json
{ "success": false, "message": "...", "errors": { "campo": "..." } }
```

Endpoints legados mantêm formato anterior (`data`, `meta`, `success`, `errors`) para **não quebrar integrações**.

---

## Variáveis de ambiente

| Variável | Descrição | Padrão |
|----------|-----------|--------|
| `JWT_SECRET` | Chave HMAC para assinatura JWT | *(dev only — alterar em produção)* |
| `JWT_TTL` | Validade do token em segundos | 3600 |
| `API_RATE_LIMIT` | Máximo de requisições por janela | 60 |
| `API_RATE_LIMIT_WINDOW` | Janela do rate limit em segundos | 60 |

---

## Arquivos criados

| Arquivo | Responsabilidade |
|---------|------------------|
| `database/migrations/011_create_api_security.sql` | Tabelas `api_logs` e `api_rate_limit_buckets` |
| `app/Services/JwtService.php` | Geração e validação JWT (HS256) |
| `app/Services/ApiAuthService.php` | Login API e resolução de usuário via token |
| `app/Services/ApiLogService.php` | Registro de logs de requisição |
| `app/Services/ApiRateLimitService.php` | Regras de rate limit |
| `app/Services/ApiPayloadService.php` | Validação de payload JSON |
| `app/Repositories/ApiLogRepository.php` | Persistência de logs |
| `app/Repositories/ApiRateLimitRepository.php` | Buckets de rate limit |
| `app/Middleware/ApiMiddleware.php` | Rate limit + início/fim de log |
| `app/Middleware/JwtAuthMiddleware.php` | Autenticação Bearer |
| `app/Middleware/ApiErrorHandlerMiddleware.php` | Exceções → JSON |
| `app/Helpers/ApiResponse.php` | Respostas JSON padronizadas |
| `app/Helpers/ApiRequest.php` | IP, Bearer token, body JSON, sanitização |
| `app/Controllers/ApiAuthController.php` | `POST /api/auth/login` |
| `app/Core/ApiException.php` | Exceção HTTP para API |

---

## Arquivos alterados

| Arquivo | Alteração |
|---------|-----------|
| `config/app.php` | Configuração JWT e rate limit |
| `public/index.php` | Rota de login API + cadeia de middlewares |
| `app/Helpers/Auth.php` | Suporte a usuário via JWT (`setJwtUser`) |
| `app/Middleware/AuthMiddleware.php` | Rota pública de login API + `ApiResponse` |
| `app/Middleware/PermissionMiddleware.php` | Respostas JSON via `ApiResponse` |
| `app/Core/Router.php` | 404/500 em JSON para rotas `/api/*` |
| `app/Controllers/ApiOrderController.php` | Validação de payload via `ApiPayloadService` |

---

## Migration

```bash
php database/run_migration.php
```

---

## Testes manuais sugeridos

1. **Login JWT:** `POST /api/auth/login` com credenciais válidas → receber token.
2. **Endpoint protegido:** `GET /api/products` com `Authorization: Bearer <token>` → lista produtos.
3. **Sessão legada:** criar pedido via front-end (`order_create.js`) → continua funcionando com cookie.
4. **Sem auth:** `GET /api/products` sem token/sessão → 401 JSON.
5. **Rate limit:** exceder limite configurado → 429 com `Retry-After`.
6. **Logs:** verificar registros em `api_logs` após requisições.
