-- Migration: módulo financeiro básico (contas a receber, pagamentos, fluxo de caixa)
-- Execute: php database/run_migration.php

CREATE TABLE IF NOT EXISTS accounts_receivable (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders (id) ON DELETE RESTRICT,
    customer_id INTEGER NOT NULL REFERENCES customers (id) ON DELETE RESTRICT,
    amount NUMERIC(14, 2) NOT NULL,
    due_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT accounts_receivable_order_unique UNIQUE (order_id),
    CONSTRAINT accounts_receivable_amount_positive CHECK (amount > 0),
    CONSTRAINT accounts_receivable_status_check CHECK (
        status IN ('pending', 'partial', 'paid', 'canceled')
    )
);

CREATE INDEX IF NOT EXISTS idx_accounts_receivable_customer ON accounts_receivable (customer_id);
CREATE INDEX IF NOT EXISTS idx_accounts_receivable_status ON accounts_receivable (status);
CREATE INDEX IF NOT EXISTS idx_accounts_receivable_due_date ON accounts_receivable (due_date);
CREATE INDEX IF NOT EXISTS idx_accounts_receivable_order ON accounts_receivable (order_id);

CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    accounts_receivable_id INTEGER NOT NULL REFERENCES accounts_receivable (id) ON DELETE RESTRICT,
    amount NUMERIC(14, 2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    received_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT payments_amount_positive CHECK (amount > 0),
    CONSTRAINT payments_method_check CHECK (
        payment_method IN ('dinheiro', 'pix', 'cartao', 'boleto')
    )
);

CREATE INDEX IF NOT EXISTS idx_payments_accounts_receivable ON payments (accounts_receivable_id);
CREATE INDEX IF NOT EXISTS idx_payments_paid_at ON payments (paid_at DESC);
CREATE INDEX IF NOT EXISTS idx_payments_method ON payments (payment_method);

CREATE TABLE IF NOT EXISTS cash_flow (
    id SERIAL PRIMARY KEY,
    type VARCHAR(10) NOT NULL,
    amount NUMERIC(14, 2) NOT NULL,
    payment_method VARCHAR(20),
    reference_type VARCHAR(50),
    reference_id INTEGER,
    description TEXT,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users (id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT cash_flow_type_check CHECK (type IN ('entrada', 'saida')),
    CONSTRAINT cash_flow_amount_positive CHECK (amount > 0),
    CONSTRAINT cash_flow_method_check CHECK (
        payment_method IS NULL
        OR payment_method IN ('dinheiro', 'pix', 'cartao', 'boleto')
    )
);

CREATE INDEX IF NOT EXISTS idx_cash_flow_type ON cash_flow (type);
CREATE INDEX IF NOT EXISTS idx_cash_flow_occurred_at ON cash_flow (occurred_at DESC);
CREATE INDEX IF NOT EXISTS idx_cash_flow_reference ON cash_flow (reference_type, reference_id);

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
        'cancelamento_conta_receber'
    )
);

ALTER TABLE audit_logs DROP CONSTRAINT IF EXISTS audit_logs_entity_check;

ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_entity_check CHECK (
    entity IN ('produtos', 'clientes', 'vendas', 'estoque', 'usuarios', 'financeiro')
);
