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

        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:' . $this->columnLetter(count($headers)) . '1');

        $col = 1;
        foreach ($headers as $header)
        {
            $sheet->setCellValue([$col, 3], $header);
            $col++;
        }

        $rowNum = 4;
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

                h1 {
                    font-size: 16px;
                    margin: 0 0 4px;
                }

                .meta {
                    color: #555;
                    margin-bottom: 12px;
                    font-size: 10px;
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
                    background: #f0f0f0;
                    font-weight: bold;
                }

                tr:nth-child(even) td {
                    background: #fafafa;
                }

                .text-end {
                    text-align: right;
                }
            </style>
        </head>

        <body>
            <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
            <div class="meta">
                <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> · Gerado em <?= htmlspecialchars($generated, ENT_QUOTES, 'UTF-8') ?>
                <?php if ($period !== ''): ?> · <?= htmlspecialchars($period, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
            </div>
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
