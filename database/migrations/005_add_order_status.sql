-- Migration: status de vendas e cancelamento
-- Idempotente. Execute: php database/run_migration.php

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

-- Sincroniza audit_logs_action_check com ações canônicas + quaisquer ações já gravadas no banco
DO $$
DECLARE
    allowed_actions text;
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'public' AND table_name = 'audit_logs'
    ) THEN
        RETURN;
    END IF;

    SELECT string_agg(quote_literal(action), ', ' ORDER BY action)
    INTO allowed_actions
    FROM (
        SELECT unnest(ARRAY[
            'criar', 'editar', 'excluir', 'login', 'logout',
            'solicitar_redefinir_senha', 'redefinir_senha',
            'venda', 'saida_estoque', 'cancelamento_venda', 'entrada_estoque',
            'conta_receber', 'recebimento', 'cancelamento_conta_receber',
            'parcelamento', 'recebimento_parcela', 'cancelamento_parcelas',
            'consentimento_lgpd', 'pix_cobranca', 'pix_conciliacao'
        ]::text[]) AS action
        UNION
        SELECT DISTINCT action FROM audit_logs
    ) AS actions;

    IF allowed_actions IS NULL THEN
        RETURN;
    END IF;

    ALTER TABLE audit_logs DROP CONSTRAINT IF EXISTS audit_logs_action_check;

    EXECUTE format(
        'ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_action_check CHECK (action IN (%s))',
        allowed_actions
    );
END $$;
