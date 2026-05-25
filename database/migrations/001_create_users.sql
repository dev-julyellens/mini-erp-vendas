-- Migration: users + password reset tokens
-- Execute: psql -U postgres -d mini_erp_vendas -f database/migrations/001_create_users.sql

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT users_email_unique UNIQUE (email),
    CONSTRAINT users_role_check CHECK (role IN ('admin', 'vendedor', 'financeiro', 'estoque'))
);

CREATE INDEX IF NOT EXISTS idx_users_email_lower ON users (LOWER(email));
CREATE INDEX IF NOT EXISTS idx_users_active ON users (active) WHERE active = TRUE;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    token_hash VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_password_reset_token_hash ON password_reset_tokens (token_hash);
CREATE INDEX IF NOT EXISTS idx_password_reset_user ON password_reset_tokens (user_id);

-- Admin padrão (senha: Admin@123) — altere após o primeiro login
INSERT INTO users (name, email, password_hash, role, active)
SELECT
    'Administrador',
    'admin@mini-erp.local',
    '$2y$10$XNyBEjcS0aobvO6spDzXWOSwE.SxMSJhh59KUBcOOFTGSlAZdSwxe',
    'admin',
    TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE LOWER(email) = LOWER('admin@mini-erp.local')
);
