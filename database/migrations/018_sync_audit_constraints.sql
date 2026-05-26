-- Repara/sincroniza constraints de audit_logs (idempotente)
-- Útil se 005 falhou após DROP da constraint antiga.

DO $$
DECLARE
    allowed_actions text;
    allowed_entities text;
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

    IF allowed_actions IS NOT NULL THEN
        ALTER TABLE audit_logs DROP CONSTRAINT IF EXISTS audit_logs_action_check;
        EXECUTE format(
            'ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_action_check CHECK (action IN (%s))',
            allowed_actions
        );
    END IF;

    SELECT string_agg(quote_literal(entity), ', ' ORDER BY entity)
    INTO allowed_entities
    FROM (
        SELECT unnest(ARRAY[
            'produtos', 'clientes', 'vendas', 'estoque', 'usuarios', 'financeiro'
        ]::text[]) AS entity
        UNION
        SELECT DISTINCT entity FROM audit_logs
    ) AS entities;

    IF allowed_entities IS NOT NULL THEN
        ALTER TABLE audit_logs DROP CONSTRAINT IF EXISTS audit_logs_entity_check;
        EXECUTE format(
            'ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_entity_check CHECK (entity IN (%s))',
            allowed_entities
        );
    END IF;
END $$;
