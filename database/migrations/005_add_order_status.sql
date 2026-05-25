-- Migration: status de vendas e cancelamento
-- Execute: php database/run_migration.php

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'paid';

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS canceled_by INTEGER REFERENCES users (id) ON DELETE SET NULL;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS canceled_at TIMESTAMP;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'orders_status_check'
    ) THEN
        ALTER TABLE orders
            ADD CONSTRAINT orders_status_check CHECK (
                status IN ('pending', 'paid', 'canceled', 'refunded')
            );
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_orders_status ON orders (status);

ALTER TABLE audit_logs DROP CONSTRAINT IF EXISTS audit_logs_action_check;

ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_action_check CHECK (
    action IN (
        'criar',
        'editar',
        'excluir',
        'login',
        'logout',
        'solicitar_redefinir_senha',
        'redefinir_senha',
        'venda',
        'saida_estoque',
        'cancelamento_venda',
        'entrada_estoque'
    )
);
