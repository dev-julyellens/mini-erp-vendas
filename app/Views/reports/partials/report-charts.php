<?php

declare(strict_types=1);

/**
 * Gráficos Chart.js do relatório (dados completos do filtro, não só a página da tabela).
 *
 * @var array{charts: list<array<string, mixed>>}|null $reportCharts
 */

if (empty($reportCharts['charts']) || !is_array($reportCharts['charts']))
{
    return;
}

?>
<div id="reportCharts" class="row g-3 mb-3">
    <?php foreach ($reportCharts['charts'] as $chart): ?>
        <?php
        $canvasId = (string) ($chart['canvasId'] ?? '');
        $dataId = (string) ($chart['dataId'] ?? $canvasId . 'Data');
        $colClass = (string) ($chart['colClass'] ?? 'col-12');
        $chartTitle = (string) ($chart['title'] ?? '');
        $chartSubtitle = (string) ($chart['subtitle'] ?? '');
        $payload = $chart['payload'] ?? [];
        try
        {
            $chartJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        catch (\JsonException)
        {
            continue;
        }
        if ($canvasId === '')
        {
            continue;
        }
        ?>
        <div class="<?= htmlspecialchars($colClass, ENT_QUOTES, 'UTF-8') ?>">
            <div class="card-soft p-3 p-md-4 h-100">
                <div class="mb-2">
                    <div class="fw-semibold"><?= htmlspecialchars($chartTitle, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($chartSubtitle !== ''): ?>
                        <div class="text-muted small"><?= htmlspecialchars($chartSubtitle, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
                <div class="report-chart-wrap<?= ($chart['payload']['kind'] ?? '') === 'horizontalBar' ? ' report-chart-wrap--tall' : '' ?>">
                    <canvas id="<?= htmlspecialchars($canvasId, ENT_QUOTES, 'UTF-8') ?>"
                        aria-label="<?= htmlspecialchars($chartTitle, ENT_QUOTES, 'UTF-8') ?>"></canvas>
                </div>
                <script type="application/json" id="<?= htmlspecialchars($dataId, ENT_QUOTES, 'UTF-8') ?>">
                    <?= $chartJson ?>
                </script>
            </div>
        </div>
    <?php endforeach; ?>
</div>