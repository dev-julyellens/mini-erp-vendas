# Autenticação e sessão

## 1. Visão geral do módulo

Autenticação **web por sessão PHP** e **API por JWT**. Suporta multi-empresa (seleção de tenant após login), reset de senha, política de senha, timeout de sessão, consentimento LGPD e fluxo de onboarding/assinatura antes do uso pleno.

## 2. Fluxo funcional

### Login web

1. `GET/POST /login` → `AuthController`
2. `AuthService::authenticate()` valida e-mail/senha (`password_verify`)
3. `CompanyAuthService::resolvePostCredentialFlow()`:
   - **1 empresa:** `completeLoginWithCompany()` → sessão completa → redirect `/`
   - **N empresas:** `Auth::setPendingUserId()` → `/select-company`
4. `POST /select-company` → `AuthService::selectCompany()` → contexto de empresa
5. Middlewares subsequentes: LGPD → onboarding → assinatura ativa

### API

1. `POST /api/auth/login` → `ApiAuthController` → `ApiAuthService`
2. Retorna JWT (TTL em `config/app.php` → `jwt.ttl`, padrão 3600s)
3. `JwtAuthMiddleware` decodifica Bearer e popula sessão/usuário antes de `AuthMiddleware`

### Logout e reset

- `POST /logout` limpa sessão
- Forgot/reset: token SHA-256 em `password_reset_tokens` (TTL 2h); URL de reset exposta só com `APP_DEBUG=true`

## 3. Estrutura de banco relacionada

| Tabela | Uso |
|--------|-----|
| `users` | Credenciais, `role`, `active` |
| `user_companies` | Empresas do usuário |
| `companies` | Tenant ativo |
| `password_reset_tokens` | Reset de senha |
| `lgpd_consents` | Versão da política aceita |
| `access_logs` | Registro de acessos (middleware) |

## 4. Services envolvidos

| Service | Responsabilidade |
|---------|------------------|
| `AuthService` | Login, logout, reset, seleção de empresa |
| `CompanyAuthService` | Fluxo multi-empresa, `completeLoginWithCompany` |
| `SessionService` | Idle e timeout absoluto de sessão |
| `ApiAuthService` | Login API + resolução de usuário do token |
| `JwtService` | Criação/decodificação JWT |
| `PasswordPolicyService` | Regras de senha |
| `LgpdConsentService` | Consentimento obrigatório |
| `OnboardingService` | Empresa e plano inicial |
| `SubscriptionService` | Assinatura SaaS ativa |

## 5. Repositories envolvidos

- `UserRepository` — usuários e tokens de reset
- `CompanyRepository` — empresas e vínculos
- `LgpdConsentRepository`
- `SubscriptionRepository`, `PlanRepository`

## 6. Controllers envolvidos

| Controller | Rotas |
|------------|-------|
| `AuthController` | `/login`, `/select-company`, `/logout`, `/forgot-password`, `/reset-password` |
| `ApiAuthController` | `POST /api/auth/login` |
| `LgpdController` | `/lgpd/consent` |
| `OnboardingController` | `/onboarding/*` |
| `SubscriptionController` | `/subscription/*` |

## 7. Regras de negócio

- Usuário inativo não autentica.
- CSRF obrigatório em POST web (exceto rotas API e públicas listadas em `AuthMiddleware`).
- Cookie de sessão: `mini_erp_session` com flags seguras (`app/bootstrap.php`).
- Admin (`role=admin`) ignora ACL em `PermissionService`, mas passa pelos middlewares de LGPD/onboarding/assinatura conforme configurado.
- Seed padrão: `admin@mini-erp.local` / `Admin@123` (migration `001`).

## 8. Fluxo de dados

```
Browser → AuthMiddleware (sessão/CSRF/timeout)
       → JwtAuthMiddleware (Bearer opcional)
       → Auth::user() / CompanyContext::requireId()
       → Services/Repositories
```

## 9. Pontos críticos

- `JWT_SECRET` padrão em dev — **alterar em produção** (`config/app.php`).
- Reset de senha **não envia e-mail**; link só visível em debug.
- Rotas públicas definidas em `AuthMiddleware::PUBLIC_ROUTES` (login, webhooks PIX mock, API login).
- API aceita sessão web **ou** JWT na mesma rota protegida.

## 10. Dependências

- `app/bootstrap.php` — sessão, autoload, `.env`
- `App\Helpers\Auth`, `App\Helpers\Csrf`, `App\Helpers\CompanyContext`
- Middlewares: `AuthMiddleware`, `JwtAuthMiddleware`, `LgpdMiddleware`, `OnboardingMiddleware`, `SubscriptionMiddleware`

## 11. Possíveis melhorias futuras

- Integração com SMTP para reset de senha.
- Refresh token para API.
- 2FA para perfis administrativos.
- Rate limit no login web (hoje só API login tem limite dedicado).
