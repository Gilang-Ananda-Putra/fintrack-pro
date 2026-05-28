/**
 * FinTrack Pro — charts.js
 *
 * Wrapper dan helper functions untuk Chart.js v4.
 * File ini dimuat SETELAH Chart.js CDN (diatur oleh footer.php).
 *
 * Fungsi yang tersedia:
 *  - renderCashFlowChart(canvasId, labels, incomeData, expenseData)
 *  - renderDonutChart(canvasId, labels, data, colors?)
 *  - renderBarChart(canvasId, labels, incomeData, expenseData)
 *
 * Semua fungsi menggunakan color palette brand FinTrack Pro.
 */

'use strict';

// ── Brand Color Palette ───────────────────────────────────────────────────────
var FT_COLORS = {
    primary:   '#003ec7',   // biru utama
    secondary: '#006e2f',   // hijau (income)
    error:     '#ba1a1a',   // merah (expense)
    outline:   '#737688',   // abu
    surface:   '#f2f3ff',   // latar terang

    // Palet untuk donut/kategori (urutan prioritas)
    palette: [
        '#003ec7',  // biru
        '#006e2f',  // hijau
        '#0ea5e9',  // biru muda
        '#8b5cf6',  // ungu
        '#f59e0b',  // kuning
        '#ef4444',  // merah
        '#14b8a6',  // teal
        '#ec4899',  // pink
        '#6366f1',  // indigo
        '#84cc16',  // lime
    ]
};

// ── Global Chart Defaults ─────────────────────────────────────────────────────
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family  = "'Inter', sans-serif";
    Chart.defaults.font.size    = 12;
    Chart.defaults.color        = '#737688';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyleWidth = 10;
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.backgroundColor = '#283044';
    Chart.defaults.plugins.tooltip.titleColor = '#eef0ff';
    Chart.defaults.plugins.tooltip.bodyColor  = '#c3c5d9';
}

// ── Helper: format angka ke IDR singkat ──────────────────────────────────────
function _ftFormatIDR(value) {
    if (value >= 1000000000) {
        return 'Rp ' + (value / 1000000000).toFixed(1) + 'M';
    }
    if (value >= 1000000) {
        return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
    }
    if (value >= 1000) {
        return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
    }
    return 'Rp ' + value;
}

// ── Helper: buat gradient fill untuk line chart ───────────────────────────────
function _ftMakeGradient(ctx, color, height) {
    height = height || 280;
    var grad = ctx.createLinearGradient(0, 0, 0, height);
    // Ubah hex ke rgba
    var r = parseInt(color.slice(1, 3), 16);
    var g = parseInt(color.slice(3, 5), 16);
    var b = parseInt(color.slice(5, 7), 16);
    grad.addColorStop(0,   'rgba(' + r + ',' + g + ',' + b + ',0.18)');
    grad.addColorStop(1,   'rgba(' + r + ',' + g + ',' + b + ',0)');
    return grad;
}

// ─────────────────────────────────────────────────────────────────────────────
/**
 * renderCashFlowChart
 * Line chart dua dataset: Pemasukan vs Pengeluaran
 *
 * @param {string}   canvasId     - ID elemen <canvas>
 * @param {string[]} labels       - Label sumbu X (nama bulan, dll)
 * @param {number[]} incomeData   - Data pemasukan per label
 * @param {number[]} expenseData  - Data pengeluaran per label
 */
function renderCashFlowChart(canvasId, labels, incomeData, expenseData) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) { return; }

    var ctx = canvas.getContext('2d');

    var incomeGrad  = _ftMakeGradient(ctx, FT_COLORS.secondary);
    var expenseGrad = _ftMakeGradient(ctx, FT_COLORS.error);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: incomeData,
                    borderColor: FT_COLORS.secondary,
                    backgroundColor: incomeGrad,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: FT_COLORS.secondary,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                },
                {
                    label: 'Pengeluaran',
                    data: expenseData,
                    borderColor: FT_COLORS.error,
                    backgroundColor: expenseGrad,
                    borderWidth: 2,
                    borderDash: [6, 3],
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: FT_COLORS.error,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function (ctx) {
                            return ' ' + ctx.dataset.label + ': ' + _ftFormatIDR(ctx.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { maxRotation: 0 }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(195,197,217,0.25)',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function (value) {
                            return _ftFormatIDR(value);
                        }
                    }
                }
            },
            interaction: { mode: 'index', intersect: false }
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────
/**
 * renderDonutChart
 * Donut chart untuk distribusi kategori pengeluaran
 *
 * @param {string}   canvasId  - ID elemen <canvas>
 * @param {string[]} labels    - Nama kategori
 * @param {number[]} data      - Nilai per kategori
 * @param {string[]} [colors] - Opsional: override warna (hex array)
 */
function renderDonutChart(canvasId, labels, data, colors) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) { return; }

    var bgColors = colors || FT_COLORS.palette.slice(0, data.length);

    new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: bgColors,
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                            var pct   = total > 0 ? Math.round(ctx.parsed / total * 100) : 0;
                            return ' ' + ctx.label + ': ' + _ftFormatIDR(ctx.parsed) + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────
/**
 * renderBarChart
 * Bar chart grouped: Pemasukan vs Pengeluaran per periode
 *
 * @param {string}   canvasId
 * @param {string[]} labels
 * @param {number[]} incomeData
 * @param {number[]} expenseData
 */
function renderBarChart(canvasId, labels, incomeData, expenseData) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) { return; }

    new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: incomeData,
                    backgroundColor: FT_COLORS.secondary,
                    borderRadius: 4,
                    borderSkipped: false,
                },
                {
                    label: 'Pengeluaran',
                    data: expenseData,
                    backgroundColor: FT_COLORS.error,
                    borderRadius: 4,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', align: 'end' },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return ' ' + ctx.dataset.label + ': ' + _ftFormatIDR(ctx.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false, drawBorder: false } },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(195,197,217,0.25)', drawBorder: false },
                    ticks: { callback: function (v) { return _ftFormatIDR(v); } }
                }
            }
        }
    });
}
