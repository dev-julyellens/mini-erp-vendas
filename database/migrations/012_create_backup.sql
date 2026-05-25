-- Migration: backup PostgreSQL — logs e agendamento
-- Execute: php database/run_migration.php

CREATE TABLE IF NOT EXISTS backup_settings (
    id SMALLINT PRIMARY KEY DEFAULT 1,
    enabled BOOLEAN NOT NULL DEFAULT FALSE,
    run_hour SMALLINT NOT NULL DEFAULT 2,
    run_minute SMALLINT NOT NULL DEFAULT 0,
    frequency VARCHAR(20) NOT NULL DEFAULT 'daily',
    last_run_at TIMESTAMP NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_user_id INTEGER REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT backup_settings_singleton CHECK (id = 1),
    CONSTRAINT backup_settings_run_hour_check CHECK (run_hour >= 0 AND run_hour <= 23),
    CONSTRAINT backup_settings_run_minute_check CHECK (run_minute >= 0 AND run_minute <= 59),
    CONSTRAINT backup_settings_frequency_check CHECK (frequency IN ('daily'))
);

INSERT INTO backup_settings (id, enabled, run_hour, run_minute, frequency)
VALUES (1, FALSE, 2, 0, 'daily')
ON CONFLICT (id) DO NOTHING;

CREATE TABLE IF NOT EXISTS backup_logs (
    id SERIAL PRIMARY KEY,
    operation VARCHAR(20) NOT NULL,
    trigger_type VARCHAR(20) NOT NULL,
    filename VARCHAR(255),
    file_size BIGINT,
    status VARCHAR(20) NOT NULL,
    message TEXT,
    user_id INTEGER REFERENCES users (id) ON DELETE SET NULL,
    duration_ms INTEGER,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT backup_logs_operation_check CHECK (
        operation IN ('backup', 'restore', 'cleanup')
    ),
    CONSTRAINT backup_logs_trigger_check CHECK (
        trigger_type IN ('manual', 'automatic', 'cron')
    ),
    CONSTRAINT backup_logs_status_check CHECK (
        status IN ('success', 'failed', 'running')
    )
);

CREATE INDEX IF NOT EXISTS idx_backup_logs_created_at ON backup_logs (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_backup_logs_operation ON backup_logs (operation);
CREATE INDEX IF NOT EXISTS idx_backup_logs_status ON backup_logs (status);
