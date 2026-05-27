<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$files = glob(__DIR__ . '/migrations/*.sql');
if ($files === false || $files === [])
{
    fwrite(STDERR, "No migration files found.\n");
    exit(1);
}

sort($files);
$db = Database::getConnection();

ensureMigrationsTable($db);
bootstrapAppliedMigrations($db);

foreach ($files as $sqlFile)
{
    $version = basename($sqlFile);

    if (isMigrationApplied($db, $version))
    {
        echo 'Skipped (already applied): ' . $version . "\n";
        continue;
    }

    $sql = file_get_contents($sqlFile);
    if ($sql === false)
    {
        fwrite(STDERR, 'Unable to read migration: ' . $version . "\n");
        exit(1);
    }

    try
    {
        $db->exec($sql);
        markMigrationApplied($db, $version);
        echo 'Applied: ' . $version . "\n";
    }
    catch (Throwable $e)
    {
        fwrite(STDERR, 'Migration failed (' . $version . '): ' . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "All pending migrations applied successfully.\n";

function ensureMigrationsTable(PDO $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            version VARCHAR(255) PRIMARY KEY,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
}

function isMigrationApplied(PDO $db, string $version): bool
{
    $stmt = $db->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
    $stmt->execute(['version' => $version]);

    return (bool) $stmt->fetchColumn();
}

function markMigrationApplied(PDO $db, string $version): void
{
    $stmt = $db->prepare(
        'INSERT INTO schema_migrations (version) VALUES (:version)
         ON CONFLICT (version) DO NOTHING'
    );
    $stmt->execute(['version' => $version]);
}

/**
 * Marca migrations já refletidas no banco (primeira execução após upgrade do runner).
 */
function bootstrapAppliedMigrations(PDO $db): void
{
    $count = (int) $db->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
    if ($count > 0)
    {
        return;
    }

    $detectors = [
        '001_create_users.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'users'",
        '002_create_permissions.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'permissions'",
        '003_create_audit_logs.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'audit_logs'",
        '004_create_stock_movements.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'stock_movements'",
        '005_add_order_status.sql' => migration005IsComplete($db) ? 'SELECT 1' : 'SELECT 0',
        '006_create_financial.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'accounts_receivable'",
        '007_create_installments.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'installments'",
        '008_create_categories_and_product_fields.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'categories'",
        '009_add_service_estimated_time.sql' => "SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'products' AND column_name = 'estimated_time_minutes'",
        '010_report_indexes.sql' => "SELECT 1 FROM pg_indexes WHERE schemaname = 'public' AND indexname = 'idx_orders_status_created_at'",
        '011_create_api_security.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'api_logs'",
        '012_create_backup.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'backup_settings'",
        '013_create_companies.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'companies'",
        '014_create_notifications.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'notifications'",
        '015_lgpd_and_security.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'lgpd_consents'",
        '016_create_pix_charges.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'pix_charges'",
        '017_create_saas.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'plans'",
        '018_sync_audit_constraints.sql' => migration018IsComplete($db) ? 'SELECT 1' : 'SELECT 0',
        '019_user_companies_roles.sql' => "SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'user_companies' AND column_name = 'role'",
        '020_profile_avatar_and_preferences.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'user_preferences'",
        '021_create_quotes.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'quotes'",
        '022_create_inventory_counts.sql' => "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'inventory_counts'",
        '023_sync_audit_quotes_inventory.sql' => migration023IsComplete($db) ? 'SELECT 1' : 'SELECT 0',
        '024_stock_movements_company_id.sql' => "SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'stock_movements' AND column_name = 'company_id'",
    ];

    $bootstrapped = 0;
    foreach ($detectors as $version => $query)
    {
        if ((bool) $db->query($query)->fetchColumn())
        {
            markMigrationApplied($db, $version);
            $bootstrapped++;
        }
    }

    if ($bootstrapped > 0)
    {
        echo "Bootstrapped {$bootstrapped} migration(s) already present in the database.\n";
    }
}

function migration005IsComplete(PDO $db): bool
{
    $hasStatus = (bool) $db->query(
        "SELECT 1 FROM information_schema.columns
         WHERE table_schema = 'public' AND table_name = 'orders' AND column_name = 'status'"
    )->fetchColumn();

    if (!$hasStatus)
    {
        return false;
    }

    $hasAudit = (bool) $db->query(
        "SELECT 1 FROM information_schema.tables
         WHERE table_schema = 'public' AND table_name = 'audit_logs'"
    )->fetchColumn();

    if (!$hasAudit)
    {
        return true;
    }

    $def = $db->query(
        "SELECT pg_get_constraintdef(c.oid)
         FROM pg_constraint c
         WHERE c.conname = 'audit_logs_action_check'"
    )->fetchColumn();

    return is_string($def) && str_contains($def, 'cancelamento_venda');
}

function migration023IsComplete(PDO $db): bool
{
    $def = $db->query(
        "SELECT pg_get_constraintdef(c.oid)
         FROM pg_constraint c
         WHERE c.conname = 'audit_logs_action_check'"
    )->fetchColumn();

    return is_string($def) && str_contains($def, 'orcamento');
}

function migration018IsComplete(PDO $db): bool
{
    $hasAudit = (bool) $db->query(
        "SELECT 1 FROM information_schema.tables
         WHERE table_schema = 'public' AND table_name = 'audit_logs'"
    )->fetchColumn();

    if (!$hasAudit)
    {
        return false;
    }

    $def = $db->query(
        "SELECT pg_get_constraintdef(c.oid)
         FROM pg_constraint c
         WHERE c.conname = 'audit_logs_action_check'"
    )->fetchColumn();

    return is_string($def) && str_contains($def, 'pix_cobranca');
}
