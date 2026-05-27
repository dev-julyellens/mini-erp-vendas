<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\DateHelper;
use App\Models\CashFlow;
use App\Models\Payment;
use App\Models\ReportFilter;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ReportExportService
{
    private ReportService $reports;

    public function __construct(?ReportService $reports = null)
    {
        $this->reports = $reports ?? new ReportService();
    }

    public function export(string $type, string $format, ReportFilter $filter): void
    {
        $format = strtolower(trim($format));
        if (!in_array($format, ['pdf', 'xlsx'], true))
        {
            throw new \InvalidArgumentException('Formato de exportação inválido.');
        }

        $title = $this->reports->title($type);
        $rows = $this->reports->dataForExport($type, $filter);
        $headers = $this->headersForType($type);
        $filename = $this->buildFilename($type, $format);

        if ($format === 'pdf')
        {
            $this->sendPdf($type, $title, $headers, $rows, $filename, $filter);

            return;
        }

        $this->sendXlsx($type, $title, $headers, $rows, $filename);
    }

    /**
     * @param list<string> $headers
     * @param list<array<string, mixed>> $rows
     */
    private function sendPdf(
        string $type,
        string $title,
        array $headers,
        array $rows,
        string $filename,
        ReportFilter $filter
    ): void
    {
        $html = $this->renderPdfHtml($type, $title, $headers, $rows, $filter);
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $dompdf->output();
        exit;
    }

    /**
     * @param list<string> $headers
     * @param list<array<string, mixed>> $rows
     */
    private function sendXlsx(string $type, string $title, array $headers, array $rows, string $filename): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($title, 0, 31));

        $kpis = $this->buildKpis($type, $rows);
        $lastCol = $this->columnLetter(max(1, count($headers)));

        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->setCellValue('A2', 'Gerado em ' . DateHelper::nowBr());
        $sheet->mergeCells('A2:' . $lastCol . '2');

        $kpiRow = 4;
        $col = 1;
        foreach ($kpis as $kpi)
        {
            $sheet->setCellValue([$col, $kpiRow], $kpi['label'] . ': ' . $kpi['value']);
            $col++;
        }

        $headerRow = 6;
        $col = 1;
        foreach ($headers as $header)
        {
            $sheet->setCellValue([$col, $headerRow], $header);
            $col++;
        }

        $rowNum = $headerRow + 1;
        foreach ($rows as $row)
        {
            $col = 1;
            foreach ($this->rowValuesForType($type, $row) as $value)
            {
                $sheet->setCellValue([$col, $rowNum], $value);
                $col++;
            }
            $rowNum++;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'report_');
        if ($tmp === false)
        {
            throw new \RuntimeException('Não foi possível criar arquivo temporário.');
        }
        $path = $tmp . '.xlsx';
        rename($tmp, $path);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($path);
        @unlink($path);
        exit;
    }

    /**
     * @param list<string> $headers
     * @param list<array<string, mixed>> $rows
     */
    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array{label: string, value: string}>
     */
    private function buildKpis(string $type, array $rows): array
    {
        $count = count($rows);
        $kpis = [
            ['label' => 'Registros', 'value' => (string) $count],
        ];

        if ($count === 0)
        {
            return $kpis;
        }

        return match ($type)
        {
            ReportService::TYPE_SALES_PERIOD => array_merge($kpis, [
                [
                    'label' => 'Pedidos',
                    'value' => (string) array_sum(array_map(static fn(array $r): int => (int) ($r['order_count'] ?? 0), $rows)),
                ],
                [
                    'label' => 'Total faturado',
                    'value' => 'R$ ' . $this->formatMoney((string) array_sum(array_map(
                        static fn(array $r): float => (float) ($r['total_amount'] ?? 0),
                        $rows
                    ))),
                ],
            ]),
            ReportService::TYPE_SALES_CUSTOMER => array_merge($kpis, [
                [
                    'label' => 'Total faturado',
                    'value' => 'R$ ' . $this->formatMoney((string) array_sum(array_map(
                        static fn(array $r): float => (float) ($r['total_amount'] ?? 0),
                        $rows
                    ))),
                ],
            ]),
            ReportService::TYPE_SALES_PRODUCT, ReportService::TYPE_TOP_PRODUCTS => array_merge($kpis, [
                [
                    'label' => 'Unidades vendidas',
                    'value' => (string) array_sum(array_map(static fn(array $r): int => (int) ($r['quantity_sold'] ?? 0), $rows)),
                ],
                [
                    'label' => 'Receita total',
                    'value' => 'R$ ' . $this->formatMoney((string) array_sum(array_map(
                        static fn(array $r): float => (float) ($r['total_amount'] ?? 0),
                        $rows
                    ))),
                ],
            ]),
            ReportService::TYPE_LOW_STOCK => array_merge($kpis, [
                [
                    'label' => 'Itens críticos',
                    'value' => (string) $count,
                ],
            ]),
            ReportService::TYPE_CASH_FLOW => array_merge($kpis, [
                [
                    'label' => 'Entradas',
                    'value' => 'R$ ' . $this->formatMoney((string) array_sum(array_map(
                        static fn(array $r): float => ($r['type'] ?? '') === 'entrada' ? (float) ($r['amount'] ?? 0) : 0.0,
                        $rows
                    ))),
                ],
                [
                    'label' => 'Saídas',
                    'value' => 'R$ ' . $this->formatMoney((string) array_sum(array_map(
                        static fn(array $r): float => ($r['type'] ?? '') === 'saida' ? (float) ($r['amount'] ?? 0) : 0.0,
                        $rows
                    ))),
                ],
            ]),
            default => $kpis,
        };
    }

    /**
     * @param list<string> $headers
     * @param array<int, array<string, mixed>> $rows
     */
    private function renderPdfHtml(
        string $type,
        string $title,
        array $headers,
        array $rows,
        ReportFilter $filter
    ): string
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $appName = (string) ($config['app_name'] ?? 'Mini ERP');
        $period = $this->formatPeriodLabel($filter);
        $generated = DateHelper::nowBr();
        $kpis = $this->buildKpis($type, $rows);
        $chartBase64 = (new ReportChartImageService())->renderBase64ForReport($type, $filter);

        ob_start();
