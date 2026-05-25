(function () {
    'use strict';

    var root = document.getElementById('dashboardCharts');
    if (!root || typeof Chart === 'undefined') {
        return;
    }

    var dailyEl = document.getElementById('chartDailyRevenue');
    var monthlyEl = document.getElementById('chartMonthlySales');
    var topEl = document.getElementById('chartTopProducts');

    var palette = {
        primary: 'rgba(14, 165, 233, 0.85)',
        primaryFill: 'rgba(14, 165, 233, 0.15)',
        secondary: 'rgba(99, 102, 241, 0.85)',
        secondaryFill: 'rgba(99, 102, 241, 0.12)',
        accent: 'rgba(34, 197, 94, 0.85)',
        grid: 'rgba(148, 163, 184, 0.25)',
        text: '#64748b'
    };

    var baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: palette.text, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }
            },
            y: {
                grid: { color: palette.grid },
                ticks: { color: palette.text }
            }
        }
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

    var daily = parsePayload('dashboardDailyData');
    if (dailyEl && daily && daily.labels) {
        new Chart(dailyEl, {
            type: 'line',
            data: {
                labels: daily.labels,
                datasets: [{
                    label: 'Faturamento (R$)',
                    data: daily.amounts,
                    borderColor: palette.primary,
                    backgroundColor: palette.primaryFill,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: Object.assign({}, baseOptions, {
                scales: {
                    x: baseOptions.scales.x,
                    y: Object.assign({}, baseOptions.scales.y, {
                        ticks: {
                            color: palette.text,
                            callback: function (v) {
                                return 'R$ ' + Number(v).toLocaleString('pt-BR');
                            }
                        }
                    })
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var val = ctx.parsed.y || 0;
                                return 'R$ ' + val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        }
                    }
                }
            })
        });
    }

    var monthly = parsePayload('dashboardMonthlyData');
    if (monthlyEl && monthly && monthly.labels) {
        new Chart(monthlyEl, {
            type: 'bar',
            data: {
                labels: monthly.labels,
                datasets: [{
                    label: 'Vendas (R$)',
                    data: monthly.amounts,
                    backgroundColor: palette.secondary,
                    borderRadius: 6,
                    maxBarThickness: 42
                }]
            },
            options: Object.assign({}, baseOptions, {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var val = ctx.parsed.y || 0;
                                return 'R$ ' + val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    x: Object.assign({}, baseOptions.scales.x, {
                        ticks: { color: palette.text, maxRotation: 45, minRotation: 0, autoSkip: true, maxTicksLimit: 12 }
                    }),
                    y: Object.assign({}, baseOptions.scales.y, {
                        ticks: {
                            color: palette.text,
                            callback: function (v) {
                                if (v >= 1000) {
                                    return 'R$ ' + (v / 1000).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + 'k';
                                }
                                return 'R$ ' + Number(v).toLocaleString('pt-BR');
                            }
                        }
                    })
                }
            })
        });
    }

    var top = parsePayload('dashboardTopProductsData');
    if (topEl && top && top.labels) {
        new Chart(topEl, {
            type: 'bar',
            data: {
                labels: top.labels,
                datasets: [{
                    label: 'Quantidade',
                    data: top.quantities,
                    backgroundColor: palette.accent,
                    borderRadius: 6,
                    maxBarThickness: 36
                }]
            },
            options: Object.assign({}, baseOptions, {
                indexAxis: 'y',
                scales: {
                    x: Object.assign({}, baseOptions.scales.x, {
                        position: 'bottom',
                        ticks: { color: palette.text, precision: 0 }
                    }),
                    y: Object.assign({}, baseOptions.scales.y, {
                        grid: { display: false },
                        ticks: { color: palette.text, autoSkip: false }
                    })
                }
            })
        });
    }
})();
