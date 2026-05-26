-- Avatar do usuário e preferências de interface sincronizadas no servidor

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS avatar_path VARCHAR(500);

CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INTEGER PRIMARY KEY REFERENCES users (id) ON DELETE CASCADE,
    theme VARCHAR(10) NOT NULL DEFAULT 'light',
    sidebar_collapsed BOOLEAN NOT NULL DEFAULT FALSE,
    sidebar_pinned BOOLEAN NOT NULL DEFAULT FALSE,
    dashboard_tab VARCHAR(50) NOT NULL DEFAULT 'overview',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT user_preferences_theme_check CHECK (theme IN ('light', 'dark'))
);

CREATE INDEX IF NOT EXISTS idx_user_preferences_updated ON user_preferences (updated_at);
