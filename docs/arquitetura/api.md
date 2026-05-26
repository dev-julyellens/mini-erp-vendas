# API REST

## 1. Visão geral do módulo

API JSON sob o mesmo front controller (`public/index.php`), com autenticação JWT ou sessão web, rate limiting, logging estruturado e tratamento de erros dedicado (`ApiErrorHandlerMiddleware`).

## 2. Fluxo funcional

### Autenticação

```
POST /api/auth/login
Body: { "email", "password", "company_id"? }
→ ApiAuthService → JWT + dados do usuário/empresa
```

### Requisições autenticadas

1. `ApiMiddleware` — rate limit + log da requisição
2. `JwtAuthMiddleware` — Bearer token (opcional se já houver sessão)
3. `AuthMiddleware` — sessão (sem CSRF em paths `/api/*`)
4. Demais middlewares (LGPD, onboarding, subscription, access log, permission)
5. Controller → Service → JSON via `ApiResponse`

### Erros

`ApiErrorHandlerMiddleware` captura exceções e retorna JSON padronizado; usa `App\Core\Logger` em falhas críticas.

## 3. Estrutura de banco relacionada

| Tabela | Uso |
|--------|-----|
| `api_logs` | Request/response metadata |
| `api_rate_limit_buckets` | Contadores por chave (IP + rota) |
| Tabelas de domínio | Mesmas da aplicação web |

## 4. Services envolvidos

| Service | Função |
|---------|--------|
| `ApiAuthService` | Login e usuário do token |
| `JwtService` | Token HS256 |
| `ApiLogService` | Persistência de logs |
| `ApiRateLimitService` | Janela deslizante |
| `ApiPayloadService` | Normalização de body JSON |
| `OrderService`, `OrderCancelService` | Vendas via API |
| `ProductService` (via repo) | Listagem de produtos |

## 5. Repositories envolvidos

- `ApiLogRepository`, `ApiRateLimitRepository`
- Repositórios de domínio conforme endpoint

## 6. Controllers envolvidos

| Método | Path | Controller | Ação |
|--------|------|------------|------|
| POST | `/api/auth/login` | `ApiAuthController` | `login` |
| GET | `/api/products` | `ApiProductController` | `index` |
| GET | `/api/orders` | `ApiOrderController` | `index` |
| POST | `/api/orders` | `ApiOrderController` | `store` |
| POST | `/api/orders/cancel` | `ApiOrderController` | `cancel` |

## 7. Regras de negócio

- Mesmas regras de `OrderService` / `OrderCancelService` da interface web.
- ACL via `RoutePermissionMap` (ex.: `POST /api/orders` → `vendas.criar`).
- Rate limits (`config/app.php`):
  - Padrão: 60 req / 60s
  - Login: 10 req / janela
  - Header `Retry-After` em HTTP 429
- Content-Type JSON esperado em POST.
- Cancelamento: body com `order_id` ou `id`.

## 8. Fluxo de dados

```
Client → ApiMiddleware (rate limit, log)
      → JwtAuthMiddleware
      → AuthMiddleware
      → PermissionMiddleware
      → Api*Controller
      → Service
      → ApiResponse::json(...)
```

Helpers: `App\Helpers\ApiRequest`, `App\Helpers\ApiResponse`.

## 9. Pontos críticos

- `JWT_SECRET` deve ser forte em produção.
- Mensagens de validação em inglês nos services; respostas API seguem o mesmo padrão.
- Webhook PIX mock não faz parte da API autenticada — rota pública separada.
- Logs podem conter payloads — revisar retenção e LGPD (`MASK_SENSITIVE_DATA`).

## 10. Dependências

- `config/app.php` — jwt, api rate limits
- `App\Core\ApiException`
- Documentação de implementação: `docs/implementacoes/13-api-segura.md`

## 11. Possíveis melhorias futuras

- OpenAPI/Swagger.
- Versionamento `/api/v1`.
- Scopes OAuth2 ou API keys por integração.
- Paginação cursor-based em `/api/orders`.
- Testes de contrato (Pact).
