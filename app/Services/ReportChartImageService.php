<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReportFilter;

/**
 * Gera PNG (base64) para embutir em PDF a partir dos mesmos dados dos gráficos da tela.
 */
final class ReportChartImageService
{
    private const WIDTH = 900;

    private const HEIGHT = 280;

    private const PADDING = 48;

    private ReportChartService $charts;

    public function __construct(?ReportChartService $charts = null)
    {
        $this->charts = $charts ?? new ReportChartService();
    }

    public function renderBase64ForReport(string $type, ReportFilter $filter): ?string
    {
        if (!extension_loaded('gd'))
        {
            return null;
        }

        $bundle = $this->charts->build($type, $filter);
        if ($bundle === null || ($bundle['charts'] ?? []) === [])
        {
            return null;
        }

        $chart = $bundle['charts'][0];
        $payload = $chart['payload'] ?? null;
        if (!is_array($payload))
        {
            return null;
        }

        $png = $this->renderPayload(
            (string) ($chart['title'] ?? 'Gráfico'),
            (string) ($payload['kind'] ?? 'bar'),
            (array) ($payload['labels'] ?? []),
            (array) ($payload['datasets'] ?? [])
        );

        return $png !== null ? base64_encode($png) : null;
    }

    /**
     * @param list<string> $labels
     * @param list<array{label: string, data: list<float|int>, color?: string}> $datasets
     */
    private function renderPayload(string $title, string $kind, array $labels, array $datasets): ?string
    {
        if ($labels === [] || $datasets === [])
        {
            return null;
        }

        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if ($image === false)
        {
            return null;
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $grid = imagecolorallocate($image, 226, 232, 240);
        $text = imagecolorallocate($image, 51, 65, 85);
        $muted = imagecolorallocate($image, 100, 116, 139);
        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $white);

        imagestring($image, 5, self::PADDING, 12, $this->truncate($title, 42), $text);

        $plotTop = 44;
        $plotBottom = self::HEIGHT - 28;
        $plotLeft = self::PADDING + 80;
        $plotRight = self::WIDTH - self::PADDING;
        $plotHeight = $plotBottom - $plotTop;
        $plotWidth = $plotRight - $plotLeft;

        imageline($image, $plotLeft, $plotTop, $plotLeft, $plotBottom, $grid);
        imageline($image, $plotLeft, $plotBottom, $plotRight, $plotBottom, $grid);

        $count = count($labels);
        if ($count === 0)
        {
            imagedestroy($image);

            return null;
        }

        if ($kind === 'line')
        {
            $this->drawLineChart($image, $labels, $datasets[0], $plotLeft, $plotTop, $plotWidth, $plotHeight, $muted, $text);
        }
        elseif ($kind === 'stackedBar')
        {
            $this->drawStackedBars($image, $labels, $datasets, $plotLeft, $plotTop, $plotWidth, $plotHeight, $muted, $text);
        }
        else
        {
            $this->drawHorizontalBars($image, $labels, $datasets[0], $plotLeft, $plotTop, $plotWidth, $plotHeight, $muted, $text);
        }

        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return is_string($bytes) && $bytes !== '' ? $bytes : null;
    }

    /**
     * @param array{label: string, data: list<float|int>, color?: string} $dataset
     */
    private function drawHorizontalBars(
        \GdImage $image,
        array $labels,
        array $dataset,
        int $left,
        int $top,
        int $width,
        int $height,
        int $muted,
        int $text
    ): void
    {
        $values = array_map(static fn(mixed $v): float => (float) $v, $dataset['data'] ?? []);
        $max = max(1.0, ...array_map('abs', $values));
        $barColor = imagecolorallocate($image, 14, 165, 233);
        $count = count($labels);
        $gap = 6;
        $barHeight = max(8, (int) floor(($height - ($count - 1) * $gap) / $count));

        for ($i = 0; $i < $count; $i++)
        {
            $y = $top + $i * ($barHeight + $gap);
            $label = $this->truncate((string) ($labels[$i] ?? ''), 12);
            imagestring($image, 2, 8, $y + (int) floor($barHeight / 2) - 6, $label, $muted);
            $value = $values[$i] ?? 0.0;
            $barW = (int) round(($width - 20) * ($value / $max));
            imagefilledrectangle($image, $left, $y, $left + $barW, $y + $barHeight, $barColor);
        }
    }

