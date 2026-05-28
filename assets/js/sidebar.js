/**
 * FinTrack Pro — sidebar.js
 *
 * Mengelola behavior sidebar untuk tampilan mobile:
 * - Toggle buka/tutup sidebar saat tombol hamburger ditekan
 * - Tutup sidebar saat backdrop (overlay gelap) diklik
 * - Tutup sidebar saat item navigasi diklik (UX mobile)
 * - Tutup sidebar saat tombol Escape ditekan
 *
 * Elemen yang dibutuhkan di DOM (lihat includes/sidebar.php & topbar.php):
 *   #app-sidebar     → elemen aside sidebar
 *   #sidebar-backdrop → overlay gelap di belakang sidebar
 *   #sidebar-toggle  → tombol hamburger di topbar (mobile only)
 */

'use strict';

(function () {
    // ── Referensi elemen DOM ────────────────────────────────────────────────
    const sidebar  = document.getElementById('app-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const toggle   = document.getElementById('sidebar-toggle');

    // Guard: jika elemen tidak ada (halaman tanpa layout), hentikan eksekusi
    if (!sidebar || !backdrop || !toggle) {
        return;
    }

    // ── Konstanta class Tailwind ────────────────────────────────────────────
    const HIDDEN_CLASS  = '-translate-x-full';
    const SHOWN_CLASS   = 'translate-x-0';

    // ── Fungsi buka sidebar ─────────────────────────────────────────────────
    function openSidebar() {
        sidebar.classList.remove(HIDDEN_CLASS);
        sidebar.classList.add(SHOWN_CLASS);
        backdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // cegah scroll body
        toggle.setAttribute('aria-expanded', 'true');
    }

    // ── Fungsi tutup sidebar ────────────────────────────────────────────────
    function closeSidebar() {
        sidebar.classList.add(HIDDEN_CLASS);
        sidebar.classList.remove(SHOWN_CLASS);
        backdrop.classList.add('hidden');
        document.body.style.overflow = '';
        toggle.setAttribute('aria-expanded', 'false');
    }

    // ── Toggle: buka jika tertutup, tutup jika terbuka ─────────────────────
    function toggleSidebar() {
        const isOpen = !sidebar.classList.contains(HIDDEN_CLASS);
        isOpen ? closeSidebar() : openSidebar();
    }

    // ── Event Listeners ─────────────────────────────────────────────────────

    // Tombol hamburger
    toggle.addEventListener('click', toggleSidebar);

    // Backdrop overlay
    backdrop.addEventListener('click', closeSidebar);

    // Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !sidebar.classList.contains(HIDDEN_CLASS)) {
            closeSidebar();
        }
    });

    // Klik link navigasi di sidebar (pada mobile, sidebar harus tertutup setelah navigasi)
    const navLinks = sidebar.querySelectorAll('a[href]');
    navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            // Hanya tutup jika ukuran layar mobile (sidebar overlay aktif)
            if (window.innerWidth < 768) {
                closeSidebar();
            }
        });
    });

    // Saat resize ke desktop, reset state sidebar
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 768) {
            // Di desktop sidebar selalu terlihat, reset style overflow body
            document.body.style.overflow = '';
            backdrop.classList.add('hidden');
        }
    });

}());
