<?php

/**
 * Application configuration.
 * Values are read from environment (e.g. config/.env loaded in app/bootstrap.php).
 */

use App\Core\Env;

$env = static fn(string $key): ?string => Env::get($key);

$dbHost = $env('DB_HOST');
$dbPort = $env('DB_PORT');
$dbName = $env('DB_NAME');
$dbUser = $env('DB_USER');
$dbPass = $env('DB_PASSWORD');
$base = $env('APP_BASE_URL');
$timezone = $env('APP_TIMEZONE');

$debug = $env('APP_DEBUG');
$appEnv = $env('APP_ENV');
$mailDriver = $env('MAIL_DRIVER');
$mailFromAddress = $env('MAIL_FROM_ADDRESS');
$mailFromName = $env('MAIL_FROM_NAME');
$mailSmtpHost = $env('MAIL_SMTP_HOST');
$mailSmtpPort = $env('MAIL_SMTP_PORT');
$mailSmtpUser = $env('MAIL_SMTP_USER');
$mailSmtpPassword = $env('MAIL_SMTP_PASSWORD');
$mailSmtpEncryption = $env('MAIL_SMTP_ENCRYPTION');
$mailSmtpAuth = $env('MAIL_SMTP_AUTH');
$mailSmtpDebug = $env('MAIL_SMTP_DEBUG');
$jwtTtl = $env('JWT_TTL');
$apiRateLimit = $env('API_RATE_LIMIT');
$apiRateLimitWindow = $env('API_RATE_LIMIT_WINDOW');
$apiLoginRateLimit = $env('API_LOGIN_RATE_LIMIT');
$backupPath = $env('BACKUP_PATH');
$backupRetentionDays = $env('BACKUP_RETENTION_DAYS');
$pgDumpPath = $env('PG_DUMP_PATH');
$psqlPath = $env('PSQL_PATH');
$sessionIdleTimeout = $env('SESSION_IDLE_TIMEOUT');
$sessionAbsoluteTimeout = $env('SESSION_ABSOLUTE_TIMEOUT');
$passwordMinLength = $env('PASSWORD_MIN_LENGTH');
$passwordRequireComplexity = $env('PASSWORD_REQUIRE_COMPLEXITY');
$lgpdPolicyVersion = $env('LGPD_POLICY_VERSION');
$maskSensitiveData = $env('MASK_SENSITIVE_DATA');
$pixEnabled = $env('PIX_ENABLED');
$pixGateway = $env('PIX_DEFAULT_GATEWAY');
$pixTtl = $env('PIX_CHARGE_TTL_SECONDS');
$pixWebhookSecret = $env('PIX_WEBHOOK_SECRET');
$pixMerchantName = $env('PIX_MERCHANT_NAME');
$pixMerchantCity = $env('PIX_MERCHANT_CITY');
$logPath = $env('LOG_PATH');
$logLevel = $env('LOG_LEVEL');

