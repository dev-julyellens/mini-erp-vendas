<?php

/**
 * Application configuration.
 * Values are read from environment (e.g. config/.env loaded in app/bootstrap.php).
 */
$dbHost = getenv('DB_HOST');
$dbPort = getenv('DB_PORT');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASSWORD');
$base = getenv('APP_BASE_URL');
$timezone = getenv('APP_TIMEZONE');

$debug = getenv('APP_DEBUG');
$jwtSecret = getenv('JWT_SECRET');
$jwtTtl = getenv('JWT_TTL');
$apiRateLimit = getenv('API_RATE_LIMIT');
$apiRateLimitWindow = getenv('API_RATE_LIMIT_WINDOW');
$apiLoginRateLimit = getenv('API_LOGIN_RATE_LIMIT');
$backupPath = getenv('BACKUP_PATH');
$backupRetentionDays = getenv('BACKUP_RETENTION_DAYS');
$pgDumpPath = getenv('PG_DUMP_PATH');
$psqlPath = getenv('PSQL_PATH');
$sessionIdleTimeout = getenv('SESSION_IDLE_TIMEOUT');
$sessionAbsoluteTimeout = getenv('SESSION_ABSOLUTE_TIMEOUT');
$passwordMinLength = getenv('PASSWORD_MIN_LENGTH');
$passwordRequireComplexity = getenv('PASSWORD_REQUIRE_COMPLEXITY');
$lgpdPolicyVersion = getenv('LGPD_POLICY_VERSION');
$maskSensitiveData = getenv('MASK_SENSITIVE_DATA');

return [
    'app_name' => 'Mini ERP de Vendas',
    'base_url' => ($base !== false && $base !== '') ? $base : 'http://localhost/mini-erp-vendas/public',
    'timezone' => ($timezone !== false && $timezone !== '') ? $timezone : 'America/Sao_Paulo',
    'debug' => ($debug !== false && $debug !== '') ? filter_var($debug, FILTER_VALIDATE_BOOLEAN) : false,
    'jwt' => [
        'secret' => ($jwtSecret !== false && $jwtSecret !== '') ? $jwtSecret : 'mini-erp-dev-jwt-secret-change-in-production',
        'ttl' => ($jwtTtl !== false && $jwtTtl !== '') ? max(60, (int) $jwtTtl) : 3600,
    ],
    'api' => [
        'rate_limit' => ($apiRateLimit !== false && $apiRateLimit !== '') ? max(1, (int) $apiRateLimit) : 60,
        'rate_limit_window' => ($apiRateLimitWindow !== false && $apiRateLimitWindow !== '') ? max(1, (int) $apiRateLimitWindow) : 60,
        'login_rate_limit' => ($apiLoginRateLimit !== false && $apiLoginRateLimit !== '') ? max(1, (int) $apiLoginRateLimit) : 10,
    ],
    'database' => [
        'host' => ($dbHost !== false && $dbHost !== '') ? $dbHost : '127.0.0.1',
        'port' => ($dbPort !== false && $dbPort !== '') ? $dbPort : '5432',
        'database' => ($dbName !== false && $dbName !== '') ? $dbName : 'mini_erp_vendas',
        'username' => ($dbUser !== false && $dbUser !== '') ? $dbUser : 'postgres',
        'password' => ($dbPass !== false && $dbPass !== '') ? $dbPass : 'postgres',
    ],
    'backup' => [
        'storage_path' => ($backupPath !== false && $backupPath !== '') ? $backupPath : dirname(__DIR__) . '/storage/backups',
        'retention_days' => ($backupRetentionDays !== false && $backupRetentionDays !== '') ? max(1, (int) $backupRetentionDays) : 30,
        'pg_dump_path' => ($pgDumpPath !== false && $pgDumpPath !== '') ? $pgDumpPath : '',
        'psql_path' => ($psqlPath !== false && $psqlPath !== '') ? $psqlPath : '',
    ],
    'security' => [
        'session_idle_timeout' => ($sessionIdleTimeout !== false && $sessionIdleTimeout !== '') ? (int) $sessionIdleTimeout : 1800,
        'session_absolute_timeout' => ($sessionAbsoluteTimeout !== false && $sessionAbsoluteTimeout !== '') ? (int) $sessionAbsoluteTimeout : 28800,
        'password_min_length' => ($passwordMinLength !== false && $passwordMinLength !== '') ? (int) $passwordMinLength : 8,
        'password_require_complexity' => ($passwordRequireComplexity !== false && $passwordRequireComplexity !== '')
            ? filter_var($passwordRequireComplexity, FILTER_VALIDATE_BOOLEAN)
            : true,
        'lgpd_policy_version' => ($lgpdPolicyVersion !== false && $lgpdPolicyVersion !== '') ? $lgpdPolicyVersion : '2026-05-01',
        'mask_sensitive_data' => ($maskSensitiveData !== false && $maskSensitiveData !== '')
            ? filter_var($maskSensitiveData, FILTER_VALIDATE_BOOLEAN)
            : true,
    ],
];
