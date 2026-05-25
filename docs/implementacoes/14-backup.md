# Implementação: Backup PostgreSQL

**Prompt de origem:** `.cursor/prompts/14-backup.md`  
**Data de referência:** maio/2026  
**Escopo:** backup manual e automático do PostgreSQL, restauração, logs e interface administrativa.

---

## Visão geral

O módulo de backup permite exportar o banco PostgreSQL via `pg_dump`, restaurar com `psql`, agendar execuções diárias e registrar todas as operações em log.

### Fluxo resumido

```
Interface /backups (admin)
  → BackupController
  → BackupService
  → pg_dump / psql (PgCli)
  → storage/backups/*.sql
  → backup_logs (PostgreSQL)

Agendador (cron / Task Scheduler)
  → php database/backup_cron.php
  → BackupService::runScheduledIfDue()
```

---

## Funcionalidades

| Recurso | Descrição |
|---------|-----------|
| Backup manual | Botão "Criar backup agora" na interface |
| Exportação | Download de arquivos `.sql` gerados |
| Backup automático | Agendamento diário configurável (hora/minuto) |
| Restore | Restauração com confirmação digitando `RESTAURAR` |
| Logs | Histórico de backup, restore e limpeza |
| Retenção | Remoção automática de backups antigos (padrão: 30 dias) |

---

## Permissões

- Rotas mapeadas no módulo `usuarios` (ACL existente).
- Operações destrutivas e criação exigem perfil **admin** (validado no service).
- Usuários com `usuarios.visualizar` podem ver logs e arquivos, mas não executar ações sensíveis.

---

## Configuração (.env)

```env
BACKUP_PATH=                    # opcional; padrão: storage/backups
BACKUP_RETENTION_DAYS=30
PG_DUMP_PATH=                   # opcional; auto-detecta PostgreSQL no Windows
PSQL_PATH=                      # opcional; auto-detecta PostgreSQL no Windows
```

---

## Agendamento automático

1. Ative o backup automático em **Backup → Agendamento**.
2. Configure hora e minuto desejados.
3. Agende no sistema operacional:

**Linux (cron):**
```bash
0 2 * * * php /caminho/mini-erp-vendas/database/backup_cron.php
```

**Windows (Task Scheduler):**
```
php C:\xampp\htdocs\mini-erp-vendas\database\backup_cron.php
```

O script verifica se o agendamento está ativo e se ainda não rodou no dia.

---

## Migration

```bash
php database/run_migration.php
```

Cria as tabelas `backup_settings` e `backup_logs`.

---

## Arquivos principais

| Caminho | Responsabilidade |
|---------|------------------|
| `app/Controllers/BackupController.php` | Interface HTTP |
| `app/Services/BackupService.php` | Regras de backup/restore/agendamento |
| `app/Repositories/BackupLogRepository.php` | Persistência de logs |
| `app/Repositories/BackupSettingsRepository.php` | Configuração de agendamento |
| `app/Helpers/PgCli.php` | Execução segura de pg_dump/psql |
| `app/Views/backups/index.php` | Interface administrativa |
| `database/backup_cron.php` | Script para agendador externo |
| `database/migrations/012_create_backup.sql` | Schema |

---

## Verificação

1. Executar migration.
2. Acessar `/backups` como admin.
3. Criar backup manual e verificar arquivo em `storage/backups/`.
4. Baixar o arquivo `.sql`.
5. (Opcional) Restaurar em ambiente de teste.
6. Executar `php database/backup_cron.php` com agendamento ativo.

---

## Observações de produção

- Garanta que `pg_dump` e `psql` estejam no PATH ou configure `PG_DUMP_PATH` / `PSQL_PATH`.
- O diretório `storage/backups` deve ser gravável pelo usuário do PHP.
- Restauração substitui dados existentes — use apenas com confirmação explícita.
- Arquivos `.sql` não são versionados (`.gitignore`).
