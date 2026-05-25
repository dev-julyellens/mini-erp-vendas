-- Migration: logs de API e controle de rate limit
-- Execute: php database/run_migration.php

CREATE TABLE IF NOT EXISTS api_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users (id) ON DELETE SET NULL,
    ip_address VARCHAR(45) NOT NULL,
    http_method VARCHAR(10) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    payload JSONB,
    status_code SMALLINT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_api_logs_user_id ON api_logs (user_id);
CREATE INDEX IF NOT EXISTS idx_api_logs_endpoint ON api_logs (endpoint);
CREATE INDEX IF NOT EXISTS idx_api_logs_created_at ON api_logs (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_api_logs_ip_created ON api_logs (ip_address, created_at DESC);

CREATE TABLE IF NOT EXISTS api_rate_limit_buckets (
    bucket_key VARCHAR(255) PRIMARY KEY,
    request_count INTEGER NOT NULL DEFAULT 0,
    reset_at TIMESTAMP NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_api_rate_limit_reset_at ON api_rate_limit_buckets (reset_at);
