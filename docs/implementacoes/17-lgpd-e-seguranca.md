# Implementação: LGPD e segurança

**Prompt de origem:** `.cursor/prompts/17-LGPD-e-seguranca.md`  
**Data de referência:** maio/2026  
**Escopo:** política de senha, expiração de sessão, logs de acesso, proteção contra SQL injection, sanitização, headers de segurança, mascaramento de dados sensíveis e consentimento LGPD.

---

## Visão geral

Camada de segurança reforçada para uso em produção, mantendo a arquitetura **Controller → Service → Repository** e compatibilidade com módulos existentes.

### Fluxo resumido

```
Request → SecurityHeadersMiddleware
       → AuthMiddleware (CSRF, autenticação, expiração de sessão)
       → LgpdMiddleware (consentimento pendente)
       → AccessLogMiddleware (registro assíncrono via shutdown)
       → PermissionMiddleware → Controller
```

---

## Recursos implementados

| Recurso | Implementação |
|---------|---------------|
| Política de senha | `PasswordPolicyService` — mínimo configurável, maiúscula, minúscula, número e especial |
| Expiração de sessão | `SessionService` — idle (padrão 30 min) e absoluto (padrão 8 h) |
| Logs de acesso | Tabela `access_logs`, tela `/access-logs` (perfil `usuarios.visualizar`) |
| SQL Injection | Prepared statements + `PDO::ATTR_EMULATE_PREPARES => false` |
| Sanitização | `InputSanitizer` em cadastros (ex.: clientes) |
| Headers de segurança | `SecurityHeadersMiddleware` (CSP, X-Frame-Options, etc.) |
| Dados sensíveis | `DataMask` em listagens de clientes; redação em logs de API |
| Consentimento LGPD | Tela `/lgpd/consent`, tabela `lgpd_consents`, middleware obrigatório |
| Uploads | `FileUploadValidator` (utilitário para futuros formulários com arquivo) |

---

## Migration

| Arquivo | Descrição |
|---------|-----------|
| `database/migrations/015_lgpd_and_security.sql` | Tabelas `access_logs` e `lgpd_consents` |

```bash
php database/run_migration.php
```

---

## Rotas novas

| Método | Rota | Handler | Acesso |
|--------|------|---------|--------|
| GET | `/lgpd/consent` | `LgpdController@showConsent` | Autenticado |
| POST | `/lgpd/consent` | `LgpdController@storeConsent` | Autenticado |
| GET | `/access-logs` | `AccessLogController@index` | `usuarios.visualizar` |

---

## Variáveis de ambiente (`config/app.php` → `security`)

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `SESSION_IDLE_TIMEOUT` | `1800` | Segundos sem atividade até logout |
| `SESSION_ABSOLUTE_TIMEOUT` | `28800` | Tempo máximo de sessão (segundos) |
| `PASSWORD_MIN_LENGTH` | `8` | Tamanho mínimo da senha |
| `PASSWORD_REQUIRE_COMPLEXITY` | `true` | Exigir complexidade na redefinição |
| `LGPD_POLICY_VERSION` | `2026-05-01` | Versão atual; novo valor exige novo consentimento |
| `MASK_SENSITIVE_DATA` | `true` | Mascarar e-mail/telefone em listagens |

---

## Critérios de aceite (status)

| Critério | Status |
|----------|--------|
| Sistema mais seguro | OK |
| Vulnerabilidades reduzidas | OK |
| Sem remoção de funcionalidades | OK |
| Arquitetura MVC respeitada | OK |

---

## Pontos de atenção

1. Usuários já logados precisarão aceitar a política LGPD na primeira requisição após o deploy.
2. Alterar `LGPD_POLICY_VERSION` força novo consentimento para todos.
3. Em produção, use `APP_DEBUG=false` e HTTPS para cookies seguros.
4. A senha do admin seed (`Admin@123`) não atende à nova política — redefina após migration.
