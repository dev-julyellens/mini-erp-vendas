-- Papéis e status por empresa no vínculo usuário ↔ empresa (idempotente)

ALTER TABLE user_companies ADD COLUMN IF NOT EXISTS role VARCHAR(50) NOT NULL DEFAULT 'employee';
ALTER TABLE user_companies ADD COLUMN IF NOT EXISTS active BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE user_companies ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE user_companies ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

UPDATE user_companies uc
SET role = 'owner'
FROM companies c
WHERE c.id = uc.company_id
  AND c.owner_user_id = uc.user_id;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'user_companies_role_check'
    ) THEN
        ALTER TABLE user_companies
            ADD CONSTRAINT user_companies_role_check
            CHECK (role IN ('owner', 'admin', 'manager', 'employee'));
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_user_companies_user_active
    ON user_companies (user_id) WHERE active = TRUE;

CREATE INDEX IF NOT EXISTS idx_user_companies_company_active
    ON user_companies (company_id) WHERE active = TRUE;
