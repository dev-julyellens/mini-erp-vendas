-- Arquitetura SaaS: planos, assinaturas, cobrança recorrente, limites e onboarding
-- Idempotente: pode ser executada mais de uma vez com segurança.

ALTER TABLE companies ADD COLUMN IF NOT EXISTS slug VARCHAR(80);
ALTER TABLE companies ADD COLUMN IF NOT EXISTS owner_user_id INTEGER REFERENCES users (id) ON DELETE SET NULL;
ALTER TABLE companies ADD COLUMN IF NOT EXISTS onboarding_step VARCHAR(30) NOT NULL DEFAULT 'completed';
ALTER TABLE companies ADD COLUMN IF NOT EXISTS onboarding_completed_at TIMESTAMP;

UPDATE companies
SET slug = 'empresa-' || id::text,
    onboarding_step = COALESCE(onboarding_step, 'completed'),
    onboarding_completed_at = COALESCE(onboarding_completed_at, created_at)
WHERE slug IS NULL;

DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'companies'
          AND column_name = 'slug'
          AND is_nullable = 'YES'
    ) THEN
        ALTER TABLE companies ALTER COLUMN slug SET NOT NULL;
    END IF;
END $$;

ALTER TABLE companies DROP CONSTRAINT IF EXISTS companies_name_unique;
CREATE UNIQUE INDEX IF NOT EXISTS companies_slug_unique ON companies (LOWER(slug));
CREATE INDEX IF NOT EXISTS idx_companies_onboarding ON companies (onboarding_step)
    WHERE onboarding_completed_at IS NULL;

CREATE TABLE IF NOT EXISTS plans (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT,
    price_monthly NUMERIC(12, 2) NOT NULL DEFAULT 0,
    billing_interval VARCHAR(20) NOT NULL DEFAULT 'monthly',
    trial_days INTEGER NOT NULL DEFAULT 0,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT plans_code_unique UNIQUE (code),
    CONSTRAINT plans_billing_interval_check CHECK (billing_interval IN ('monthly', 'yearly')),
    CONSTRAINT plans_price_non_negative CHECK (price_monthly >= 0),
    CONSTRAINT plans_trial_days_non_negative CHECK (trial_days >= 0)
);

CREATE INDEX IF NOT EXISTS idx_plans_active ON plans (active, sort_order) WHERE active = TRUE;

CREATE TABLE IF NOT EXISTS plan_limits (
    id SERIAL PRIMARY KEY,
    plan_id INTEGER NOT NULL REFERENCES plans (id) ON DELETE CASCADE,
    limit_key VARCHAR(50) NOT NULL,
    limit_value INTEGER NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT plan_limits_plan_key_unique UNIQUE (plan_id, limit_key),
    CONSTRAINT plan_limits_key_check CHECK (
        limit_key IN ('customers_max', 'products_max', 'users_max', 'orders_month_max')
    )
);

CREATE INDEX IF NOT EXISTS idx_plan_limits_plan ON plan_limits (plan_id);

CREATE TABLE IF NOT EXISTS subscriptions (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies (id) ON DELETE CASCADE,
    plan_id INTEGER NOT NULL REFERENCES plans (id) ON DELETE RESTRICT,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    current_period_start TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    current_period_end TIMESTAMP NOT NULL,
    trial_ends_at TIMESTAMP,
    canceled_at TIMESTAMP,
    cancel_at_period_end BOOLEAN NOT NULL DEFAULT FALSE,
    external_subscription_id VARCHAR(100),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT subscriptions_company_unique UNIQUE (company_id),
    CONSTRAINT subscriptions_status_check CHECK (
        status IN ('trialing', 'active', 'past_due', 'canceled', 'expired')
    )
);

CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions (status);
CREATE INDEX IF NOT EXISTS idx_subscriptions_period_end ON subscriptions (current_period_end);

CREATE TABLE IF NOT EXISTS subscription_invoices (
    id SERIAL PRIMARY KEY,
    subscription_id INTEGER NOT NULL REFERENCES subscriptions (id) ON DELETE CASCADE,
    company_id INTEGER NOT NULL REFERENCES companies (id) ON DELETE CASCADE,
    amount NUMERIC(12, 2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    period_start TIMESTAMP NOT NULL,
    period_end TIMESTAMP NOT NULL,
    due_at TIMESTAMP NOT NULL,
    paid_at TIMESTAMP,
    external_invoice_id VARCHAR(100),
    failure_reason TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT subscription_invoices_status_check CHECK (
        status IN ('pending', 'paid', 'failed', 'void')
    ),
    CONSTRAINT subscription_invoices_amount_positive CHECK (amount > 0)
);

CREATE INDEX IF NOT EXISTS idx_subscription_invoices_company ON subscription_invoices (company_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_subscription_invoices_subscription ON subscription_invoices (subscription_id, period_end DESC);
CREATE INDEX IF NOT EXISTS idx_subscription_invoices_pending ON subscription_invoices (status, due_at)
    WHERE status = 'pending';

INSERT INTO plans (code, name, description, price_monthly, billing_interval, trial_days, sort_order)
VALUES
    ('starter', 'Starter', 'Ideal para começar com poucos cadastros.', 49.90, 'monthly', 14, 10),
    ('professional', 'Professional', 'Para operações em crescimento.', 149.90, 'monthly', 14, 20),
    ('enterprise', 'Enterprise', 'Limites ampliados e operação sem restrições práticas.', 399.90, 'monthly', 0, 30)
ON CONFLICT (code) DO NOTHING;

INSERT INTO plan_limits (plan_id, limit_key, limit_value)
SELECT p.id, v.limit_key, v.limit_value
FROM plans p
CROSS JOIN (
    VALUES
        ('starter', 'customers_max', 50),
        ('starter', 'products_max', 100),
        ('starter', 'users_max', 3),
        ('starter', 'orders_month_max', 200),
        ('professional', 'customers_max', 500),
        ('professional', 'products_max', 1000),
        ('professional', 'users_max', 10),
        ('professional', 'orders_month_max', 2000),
        ('enterprise', 'customers_max', -1),
        ('enterprise', 'products_max', -1),
        ('enterprise', 'users_max', -1),
        ('enterprise', 'orders_month_max', -1)
) AS v(plan_code, limit_key, limit_value)
WHERE p.code = v.plan_code
ON CONFLICT (plan_id, limit_key) DO NOTHING;

INSERT INTO subscriptions (company_id, plan_id, status, current_period_start, current_period_end)
SELECT c.id, p.id, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP + INTERVAL '1 year'
FROM companies c
CROSS JOIN plans p
WHERE p.code = 'enterprise'
ON CONFLICT (company_id) DO NOTHING;

UPDATE companies SET slug = 'empresa-padrao' WHERE id = 1 AND slug <> 'empresa-padrao';
