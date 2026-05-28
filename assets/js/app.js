/**
 * FinTrack Pro — app.js
 *
 * Inisialisasi global dan helper UI yang dipakai di seluruh aplikasi.
 * Dimuat oleh footer.php setelah sidebar.js.
 *
 * Fitur:
 *  1. Auto-hide flash message setelah 4 detik
 *  2. Konfirmasi hapus via [data-confirm="..."]
 *  3. Input amount: hanya izinkan angka dan titik/koma
 *  4. Format tanggal ke format Indonesia di elemen [data-date]
 *  5. Highlight baris tabel aktif saat di-hover
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {

    // ── 1. Auto-hide flash message ──────────────────────────────────────────
    var flashMessages = document.querySelectorAll('[data-flash], #flash-message, .flash-message');

    flashMessages.forEach(function (el) {
        var delay = parseInt(el.dataset.flashDelay, 10) || 4000;

        setTimeout(function () {
            el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            el.style.opacity    = '0';
            el.style.transform  = 'translateY(-8px)';

            setTimeout(function () {
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, 400);
        }, delay);
    });

    // ── 2. Konfirmasi sebelum hapus (data-confirm attribute) ────────────────
    // Penggunaan: <button data-confirm="Yakin hapus ini?">Hapus</button>
    // Atau pada <form data-confirm="..."> untuk konfirmasi sebelum submit
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        var eventName = (el.tagName === 'FORM') ? 'submit' : 'click';

        el.addEventListener(eventName, function (e) {
            var message = el.dataset.confirm || 'Yakin ingin melanjutkan?';
            if (!window.confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // ── 3. Validasi input amount (hanya angka, koma, titik) ─────────────────
    document.querySelectorAll('input[data-type="currency"], input.input-currency').forEach(function (input) {
        input.addEventListener('input', function () {
            // Biarkan browser handle type="number", ini untuk type="text" custom
            var val = input.value.replace(/[^0-9.,]/g, '');
            input.value = val;
        });

        input.addEventListener('blur', function () {
            // Simpan nilai raw ke dataset untuk diproses server
            input.dataset.rawValue = input.value.replace(/[.,]/g, '');
        });
    });

    // ── 4. Format elemen [data-date] ke format Indonesia ────────────────────
    // Penggunaan: <span data-date="2025-01-15"></span>
    document.querySelectorAll('[data-date]').forEach(function (el) {
        var raw = el.dataset.date;
        if (!raw) { return; }

        try {
            var date = new Date(raw);
            if (!isNaN(date.getTime())) {
                el.textContent = date.toLocaleDateString('id-ID', {
                    day:   '2-digit',
                    month: 'long',
                    year:  'numeric'
                });
            }
        } catch (err) {
            // Biarkan teks asli jika parsing gagal
        }
    });

    // ── 5. Tombol "kembali" yang aman ───────────────────────────────────────
    document.querySelectorAll('[data-back]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = btn.dataset.back || '/';
            }
        });
    });

    // ── 6. Highlight row tabel yang diklik (opsional, UX) ───────────────────
    document.querySelectorAll('table tbody tr').forEach(function (row) {
        row.style.cursor = 'default';
    });

    // ── 7. Tooltip sederhana via [title] attribute ───────────────────────────
    // Browser default tooltip sudah cukup, tidak perlu custom JS untuk ini.

    // ── Selesai ──────────────────────────────────────────────────────────────
    if (typeof console !== 'undefined' && console.log) {
        console.log('[FinTrack Pro] App initialized.');
    }

});

// ── Utility global: format angka ke IDR (bisa dipanggil dari mana saja) ──────
window.ftFormatIDR = function (amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(amount);
};
