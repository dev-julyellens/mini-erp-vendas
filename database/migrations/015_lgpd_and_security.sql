-- Migration: logs de acesso web e consentimento LGPD
-- Execute: php database/run_migration.php

CREATE TABLE IF NOT EXISTS access_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users (id) ON DELETE SET NULL,
    ip_address VARCHAR(45) NOT NULL,
    http_method VARCHAR(10) NOT NULL,
    path VARCHAR(255) NOT NULL,
    status_code SMALLINT,
    user_agent VARCHAR(512),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_access_logs_user_id ON access_logs (user_id);
CREATE INDEX IF NOT EXISTS idx_access_logs_path ON access_logs (path);
CREATE INDEX IF NOT EXISTS idx_access_logs_created_at ON access_logs (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_access_logs_ip_created ON access_logs (ip_address, created_at DESC);

CREATE TABLE IF NOT EXISTS lgpd_consents (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    policy_version VARCHAR(32) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(512),
    accepted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT lgpd_consents_user_version_unique UNIQUE (user_id, policy_version)
);

CREATE INDEX IF NOT EXISTS idx_lgpd_consents_user_id ON lgpd_consents (user_id);
CREATE INDEX IF NOT EXISTS idx_lgpd_consents_accepted_at ON lgpd_consents (accepted_at DESC);