?>
        <!DOCTYPE html>
        <html lang="pt-BR">

        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: DejaVu Sans, sans-serif;
                    font-size: 11px;
                    color: #222;
                }

                .report-header {
                    border-bottom: 2px solid #0ea5e9;
                    padding-bottom: 8px;
                    margin-bottom: 14px;
                }

                h1 {
                    font-size: 18px;
                    margin: 0 0 4px;
                    color: #0f172a;
                }

                .meta {
                    color: #555;
                    font-size: 10px;
                }

                .kpi-row {
                    width: 100%;
                    margin-bottom: 14px;
                }

                .kpi-box {
                    display: inline-block;
                    width: 23%;
                    margin-right: 1%;
                    padding: 8px 10px;
                    background: #f0f9ff;
                    border: 1px solid #bae6fd;
                    border-radius: 6px;
                    vertical-align: top;
                }

                .kpi-label {
                    font-size: 9px;
                    color: #64748b;
                    text-transform: uppercase;
                }

                .kpi-value {
                    font-size: 14px;
                    font-weight: bold;
                    color: #0f172a;
                }

                .summary {
                    font-size: 10px;
                    color: #475569;
                    margin-bottom: 12px;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                }

                th,
                td {
                    border: 1px solid #ccc;
                    padding: 6px 8px;
                    text-align: left;
                }

                th {
                    background: #e2e8f0;
                    font-weight: bold;
                    font-size: 10px;
                }

                tr:nth-child(even) td {
                    background: #fafafa;
                }

                .text-end {
                    text-align: right;
                }

                .footer {
                    margin-top: 16px;
                    font-size: 9px;
                    color: #94a3b8;
                    text-align: center;
                    border-top: 1px solid #e2e8f0;
                    padding-top: 8px;
                }

                .chart-image {
                    width: 100%;
                    max-height: 220px;
                    margin-bottom: 14px;
                }
            </style>
        </head>

        <body>
            <div class="report-header">
                <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="meta">
                    <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> · Relatório gerencial · <?= htmlspecialchars($generated, ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($period !== ''): ?> · <?= htmlspecialchars($period, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                </div>
            </div>
            <div class="summary">Resumo executivo com indicadores consolidados do período filtrado.</div>
            <div class="kpi-row">
                <?php foreach ($kpis as $kpi): ?>
                    <div class="kpi-box">
                        <div class="kpi-label"><?= htmlspecialchars($kpi['label'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="kpi-value"><?= htmlspecialchars($kpi['value'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($chartBase64 !== null): ?>
                <img class="chart-image" src="data:image/png;base64,<?= $chartBase64 ?>" alt="Gráfico do relatório">
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($headers as $h): ?>
                            <th><?= htmlspecialchars($h, ENT_QUOTES, 'UTF-8') ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows === []): ?>
                        <tr>
                            <td colspan="<?= count($headers) ?>">Nenhum registro no período.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($this->rowValuesForType($type, $row) as $i => $val): ?>
                                    <?php $isMoney = str_contains((string) $headers[$i], 'R$') || in_array($headers[$i], ['Valor', 'Receita', 'Entrada', 'Saída', 'Saldo'], true); ?>
                                    <td class="<?= $isMoney ? 'text-end' : '' ?>"><?= htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="footer">
                <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> — Documento gerado automaticamente. Uso interno.
            </div>
        </body>

        </html>
<?php

        return (string) ob_get_clean();
    }

    /**
     * @return list<string>
     */
    private function headersForType(string $type): array
    {
        return match ($type)
        {
            ReportService::TYPE_SALES_PERIOD => ['Data', 'Pedidos', 'Total (R$)'],
            ReportService::TYPE_SALES_CUSTOMER => ['Cliente', 'Pedidos', 'Total (R$)'],
            ReportService::TYPE_SALES_PRODUCT, ReportService::TYPE_TOP_PRODUCTS => [
                'Produto',
                'SKU',
                'Tipo',
                'Qtd.',
                'Receita (R$)',
            ],
            ReportService::TYPE_LOW_STOCK => ['Produto', 'SKU', 'Categoria', 'Estoque', 'Mínimo'],
            ReportService::TYPE_CASH_FLOW => ['Data', 'Tipo', 'Valor (R$)', 'Forma', 'Descrição'],
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $row
     * @return list<string|int|float>
     */
    private function rowValuesForType(string $type, array $row): array
    {
        return match ($type)
        {
            ReportService::TYPE_SALES_PERIOD => [
                $this->formatDate((string) ($row['period_date'] ?? '')),
                (int) ($row['order_count'] ?? 0),
                $this->formatMoney((string) ($row['total_amount'] ?? '0')),
            ],
            ReportService::TYPE_SALES_CUSTOMER => [
                (string) ($row['customer_name'] ?? ''),
                (int) ($row['order_count'] ?? 0),
                $this->formatMoney((string) ($row['total_amount'] ?? '0')),
            ],
            ReportService::TYPE_SALES_PRODUCT, ReportService::TYPE_TOP_PRODUCTS => [
                (string) ($row['product_name'] ?? ''),
                (string) ($row['sku'] ?? ''),
                ($row['product_type'] ?? '') === 'service' ? 'Serviço' : 'Produto',
                (int) ($row['quantity_sold'] ?? 0),
                $this->formatMoney((string) ($row['total_amount'] ?? '0')),
            ],
            ReportService::TYPE_LOW_STOCK => [
                (string) ($row['product_name'] ?? ''),
                (string) ($row['sku'] ?? ''),
                (string) ($row['category_name'] ?? '—'),
                (int) ($row['stock'] ?? 0),
                (int) ($row['min_stock'] ?? 0),
            ],
            ReportService::TYPE_CASH_FLOW => [
                DateHelper::toBrDateTime((string) ($row['occurred_at'] ?? 'now')),
                CashFlow::typeLabel((string) ($row['type'] ?? '')),
                $this->formatMoney((string) ($row['amount'] ?? '0')),
                isset($row['payment_method']) && $row['payment_method'] !== null
                    ? Payment::methodLabel((string) $row['payment_method'])
                    : '—',
                (string) ($row['description'] ?? '—'),
            ],
            default => [],
        };
    }

    private function formatPeriodLabel(ReportFilter $filter): string
    {
        $parts = [];
        if ($filter->dateFrom !== null && $filter->dateFrom !== '')
        {
            $parts[] = 'De ' . $this->formatDate($filter->dateFrom);
        }
        if ($filter->dateTo !== null && $filter->dateTo !== '')
        {
            $parts[] = 'Até ' . $this->formatDate($filter->dateTo);
        }

        return implode(' ', $parts);
    }

    private function formatDate(string $iso): string
    {
        return DateHelper::toBrDate($iso);
    }

    private function formatMoney(string $value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }

    private function buildFilename(string $type, string $format): string
    {
        $slug = preg_replace('/[^a-z0-9-]/', '', str_replace('_', '-', $type)) ?? $type;
        $ext = $format === 'pdf' ? 'pdf' : 'xlsx';

        return 'relatorio-' . $slug . '-' . date('Ymd-His') . '.' . $ext;
    }

    private function columnLetter(int $count): string
    {
        $letters = '';
        $n = max(1, $count);
        while ($n > 0)
        {
            $n--;
            $letters = chr(65 + ($n % 26)) . $letters;
            $n = (int) floor($n / 26);
        }

        return $letters;
    }
}
