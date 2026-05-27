-- Estende auditoria para orçamentos e inventário físico

DO $$
DECLARE
    actions text;
    entities text;
BEGIN
    SELECT string_agg(quote_literal(action), ', ' ORDER BY action)
    INTO actions
    FROM (
        SELECT unnest(ARRAY[
            'criar', 'editar', 'excluir', 'login', 'logout',
            'solicitar_redefinir_senha', 'redefinir_senha',
            'venda', 'saida_estoque', 'cancelamento_venda', 'entrada_estoque',
            'conta_receber', 'recebimento', 'cancelamento_conta_receber',
            'parcelamento', 'recebimento_parcela', 'cancelamento_parcelas',
            'consentimento_lgpd', 'pix_cobranca', 'pix_conciliacao',
            'orcamento', 'orcamento_conversao', 'inventario_fisico'
        ]) AS action
        UNION
        SELECT DISTINCT action FROM audit_logs WHERE action IS NOT NULL AND action <> ''
    ) s;

    SELECT string_agg(quote_literal(entity), ', ' ORDER BY entity)
    INTO entities
    FROM (
        SELECT unnest(ARRAY[
            'produtos', 'clientes', 'vendas', 'estoque', 'usuarios', 'financeiro'
        ]) AS entity
        UNION
        SELECT DISTINCT entity FROM audit_logs WHERE entity IS NOT NULL AND entity <> ''
    ) s;

    IF actions IS NOT NULL THEN
        ALTER TABLE audit_logs DROP CONSTRAINT IF EXISTS audit_logs_action_check;
        EXECUTE format(
            'ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_action_check CHECK (action IN (%s))',
            actions
        );
    END IF;

    IF entities IS NOT NULL THEN
        ALTER TABLE audit_logs DROP CONSTRAINT IF EXISTS audit_logs_entity_check;
        EXECUTE format(
            'ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_entity_check CHECK (entity IN (%s))',
            entities
        );
    END IF;
END $$;
