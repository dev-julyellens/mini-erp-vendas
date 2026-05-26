-- Notificações operacionais persistidas por empresa

CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies (id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    entity_type VARCHAR(50),
    entity_id INTEGER,
    level VARCHAR(20) NOT NULL DEFAULT 'warning',
    link_url VARCHAR(500),
    dedupe_key VARCHAR(255),
    read_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT notifications_type_check CHECK (
        type IN ('low_stock', 'overdue_account', 'order_canceled', 'critical_error')
    ),
    CONSTRAINT notifications_level_check CHECK (
        level IN ('info', 'warning', 'danger')
    )
);

CREATE UNIQUE INDEX notifications_dedupe_open_unique
    ON notifications (company_id, dedupe_key)
    WHERE dedupe_key IS NOT NULL AND read_at IS NULL;

CREATE INDEX idx_notifications_company_unread
    ON notifications (company_id, created_at DESC)
    WHERE read_at IS NULL;

CREATE INDEX idx_notifications_company_created
    ON notifications (company_id, created_at DESC);

CREATE INDEX idx_notifications_type
    ON notifications (company_id, type);
