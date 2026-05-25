# Implementação: Autenticação

**Prompt de origem:** `.cursor/prompts/1-autenticacao.md`  
**Data de referência:** maio/2026  
**Escopo:** login, logout, sessão segura, recuperação de senha, CSRF e proteção de rotas administrativas/API.

---

## Visão geral

Sistema de autenticação baseado em **sessão PHP** com arquitetura **Controller → Service → Repository**, middleware global de autenticação e validação CSRF em formulários HTML. Não houve inclusão de pacotes Composer externos.

### Fluxo resumido

```
Request → bootstrap (sessão segura)
       → AuthMiddleware (CSRF em POST, auth em rotas protegidas)
       → Router → Controller → Service → Repository → PostgreSQL
```

### Usuário padrão (seed)

| Campo   | Valor                    |
|---------|--------------------------|
| E-mail  | `admin@mini-erp.local`   |
| Senha   | `Admin@123`              |
| Perfil  | `admin`                  |

> Alterar a senha após o primeiro acesso em produção.

---

## Arquivos criados

### Aplicação (PHP)

| Arquivo | Responsabilidade |
|---------|------------------|
| `app/Controllers/AuthController.php` | Telas e ações de login, logout e recuperação de senha |
| `app/Services/AuthService.php` | Regras de negócio de autenticação e reset |
| `app/Repositories/UserRepository.php` | Persistência de usuários e tokens de reset |
| `app/Models/User.php` | Modelo de domínio do usuário |
| `app/Middleware/AuthMiddleware.php` | Guard de rotas, CSRF e redirecionamento |
| `app/Helpers/Auth.php` | Sessão do usuário autenticado (global) |
| `app/Helpers/Csrf.php` | Geração e validação de token CSRF |
| `app/Helpers/Redirect.php` | Redirecionamento centralizado e sanitização de `intended_url` |
| `app/Helpers/AppConfig.php` | Leitura de flags de ambiente (`APP_DEBUG`) |

### Views

| Arquivo | Responsabilidade |
|---------|------------------|
| `app/Views/auth/login.php` | Formulário de login |
| `app/Views/auth/forgot-password.php` | Solicitação de recuperação de senha |
| `app/Views/auth/reset-password.php` | Redefinição de senha com token |
| `app/Views/layouts/auth.php` | Layout Bootstrap para páginas públicas de auth |
| `app/Views/partials/csrf.php` | Campo hidden `_csrf` reutilizável |

### Banco de dados

| Arquivo | Responsabilidade |
|---------|------------------|
| `database/migrations/001_create_users.sql` | Migration idempotente (tabelas + seed admin) |
| `database/run_migration.php` | Script PHP para aplicar migration sem `psql` |

### Documentação

| Arquivo | Responsabilidade |
|---------|------------------|
| `docs/implementacoes/01-autenticacao.md` | Este resumo técnico |

---

## Arquivos alterados

| Arquivo | Alteração |
|---------|-----------|
| `public/index.php` | Rotas de auth + chamada a `AuthMiddleware::handle()` |
| `app/bootstrap.php` | Configuração de sessão segura (`httponly`, `samesite`, etc.) |
| `app/Core/Controller.php` | `redirect()` delegado para `App\Helpers\Redirect` |
| `app/Core/View.php` | Suporte a layout customizável (`layouts/auth`) |
| `config/app.php` | Chave `debug` a partir de `APP_DEBUG` |
| `database/database.sql` | Tabelas `users`, `password_reset_tokens` e seed admin |
| `app/Views/layouts/main.php` | Usuário logado, botão Sair, CSRF no logout |
| `app/Views/customers/form.php` | Campo CSRF |
| `app/Views/customers/index.php` | Campo CSRF no delete |
| `app/Views/products/form.php` | Campo CSRF |
| `app/Views/products/index.php` | Campo CSRF no delete |
| `public/assets/css/app.css` | Estilos da página de autenticação (`.auth-page`, etc.) |
| `README.md` | Documentação de auth, migration e variável `APP_DEBUG` |