    /**
     * @param list<array{label: string, data: list<float|int>, color?: string}> $datasets
     */
    private function drawStackedBars(
        \GdImage $image,
        array $labels,
        array $datasets,
        int $left,
        int $top,
        int $width,
        int $height,
        int $muted,
        int $text
    ): void
    {
        $count = count($labels);
        $totals = [];
        for ($i = 0; $i < $count; $i++)
        {
            $sum = 0.0;
            foreach ($datasets as $ds)
            {
                $sum += (float) (($ds['data'][$i] ?? 0));
            }
            $totals[$i] = $sum;
        }
        $max = max(1.0, ...$totals);
        $gap = 8;
        $barWidth = max(12, (int) floor(($width - ($count - 1) * $gap) / max(1, $count)));
        $colors = [
            imagecolorallocate($image, 14, 165, 233),
            imagecolorallocate($image, 239, 68, 68),
        ];

        for ($i = 0; $i < $count; $i++)
        {
            $x = $left + $i * ($barWidth + $gap);
            $label = $this->truncate((string) ($labels[$i] ?? ''), 8);
            imagestring($image, 2, $x, $top + $height + 4, $label, $muted);
            $stackY = $top + $height;
            foreach ($datasets as $di => $ds)
            {
                $value = (float) ($ds['data'][$i] ?? 0);
                $h = (int) round($height * ($value / $max));
                $stackY -= $h;
                $color = $colors[$di % count($colors)];
                imagefilledrectangle($image, $x, $stackY, $x + $barWidth, $stackY + $h, $color);
            }
        }
    }

    /**
     * @param array{label: string, data: list<float|int>} $dataset
     */
    private function drawLineChart(
        \GdImage $image,
        array $labels,
        array $dataset,
        int $left,
        int $top,
        int $width,
        int $height,
        int $muted,
        int $text
    ): void
    {
        $values = array_map(static fn(mixed $v): float => (float) $v, $dataset['data'] ?? []);
        $count = count($values);
        if ($count < 2)
        {
            return;
        }

        $max = max(1.0, ...$values);
        $lineColor = imagecolorallocate($image, 14, 165, 233);
        $fillColor = imagecolorallocatealpha($image, 14, 165, 233, 110);
        $step = $width / max(1, $count - 1);
        $points = [];

        for ($i = 0; $i < $count; $i++)
        {
            $x = (int) round($left + $i * $step);
            $y = (int) round($top + $height - ($values[$i] / $max) * $height);
            $points[] = [$x, $y];
            if ($i % max(1, (int) floor($count / 6)) === 0)
            {
                imagestring($image, 2, $x - 12, $top + $height + 4, $this->truncate((string) ($labels[$i] ?? ''), 8), $muted);
            }
        }

        for ($i = 0; $i < count($points) - 1; $i++)
        {
            imageline($image, $points[$i][0], $points[$i][1], $points[$i + 1][0], $points[$i + 1][1], $lineColor);
            imagefilledellipse($image, $points[$i][0], $points[$i][1], 6, 6, $lineColor);
        }
        imagefilledellipse($image, $points[$count - 1][0], $points[$count - 1][1], 6, 6, $lineColor);
        unset($fillColor);
    }

    private function truncate(string $value, int $max): string
    {
        if (function_exists('mb_strlen') && function_exists('mb_substr'))
        {
            return mb_strlen($value) > $max ? mb_substr($value, 0, $max - 1) . '…' : $value;
        }

        return strlen($value) > $max ? substr($value, 0, $max - 1) . '…' : $value;
    }
}
