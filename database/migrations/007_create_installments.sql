-- Migration: parcelamento (installments)
-- Execute: php database/run_migration.php

CREATE TABLE IF NOT EXISTS installments (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders (id) ON DELETE RESTRICT,
    installment_number SMALLINT NOT NULL,
    amount NUMERIC(14, 2) NOT NULL,
    due_date DATE NOT NULL,
    paid_at TIMESTAMP,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT installments_order_number_unique UNIQUE (order_id, installment_number),
    CONSTRAINT installments_number_positive CHECK (installment_number > 0),
    CONSTRAINT installments_amount_positive CHECK (amount > 0),
    CONSTRAINT installments_status_check CHECK (
        status IN ('pending', 'overdue', 'paid', 'canceled')
    )
);

CREATE INDEX IF NOT EXISTS idx_installments_order ON installments (order_id);
CREATE INDEX IF NOT EXISTS idx_installments_status ON installments (status);
CREATE INDEX IF NOT EXISTS idx_installments_due_date ON installments (due_date);
CREATE INDEX IF NOT EXISTS idx_installments_paid_at ON installments (paid_at DESC);

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
        'entrada_estoque',
        'conta_receber',
        'recebimento',
        'cancelamento_conta_receber',
        'parcelamento',
        'recebimento_parcela',
        'cancelamento_parcelas'
    )
);
