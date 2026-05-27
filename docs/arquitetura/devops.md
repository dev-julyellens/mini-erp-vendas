# DevOps, ambiente e deploy

## 1. Visão geral

O projeto roda como aplicação PHP tradicional (Apache/nginx + PHP-FPM), sem container obrigatório. Qualidade local e em CI via `composer check`.

## 2. Variáveis de ambiente

Arquivo: `config/.env` (modelo em `config/.env.example`). Carregado por `App\Core\Env::load()` em `app/bootstrap.php`.

| Variável | Produção | Desenvolvimento |
|----------|----------|-----------------|
| `APP_ENV` | `production` | `local` |
| `APP_DEBUG` | `false` | `true` |
| `JWT_SECRET` | Obrigatório (≥ 32 chars, aleatório) | Pode usar valor longo no `.env` |
| `MAIL_DRIVER` | `smtp` recomendado | `log` grava em `storage/logs/mail.log` |
| `PIX_WEBHOOK_SECRET` | Obrigatório se usar PIX | Opcional em mock |
| `PIX_MOCK_WEBHOOK_ENABLED` | `false` | Só `true` em staging se necessário |

### Validação no boot

`App\Core\SecurityBootstrap::assertSafeConfiguration()`:

- Bloqueia `APP_DEBUG=true` com `APP_ENV=production`
- Exige `JWT_SECRET` forte quando `APP_DEBUG=false`
- Usa `Env::get()` (`.env` prevalece sobre defaults do Apache via `load()` do Dotenv)

## 3. Composer e lockfile

```bash
composer install          # respeita composer.lock
composer update           # atualiza lock (commitar após testar)
composer check            # encoding + PHPStan + PHPUnit
```

O `composer.lock` deve ser versionado para CI e deploys reproduzíveis.

## 4. CI (GitHub Actions)

Workflow: `.github/workflows/quality.yml`

- Trigger: push/PR em `main` ou `master`
- PHP 8.2, extensões `pdo`, `json`, `mbstring`, `gd`
- Comando: `composer check`

Variáveis de ambiente no job definem `APP_DEBUG=true` e `JWT_SECRET` de teste (PHPUnit não carrega `SecurityBootstrap` em modo web).

Workflow adicional: `.github/workflows/integration.yml`

- Serviço PostgreSQL 16
- Importa `database/database.sql` + `php database/run_migration.php`
- Executa `composer test:integration`

Localmente, testes de integração usam `config/.env` e fazem `markTestSkipped` se o banco não estiver acessível.

## 5. Scripts em `bin/`

| Script | Função |
|--------|--------|
| `bin/check-encoding.php` | Detecta UTF-8 BOM em arquivos rastreados |
| `bin/migrate` | Wrapper de `database/run_migration.php` |

## 6. Migrations

```bash
php bin/migrate
```

- Registra versões em `schema_migrations`
- `bootstrapAppliedMigrations()` marca migrations 001–020 já presentes em bancos legados (primeira execução com tabela vazia)
- Migrations são idempotentes (`IF NOT EXISTS` onde possível)

Sincronização `database.sql` ↔ migrations: ver seção em [banco.md](banco.md#12-sincronização-databasesql).

## 7. Deploy em produção (checklist)

1. `composer install --no-dev --optimize-autoloader`
2. `cp config/.env.example config/.env` e preencher secrets
3. `APP_ENV=production`, `APP_DEBUG=false`, `JWT_SECRET` forte
4. `php bin/migrate`
5. Document root → `public/` apenas
6. Permissões de escrita: `storage/logs`, `storage/backups`, `storage/avatars`
7. HTTPS no reverse proxy
8. **HSTS** — enviado pelo `SecurityHeadersMiddleware` quando `APP_ENV=production` e HTTPS (ou `X-Forwarded-Proto: https`)
9. Remover credenciais padrão do admin
10. `MAIL_DRIVER=smtp` com credenciais reais

## 8. Headers de segurança

`App\Middleware\SecurityHeadersMiddleware`: CSP, `X-Frame-Options`, `Referrer-Policy`, etc.

- `script-src` sem `'unsafe-inline'` — tema inicial em `public/assets/js/theme-boot.js`
- `style-src` ainda permite `'unsafe-inline'` (Bootstrap/componentes)
- Em produção HTTPS: `upgrade-insecure-requests`

## 9. Logs

- Aplicação: `storage/logs/app.log` (Monolog, nível via `LOG_LEVEL`)
- E-mail em dev: `storage/logs/mail.log` quando `MAIL_DRIVER=log`

## 10. Backup

Configuração via `BACKUP_*` no `.env`. Restauração é destrutiva — exige confirmação `RESTAURAR` e perfil autorizado.

## 11. Dependências externas

- PostgreSQL acessível pela aplicação
- SMTP (produção) para reset de senha
- `pg_dump` / `psql` no PATH para backup (opcional, configurável)
