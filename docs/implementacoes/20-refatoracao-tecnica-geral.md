# Refatoração técnica geral (prompt 20)

## Objetivo

Estabilizar a base do projeto com ferramentas de qualidade, logging estruturado e padrões reutilizáveis, **sem remover funcionalidades**.

## Ferramentas adicionadas

| Ferramenta | Uso |
|------------|-----|
| **vlucas/phpdotenv** | Carregamento de `config/.env` via `App\Core\Env` (fallback legado se Composer ausente) |
| **monolog/monolog** | Logs em `App\Core\Logger` → `storage/logs/app.log` |
| **phpunit/phpunit** | Testes em `tests/Unit` — `composer test` |
| **phpstan/phpstan** | Análise estática nível 5 — `composer analyse` |
| **composer check** | Executa análise + testes |

## Melhorias na camada Core

- **`Database::transaction()`** — commit/rollback automático; suporta callbacks aninhados.
- **`App\Core\Logger`** — substitui `error_log` nos handlers críticos (API).
- **`AppException` / `NotFoundException`** — hierarquia de erros de domínio.
- **`BaseRepository`** — PDO injetável; `CustomerRepository` já estende (modelo para demais repos).
- **`App\Helpers\Validator`** — validações DRY; usado em `CustomerService`.
- **`WebExceptionHandlerMiddleware`** — exceções não tratadas na web (limite de plano, 500, validação em debug).

## Configuração

Variáveis opcionais em `.env`:

```env
LOG_PATH=
LOG_LEVEL=warning
```

## Próximos passos (incrementais)

1. Migrar repositórios restantes para `BaseRepository`.
2. Adotar `Database::transaction()` nos services com blocos try/commit/rollback duplicados.
3. Substituir `error_log` remanescente por `Logger`.
4. Ampliar cobertura de testes (services com mocks de PDO).
5. Subir nível do PHPStan gradualmente.

## Comandos

```bash
composer install
composer test
composer analyse
composer check
```
