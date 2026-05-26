<?php

declare(strict_types=1);

use App\Helpers\DateHelper;

/** @var callable(string):string $url */
/** @var string $policyVersion */
/** @var array<string, string> $errors */

$errors = $errors ?? [];

$title = 'Privacidade e proteção de dados (LGPD)';
$subtitle = 'Versão da política: ' . DateHelper::toBrDate($policyVersion);
require dirname(__DIR__) . '/components/auth-form-header.php';

?>
<div class="border rounded p-3 mb-3 small" style="max-height: 14rem; overflow-y: auto;">
    <p class="mb-2">
        Este sistema trata dados pessoais de clientes e usuários para operação do ERP (cadastros, vendas,
        financeiro e auditoria). Os dados são utilizados apenas para fins legítimos do negócio.
    </p>
    <p class="mb-2">
        Você pode solicitar informações, correção ou exclusão de dados conforme a Lei Geral de Proteção de
        Dados (Lei nº 13.709/2018), entrando em contato com o administrador da sua empresa.
    </p>
    <p class="mb-0">
        Ao continuar, você declara ciência e concordância com o tratamento descrito para uso do sistema.
    </p>
</div>

<?php if (isset($errors['consent'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['consent'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($url('lgpd/consent'), ENT_QUOTES, 'UTF-8') ?>">
    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="accept" id="accept" value="1" required>
        <label class="form-check-label" for="accept">
            Li e aceito a política de privacidade e tratamento de dados pessoais.
        </label>
    </div>

    <button type="submit" class="btn btn-primary w-100" data-loading-text="Registrando...">Continuar</button>
</form>