return [
    'app_name' => 'Mini ERP de Vendas',
    'base_url' => ($base !== null && $base !== '') ? $base : 'http://localhost/mini-erp-vendas/public',
    'timezone' => ($timezone !== null && $timezone !== '') ? $timezone : 'America/Sao_Paulo',
    'debug' => ($debug !== null && $debug !== '') ? filter_var($debug, FILTER_VALIDATE_BOOLEAN) : false,
    'env' => ($appEnv !== null && $appEnv !== '') ? strtolower(trim($appEnv)) : 'local',
    'jwt' => [
        'secret' => \App\Helpers\AppConfig::jwtSecret(),
        'ttl' => ($jwtTtl !== null && $jwtTtl !== '') ? max(60, (int) $jwtTtl) : 3600,
    ],
    'mail' => [
        'driver' => ($mailDriver !== null && $mailDriver !== '') ? strtolower(trim($mailDriver)) : 'log',
        'from_address' => ($mailFromAddress !== null && $mailFromAddress !== '') ? $mailFromAddress : 'noreply@localhost',
        'from_name' => ($mailFromName !== null && $mailFromName !== '') ? $mailFromName : 'Mini ERP de Vendas',
        'smtp_host' => ($mailSmtpHost !== null && $mailSmtpHost !== '') ? $mailSmtpHost : '',
        'smtp_port' => ($mailSmtpPort !== null && $mailSmtpPort !== '') ? max(1, (int) $mailSmtpPort) : 587,
        'smtp_user' => ($mailSmtpUser !== null && $mailSmtpUser !== '') ? $mailSmtpUser : '',
        'smtp_password' => ($mailSmtpPassword !== null && $mailSmtpPassword !== '') ? $mailSmtpPassword : '',
        'smtp_encryption' => ($mailSmtpEncryption !== null && $mailSmtpEncryption !== '') ? strtolower(trim($mailSmtpEncryption)) : 'tls',
        'smtp_auth' => ($mailSmtpAuth !== null && $mailSmtpAuth !== '')
            ? filter_var($mailSmtpAuth, FILTER_VALIDATE_BOOLEAN)
            : true,
        'smtp_debug' => ($mailSmtpDebug !== null && $mailSmtpDebug !== '')
            ? filter_var($mailSmtpDebug, FILTER_VALIDATE_BOOLEAN)
            : false,
    ],
    'api' => [
        'rate_limit' => ($apiRateLimit !== null && $apiRateLimit !== '') ? max(1, (int) $apiRateLimit) : 60,
        'rate_limit_window' => ($apiRateLimitWindow !== null && $apiRateLimitWindow !== '') ? max(1, (int) $apiRateLimitWindow) : 60,
        'login_rate_limit' => ($apiLoginRateLimit !== null && $apiLoginRateLimit !== '') ? max(1, (int) $apiLoginRateLimit) : 10,
    ],
    'database' => [
        'host' => ($dbHost !== null && $dbHost !== '') ? $dbHost : '127.0.0.1',
        'port' => ($dbPort !== null && $dbPort !== '') ? $dbPort : '5432',
        'database' => ($dbName !== null && $dbName !== '') ? $dbName : 'mini_erp_vendas',
        'username' => ($dbUser !== null && $dbUser !== '') ? $dbUser : 'postgres',
        'password' => ($dbPass !== null && $dbPass !== '') ? $dbPass : 'postgres',
    ],
    'backup' => [
        'storage_path' => ($backupPath !== null && $backupPath !== '') ? $backupPath : dirname(__DIR__) . '/storage/backups',
        'retention_days' => ($backupRetentionDays !== null && $backupRetentionDays !== '') ? max(1, (int) $backupRetentionDays) : 30,
        'pg_dump_path' => ($pgDumpPath !== null && $pgDumpPath !== '') ? $pgDumpPath : '',
        'psql_path' => ($psqlPath !== null && $psqlPath !== '') ? $psqlPath : '',
    ],
    'security' => [
        'session_idle_timeout' => ($sessionIdleTimeout !== null && $sessionIdleTimeout !== '') ? (int) $sessionIdleTimeout : 1800,
        'session_absolute_timeout' => ($sessionAbsoluteTimeout !== null && $sessionAbsoluteTimeout !== '') ? (int) $sessionAbsoluteTimeout : 28800,
        'password_min_length' => ($passwordMinLength !== null && $passwordMinLength !== '') ? (int) $passwordMinLength : 8,
        'password_require_complexity' => ($passwordRequireComplexity !== null && $passwordRequireComplexity !== '')
            ? filter_var($passwordRequireComplexity, FILTER_VALIDATE_BOOLEAN)
            : true,
        'lgpd_policy_version' => ($lgpdPolicyVersion !== null && $lgpdPolicyVersion !== '') ? $lgpdPolicyVersion : '2026-05-01',
        'mask_sensitive_data' => ($maskSensitiveData !== null && $maskSensitiveData !== '')
            ? filter_var($maskSensitiveData, FILTER_VALIDATE_BOOLEAN)
            : true,
    ],
    'log' => [
        'path' => ($logPath !== null && $logPath !== '') ? $logPath : dirname(__DIR__) . '/storage/logs/app.log',
        'level' => ($logLevel !== null && $logLevel !== '') ? $logLevel : 'warning',
    ],
    'pix' => [
        'enabled' => ($pixEnabled !== null && $pixEnabled !== '')
            ? filter_var($pixEnabled, FILTER_VALIDATE_BOOLEAN)
            : true,
        'default_gateway' => ($pixGateway !== null && $pixGateway !== '') ? $pixGateway : 'mock',
        'charge_ttl_seconds' => ($pixTtl !== null && $pixTtl !== '') ? max(300, (int) $pixTtl) : 3600,
        'webhook_secret' => ($pixWebhookSecret !== null && $pixWebhookSecret !== '') ? $pixWebhookSecret : '',
        'merchant_name' => ($pixMerchantName !== null && $pixMerchantName !== '') ? $pixMerchantName : 'Mini ERP',
        'merchant_city' => ($pixMerchantCity !== null && $pixMerchantCity !== '') ? $pixMerchantCity : 'Sao Paulo',
    ],
];