---

## Migrations criadas

| Arquivo | Descrição |
|---------|-----------|
| `database/migrations/001_create_users.sql` | Cria `users` e `password_reset_tokens`, índices, constraints e usuário admin |

### Como aplicar

**Instalação nova (schema completo):**

```bash
psql -U postgres -d mini_erp_vendas -f database/database.sql
```

**Banco já existente (apenas auth):**

```bash
php database/run_migration.php
```

---

## Tabelas criadas

### `users`

| Coluna          | Tipo        | Observação                                      |
|-----------------|-------------|-------------------------------------------------|
| `id`            | SERIAL PK   |                                                 |
| `name`          | VARCHAR(255)| NOT NULL                                        |
| `email`         | VARCHAR(255)| UNIQUE, busca case-insensitive via `LOWER()`   |
| `password_hash` | VARCHAR(255)| bcrypt via `password_hash()`                    |
| `role`          | VARCHAR(50) | CHECK: `admin`, `vendedor`, `financeiro`, `estoque` |
| `active`        | BOOLEAN     | DEFAULT `TRUE`                                  |
| `created_at`    | TIMESTAMP   | DEFAULT `CURRENT_TIMESTAMP`                     |
| `updated_at`    | TIMESTAMP   | DEFAULT `CURRENT_TIMESTAMP`                     |

**Índices:** `idx_users_email_lower`, `idx_users_active` (parcial, `active = TRUE`).

### `password_reset_tokens`

| Coluna        | Tipo        | Observação                                |
|---------------|-------------|-------------------------------------------|
| `id`          | SERIAL PK   |                                           |
| `user_id`     | INTEGER FK  | REFERENCES `users(id)` ON DELETE CASCADE  |
| `token_hash`  | VARCHAR(64) | SHA-256 do token em texto (não armazena o token) |
| `expires_at`  | TIMESTAMP   | TTL padrão: 2 horas                       |
| `created_at`  | TIMESTAMP   | DEFAULT `CURRENT_TIMESTAMP`               |

**Índices:** `idx_password_reset_token_hash`, `idx_password_reset_user`.

---

## Rotas criadas

Todas registradas em `public/index.php`.

| Método | Rota               | Handler                         | Acesso   |
|--------|--------------------|---------------------------------|----------|
| GET    | `/login`           | `AuthController@showLogin`      | Público  |
| POST   | `/login`           | `AuthController@login`          | Público  |
| POST   | `/logout`          | `AuthController@logout`         | Autenticado |
| GET    | `/forgot-password` | `AuthController@showForgotPassword` | Público |
| POST   | `/forgot-password` | `AuthController@forgotPassword` | Público  |
| GET    | `/reset-password`  | `AuthController@showResetPassword` | Público (requer `?token=`) |
| POST   | `/reset-password`  | `AuthController@resetPassword`  | Público  |

### Rotas protegidas (comportamento alterado)

Todas as demais rotas existentes passam a exigir sessão autenticada via `AuthMiddleware`, incluindo:

- Painel: `/`, `/customers/*`, `/products/*`, `/orders/*`
- API: `/api/products`, `/api/orders` (GET e POST)

Sem autenticação: redirecionamento para `/login` (HTML) ou `401 JSON` (API).

---

## Dependências adicionadas

**Nenhuma dependência Composer nova.**

Requisitos já existentes no projeto:

| Dependência    | Uso na autenticação                    |
|----------------|----------------------------------------|
| PHP >= 7.4     | `password_hash`, sessões, strict types |
| `ext-pdo`      | Acesso ao banco                        |
| `ext-pdo_pgsql`| PostgreSQL                             |

Extensões PHP utilizadas implicitamente: `json`, `session`, `filter`.

---

## Componentes de segurança

