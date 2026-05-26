(function () {
    'use strict';

    var root = document.getElementById('reportCharts');
    if (!root || typeof Chart === 'undefined') {
        return;
    }

    var palette = {
        primary: 'rgba(14, 165, 233, 0.85)',
        primaryFill: 'rgba(14, 165, 233, 0.15)',
        secondary: 'rgba(99, 102, 241, 0.85)',
        accent: 'rgba(34, 197, 94, 0.85)',
        danger: 'rgba(239, 68, 68, 0.85)',
        grid: 'rgba(148, 163, 184, 0.25)',
        text: '#64748b'
    };

    var colorMap = {
        primary: palette.primary,
        secondary: palette.secondary,
        accent: palette.accent,
        danger: palette.danger
    };

    function parsePayload(id) {
        var node = document.getElementById(id);
        if (!node || !node.textContent) {
            return null;
        }
        try {
            return JSON.parse(node.textContent);
        } catch (e) {
            return null;
        }
    }

    function currencyTooltip() {
        return {
            callbacks: {
                label: function (ctx) {
                    var val = ctx.parsed.y !== undefined ? ctx.parsed.y : ctx.parsed.x;
                    if (val === null || val === undefined) {
                        return '';
                    }
                    return (ctx.dataset.label || '') + ': R$ ' + Number(val).toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }
        };
    }

    function numberTooltip() {
        return {
            callbacks: {
                label: function (ctx) {
                    var val = ctx.parsed.y !== undefined ? ctx.parsed.y : ctx.parsed.x;
                    return (ctx.dataset.label || '') + ': ' + Number(val).toLocaleString('pt-BR');
                }
            }
        };
    }

    function baseScales(horizontal) {
        if (horizontal) {
            return {
                x: {
                    grid: { color: palette.grid },
                    ticks: { color: palette.text }
                },
                y: {
                    grid: { display: false },
                    ticks: { color: palette.text, autoSkip: false }
                }
            };
        }
        return {
            x: {
                grid: { display: false },
                ticks: { color: palette.text, maxRotation: 45, minRotation: 0, autoSkip: true, maxTicksLimit: 14 }
            },
            y: {
                grid: { color: palette.grid },
                ticks: { color: palette.text }
            }
        };
    }

    function mapDatasets(meta) {
        return (meta.datasets || []).map(function (ds) {
            var colorKey = ds.color || 'primary';
            var bg = colorMap[colorKey] || palette.primary;
            return {
                label: ds.label || '',
                data: ds.data || [],
                backgroundColor: bg,
                borderColor: bg,
                borderRadius: 6,
                maxBarThickness: 42,
                fill: false
            };
        });
    }

    root.querySelectorAll('canvas[id]').forEach(function (canvas) {
        var dataId = canvas.id + 'Data';
        var meta = parsePayload(dataId);
        if (!meta || !meta.labels || !meta.datasets) {
            return;
        }

        var kind = meta.kind || 'bar';
        var datasets = mapDatasets(meta);
        var hasCurrency = (meta.datasets || []).some(function (d) { return d.currency; });
        var tooltip = hasCurrency ? currencyTooltip() : numberTooltip();
        var horizontal = kind === 'horizontalBar';

        if (kind === 'line') {
            datasets[0].backgroundColor = palette.primaryFill;
            datasets[0].borderColor = palette.primary;
            datasets[0].fill = true;
            datasets[0].tension = 0.35;
            datasets[0].pointRadius = 3;
            datasets[0].pointHoverRadius = 5;
        }

        if (kind === 'stackedBar') {
            kind = 'bar';
            datasets.forEach(function (ds, i) {
                var colorKey = (meta.datasets[i] && meta.datasets[i].color) || (i === 0 ? 'accent' : 'danger');
                ds.backgroundColor = colorMap[colorKey] || palette.accent;
                ds.borderColor = ds.backgroundColor;
            });
        }

        var options = {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: horizontal ? 'y' : 'x',
            plugins: {
                legend: { display: kind === 'bar' && datasets.length > 1 },
                tooltip: tooltip
            },
            scales: baseScales(horizontal)
        };

        if (hasCurrency && !horizontal) {
            options.scales.y.ticks.callback = function (v) {
                if (v >= 1000) {
                    return 'R$ ' + (v / 1000).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + 'k';
                }
                return 'R$ ' + Number(v).toLocaleString('pt-BR');
            };
        }

        if (kind === 'bar' && meta.kind === 'stackedBar') {
            options.scales.x.stacked = true;
            options.scales.y.stacked = true;
        }

        new Chart(canvas, {
            type: kind === 'line' ? 'line' : 'bar',
            data: {
                labels: meta.labels,
                datasets: datasets
            },
            options: options
        });
    });
})();
