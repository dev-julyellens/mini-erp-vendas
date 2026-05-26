<?php

declare(strict_types=1);

use App\Helpers\ChartA11yHelper;

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

        $chartPayload = is_array($payload) ? $payload : [];
        $chartLabels = is_array($chartPayload['labels'] ?? null) ? $chartPayload['labels'] : [];
        $chartDatasets = is_array($chartPayload['datasets'] ?? null) ? $chartPayload['datasets'] : [];
        $summaryId = $canvasId . 'Summary';
        $titleId = $canvasId . 'Title';
        $summaryHeaders = ChartA11yHelper::columnHeaders($chartLabels, $chartDatasets);
        $summaryRows = ChartA11yHelper::tableRows($chartLabels, $chartDatasets);
        ?>
        <div class="<?= htmlspecialchars($colClass, ENT_QUOTES, 'UTF-8') ?>">
            <div class="card-soft p-3 p-md-4 h-100">
                <figure class="mb-0" role="group" aria-labelledby="<?= htmlspecialchars($titleId, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-2">
                        <figcaption class="fw-semibold mb-0" id="<?= htmlspecialchars($titleId, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($chartTitle, ENT_QUOTES, 'UTF-8') ?>
                        </figcaption>
                        <?php if ($chartSubtitle !== ''): ?>
                            <div class="text-muted small"><?= htmlspecialchars($chartSubtitle, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="report-chart-wrap<?= ($chartPayload['kind'] ?? '') === 'horizontalBar' ? ' report-chart-wrap--tall' : '' ?>">
                        <canvas id="<?= htmlspecialchars($canvasId, ENT_QUOTES, 'UTF-8') ?>" role="img"
                            aria-labelledby="<?= htmlspecialchars($titleId . ' ' . $summaryId, ENT_QUOTES, 'UTF-8') ?>"></canvas>
                    </div>
                    <?php
                    $caption = $chartTitle;
                    require dirname(__DIR__, 2) . '/components/chart-sr-summary.php';
                    ?>
                </figure>
                <script type="application/json" id="<?= htmlspecialchars($dataId, ENT_QUOTES, 'UTF-8') ?>">
                    <?= $chartJson ?>
                </script>
            </div>
        </div>
    <?php endforeach; ?>
</div>