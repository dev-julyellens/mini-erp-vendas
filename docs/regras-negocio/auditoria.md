# Regras de negócio — Auditoria

## Visão geral

Trilha de alterações sensíveis em `audit_logs` com snapshots JSON (antes/depois). Disparada via `App\Helpers\Audit::record()` a partir de services e `AuthService`.

## Estrutura do registro

| Campo | Descrição |
|-------|-----------|
| `entity` | Tipo lógico (ex.: `venda`, `cliente`, `login`) |
| `module` | Módulo ACL relacionado |
| `entity_id` | ID do registro afetado |
| `user_id` | Autor (nullable em jobs) |
| `company_id` | Tenant |
| `before_data` / `after_data` | JSONB |
| `created_at` | Timestamp |

## Eventos documentados no código

| entity | Contexto típico |
|--------|-----------------|
| `login` | Autenticação |
| `venda` | Criação de pedido |
| `cancelamento_venda` | Cancelamento |
| `conta_receber` | AR na venda |
| `parcelamento` | Geração de parcelas |
| `saida_estoque` / `entrada_estoque` | Movimentações ligadas a pedido |
| `cliente` | CRUD clientes |
| Outros | Conforme `AuditService` e services de domínio |

Constraints de `entity` evoluíram (migration `018_sync_audit_constraints.sql`).

## Consulta

- Tela `/audit-logs` — permissão `usuarios.visualizar`.
- Filtros por período, entidade e usuário (conforme controller).

## Acesso web (complementar)

- `access_logs` — cada requisição autenticada via `AccessLogMiddleware` / `AccessLogService`.
- Tela `/access-logs` — mesma permissão de usuários.

## LGPD

- Consentimentos em `lgpd_consents` (versão da política em config).
- Mascaramento opcional: `MASK_SENSITIVE_DATA` em listagens.

## Regras

- Auditoria de venda ocorre **após commit** da transação principal (evita log de operação revertida).
- Snapshots não devem incluir senhas ou tokens.
- Admin pode visualizar logs de toda a empresa ativa.

## Referências

- `app/Helpers/Audit.php`
- `app/Services/AuditService.php`
- `app/Repositories/AuditRepository.php`
- `docs/implementacoes/03-auditoria.md`
- `docs/implementacoes/17-lgpd-e-seguranca.md`
