<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Models\PixCharge;

/** @var callable(string):string $url */
/** @var PixCharge $charge */

$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');
$statusUrl = $url('finance/pix/status?id=' . $charge->id);
$receiptUrl = $url('finance/pix/receipt?id=' . $charge->id);

$subtitleParts = ['Cobrança #' . (int) $charge->id];
if ($charge->order_id !== null)
{
    $subtitleParts[] = 'Venda #' . (int) $charge->order_id;
}
if ($charge->installment_number !== null)
{
    $subtitleParts[] = 'Parcela ' . (int) $charge->installment_number;
}

$title = 'Pagamento PIX';
$subtitle = implode(' · ', $subtitleParts);
$breadcrumbs = [
    ['label' => 'Financeiro', 'href' => $url('finance')],
    ['label' => 'Pagamento PIX'],
];
$actionsHtml = '<a class="btn btn-secondary" href="' . htmlspecialchars($url('finance'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-arrow-left"></i> Voltar</a>';
require dirname(__DIR__, 2) . '/components/page-header.php';

?>
<div class="row g-3 justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="card-soft p-3 p-md-4 text-center">
            <div class="mb-2">
                <span class="badge text-bg-<?= htmlspecialchars(PixCharge::statusBadge($charge->status), ENT_QUOTES, 'UTF-8') ?>" id="pix-status-badge">
                    <?= htmlspecialchars(PixCharge::statusLabel($charge->status), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <div class="fs-3 fw-bold mb-1">R$ <?= htmlspecialchars($fmt($charge->amount), ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($charge->customer_name !== null): ?>
                <div class="text-muted small mb-3"><?= htmlspecialchars($charge->customer_name, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($charge->isPending() && !$charge->isExpired()): ?>
                <div class="text-center">
                    <?php if ($charge->qr_image_url !== null && $charge->qr_image_url !== ''): ?>
                        <img src="<?= htmlspecialchars($charge->qr_image_url, ENT_QUOTES, 'UTF-8') ?>"
                            alt="QR Code PIX" class="img-fluid rounded border bg-white p-2" style="max-width: 320px;">
                    <?php endif; ?>
                </div>

                <?php if ($charge->qr_payload !== null && $charge->qr_payload !== ''): ?>
                    <div class="mt-3 text-center mx-auto" style="max-width: 860px;">
                        <label class="form-label small text-muted" for="pix-copy">PIX copia e cola</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" id="pix-copy"
                                value="<?= htmlspecialchars($charge->qr_payload, ENT_QUOTES, 'UTF-8') ?>" readonly>
                            <button type="button" class="btn btn-outline" id="pix-copy-btn">Copiar</button>
                        </div>
                        <div class="form-text">
                            Cole este código no app do seu banco, ou escaneie o QR Code.
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-3 text-center">
                    <div class="text-muted small mb-0">
                        Válido até <?= htmlspecialchars(DateHelper::toBrDateTime($charge->expires_at), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="text-muted small mt-1" id="pix-poll-hint">Aguardando confirmação do pagamento…</div>
                </div>

                <?php if ($charge->gateway === 'mock'): ?>
                    <form method="post" action="<?= htmlspecialchars($url('finance/pix/simulate-pay'), ENT_QUOTES, 'UTF-8') ?>" class="mt-3 mx-auto" style="max-width: 860px;">
                        <?php require dirname(__DIR__, 2) . '/partials/csrf.php'; ?>
                        <input type="hidden" name="charge_id" value="<?= (int) $charge->id ?>">
                        <button type="submit" class="btn btn-secondary btn-sm w-100">
                            Simular pagamento (testes)
                        </button>
                    </form>
                <?php endif; ?>

            <?php elseif ($charge->isPaid()): ?>
                <div class="alert alert-success mb-0">
                    Pagamento confirmado.
                    <a href="<?= htmlspecialchars($receiptUrl, ENT_QUOTES, 'UTF-8') ?>">Ver comprovante</a>
                </div>
            <?php else: ?>
                <div class="alert alert-secondary mb-0">
                    Esta cobrança não está mais disponível para pagamento.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($charge->isPending() && !$charge->isExpired()): ?>
    <script>
        (function() {
            const statusUrl = <?= json_encode($statusUrl, JSON_UNESCAPED_UNICODE) ?>;
            const receiptUrl = <?= json_encode($receiptUrl, JSON_UNESCAPED_UNICODE) ?>;
            const badge = document.getElementById('pix-status-badge');
            const hint = document.getElementById('pix-poll-hint');
            const copyBtn = document.getElementById('pix-copy-btn');
            const copyInput = document.getElementById('pix-copy');

            if (copyBtn && copyInput) {
                copyBtn.addEventListener('click', function() {
                    copyInput.select();
                    navigator.clipboard.writeText(copyInput.value).then(function() {
                        copyBtn.textContent = 'Copiado!';
                        setTimeout(function() {
                            copyBtn.textContent = 'Copiar';
                        }, 2000);
                    });
                });
            }

            let timer = null;
            async function poll() {
                try {
                    const res = await fetch(statusUrl, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const body = await res.json();
                    if (!body.success) return;
                    const data = body.data || {};
                    if (data.paid) {
                        if (badge) {
                            badge.textContent = 'Pago';
                            badge.className = 'badge text-bg-success';
                        }
                        if (hint) hint.textContent = 'Pagamento confirmado! Redirecionando…';
                        clearInterval(timer);
                        window.location.href = receiptUrl;
                    }
                } catch (e) {
                    /* silencioso */
                }
            }

            timer = setInterval(poll, 4000);
            poll();
        })();
    </script>
<?php endif; ?>