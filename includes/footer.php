<?php
declare(strict_types=1);
/**
 * FinTrack Pro — Footer
 *
 * Dimuat di bagian bawah setiap halaman aplikasi.
 * Memuat JS yang dibutuhkan secara terurut:
 *   1. sidebar.js   → mobile sidebar toggle
 *   2. app.js       → global UI helpers (flash autohide, delete confirm, dll)
 *   3. charts.js    → Chart.js wrapper (hanya jika halaman set $useCharts = true)
 *
 * Penggunaan di halaman yang butuh chart:
 *   <?php $useCharts = true; ?>
 *   ...
 *   <?php include __DIR__ . '/../includes/footer.php'; ?>
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}
$_baseUrl = rtrim(BASE_URL, '/');
?>
<?php if (!empty($useCharts)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script src="<?= htmlspecialchars($_baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/js/charts.js"></script>
<?php endif; ?>
<script src="<?= htmlspecialchars($_baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/js/sidebar.js"></script>
<script src="<?= htmlspecialchars($_baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/js/app.js"></script>