| Recurso | Implementação |
|---------|---------------|
| Hash de senha | `password_hash()` / `password_verify()` (bcrypt) |
| Sessão | `httponly`, `SameSite=Strict`, `use_strict_mode`, `cookie_secure` em HTTPS |
| Regeneração de sessão | No login e no logout |
| CSRF | Token em sessão; validado em todo `POST` HTML (inclui login/recuperação) |
| Open redirect | `Redirect::sanitizeIntendedUrl()` no pós-login |
| Reset de senha | Token aleatório 64 bytes hex; hash SHA-256 no banco; expiração 2h |
| Enumeração de e-mail | Mensagem genérica; link de reset só com `APP_DEBUG=true` |

---

## Variáveis de ambiente

| Variável      | Descrição |
|---------------|-----------|
| `APP_DEBUG`   | `true`: exibe credenciais dev no login e link de reset na tela de recuperação |
| Demais (`DB_*`, `APP_BASE_URL`) | Já existentes; necessárias para funcionamento |

Exemplo em `config/.env` (desenvolvimento):

```env
APP_DEBUG=true
```

---

## API global do usuário autenticado

```php
use App\Helpers\Auth;

Auth::check();           // bool — verifica sessão (sem query)
Auth::id();              // ?int
Auth::user();            // ?User — recarrega do banco e valida active
Auth::sessionSnapshot(); // ?array — dados leves da sessão (layout)
```

---

## Pontos de atenção

### Produção

1. **Alterar senha do admin** imediatamente após deploy; não manter `Admin@123`.
2. **Definir `APP_DEBUG=false`** (ou omitir) para não expor dica de login nem link de reset na interface.
3. **HTTPS obrigatório** para `session.cookie_secure` e proteção do cookie de sessão.
4. **Integrar envio de e-mail** na recuperação de senha — hoje o link só aparece em modo debug; em produção o usuário não recebe e-mail automaticamente.

### Segurança

5. **API REST** (`/api/*`) não valida CSRF; depende de cookie de sessão com `SameSite=Strict`. Requisições cross-site de outros domínios são mitigadas, mas integrações futuras devem considerar tokens de API (prompt 13).
6. **Perfis (`role`)** estão no banco e na sessão, mas **não há RBAC** — qualquer usuário autenticado acessa todos os módulos até implementar permissões (prompt 2).
7. **Token de reset na URL** (`GET /reset-password?token=...`) pode aparecer em logs de servidor/histórico; padrão aceitável, porém sensível.
8. **Sem rate limiting** no login e na recuperação de senha — vulnerável a brute force em ambientes expostos.

### Operacional

9. **Migration idempotente** (`CREATE IF NOT EXISTS`) — segura para reexecução; o seed do admin não duplica e-mail.
10. **`database/run_migration.php`** executa o SQL inteiro via `PDO::exec()` — adequado para este projeto; migrations futuras complexas podem exigir execução statement a statement.
11. **Compatibilidade retroativa:** bancos antigos precisam rodar `php database/run_migration.php` antes de usar o sistema.

### Arquitetura

12. **`Auth::user()`** é invocado pelo middleware em rotas protegidas para invalidar sessão de usuário desativado — gera 1 query por request autenticado (comportamento intencional).
13. **Formulário de vendas** (`orders/create`) envia via `fetch` JSON para `/api/orders` — protegido por sessão, sem CSRF de formulário.

---

## Critérios de aceite (status)

| Critério | Status |
|----------|--------|
| Login funcionando | OK |
| Logout funcionando | OK |
| Sessão persistente | OK |
| Rotas protegidas | OK |
| Usuário autenticado disponível globalmente | OK (`Auth` helper) |
| Código sem duplicações críticas | OK (redirect e CSRF centralizados) |
| Sem SQL em controllers | OK |
| Regras em services | OK |

---

## Referências internas

- Regras: `.cursor/rules/architecture.md`, `coding-standards.md`, `postgres.md`
- Contexto: `.cursor/project-context.md`
- Prompt: `.cursor/prompts/1-autenticacao.md`
