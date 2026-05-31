# Analisis Aplikasi FinTrack Pro

Dokumen ini merangkum audit cepat terhadap fitur, kualitas kode, temuan kesalahan, rekomendasi pengembangan, dan tempat pencatatan task untuk aplikasi **FinTrack Pro**.

## 1. Ringkasan Aplikasi

FinTrack Pro adalah aplikasi pencatat keuangan pribadi berbasis PHP native, MySQL, PDO, dan TailwindCSS. Modul utama yang tersedia meliputi autentikasi, dashboard, CRUD transaksi, CRUD kategori, laporan, ekspor laporan, dan pengaturan profil.

## 2. Fitur yang Sudah Ada

| Area | Fitur | Status | Catatan |
| --- | --- | --- | --- |
| Autentikasi | Login manual email/password | Ada | Sudah diperbaiki agar file valid sebagai PHP, menjalankan query user, dan memverifikasi password. |
| Autentikasi | Register manual | Ada | Sudah diperbaiki agar file valid sebagai PHP, validasi password berjalan, form tidak ganda, dan user baru langsung login. |
| Autentikasi | Logout | Ada | Menghapus session lalu redirect. |
| Autentikasi | Google OAuth | Ada | Kode dan migration tersedia, tetapi schema utama belum memasukkan kolom Google OAuth. |
| Dashboard | Ringkasan saldo, income, expense | Ada | Menampilkan total semua transaksi pengguna. |
| Dashboard | Chart cash flow dan donut chart | Ada | Menggunakan Chart.js via CDN. |
| Transaksi | List, search, filter, create, edit, delete | Ada | Sudah memakai prepared statement, validasi kategori milik user, dan CSRF pada aksi mutasi. |
| Kategori | List, create, edit, delete | Ada | Sudah membatasi kategori per user dan menampilkan jumlah transaksi per kategori. |
| Laporan | Filter bulan/tahun dan ringkasan | Ada | Halaman laporan diperbaiki dari file terpotong/parse error. |
| Ekspor | CSV, Excel-compatible HTML, printable PDF page | Ada | Ekspor sudah memakai filter periode dan query user. |
| Settings | Update profil dan password | Ada | Validasi password dan CSRF tersedia. |
| Konfigurasi | `.env.example`, database config, app config | Ada | Sudah ada loader `.env` sederhana dan default lokal. |
| Database | Schema MySQL dan seed demo | Ada | Schema inti tersedia dengan index serta foreign key. |

## 3. Kelebihan

1. **Arsitektur sederhana dan mudah dipahami**: struktur folder dipisah berdasarkan modul (`auth`, `dashboard`, `transactions`, `categories`, `reports`, `settings`, `includes`, `config`).
2. **Menggunakan PDO dan prepared statement** pada mayoritas query sehingga risiko SQL injection lebih rendah.
3. **Proteksi akses halaman** tersedia melalui `includes/auth_check.php` untuk halaman yang membutuhkan login.
4. **CSRF token sudah diterapkan** pada banyak form mutasi data, termasuk login, register, transaksi, kategori, dan settings.
5. **Validasi kepemilikan data** cukup baik pada transaksi dan kategori; query umumnya membatasi operasi dengan `user_id`.
6. **Schema database cukup rapi**: ada foreign key, unique key email, unique kategori per user, dan index untuk transaksi per user/tanggal/type.
7. **UI sudah responsif** karena memanfaatkan TailwindCSS, sidebar mobile, dan layout card/table.
8. **Fitur laporan dan ekspor** sudah membantu kebutuhan utama aplikasi keuangan pribadi.
9. **Google OAuth sudah mulai disiapkan** sehingga aplikasi punya jalur autentikasi modern.

## 4. Kekurangan dan Risiko

1. **Belum ada test otomatis** untuk autentikasi, transaksi, laporan, dan settings. Saat ini validasi utama masih manual lewat `php -l`.
2. **Tidak ada dependency manager / autoloading** seperti Composer, sehingga `require_once` tersebar di banyak file dan sulit diskalakan.
3. **Belum ada routing terpusat**; tiap halaman adalah file PHP langsung. Ini sederhana, tetapi makin sulit dipelihara saat fitur bertambah.
4. **Kode UI dan logic backend masih bercampur** dalam file PHP yang sama. Hal ini menyulitkan test, reuse, dan review.
5. **Konsistensi style kode belum merata**; beberapa file sudah rapi multi-line, sementara file seperti dashboard/sidebar/topbar masih sangat padat satu baris.
6. **Error database masih menampilkan detail exception** melalui `die('Database connection failed: ...')`, yang kurang aman untuk production.
7. **Schema utama belum sinkron dengan fitur Google OAuth** karena kolom `google_id`, `avatar_url`, dan `email_verified` hanya ada di migration terpisah.
8. **Export PDF bukan PDF biner asli**, melainkan halaman HTML printable; nama fitur bisa membingungkan user.
9. **Dashboard menyebut “Monthly Income/Expense”, tetapi query menghitung total semua transaksi**, sehingga label UI berpotensi tidak akurat.
10. **HTML prototype statis masih tersimpan di root** (`dashboard.html`, `transactions.html`, dll.) dan berpotensi membingungkan antara mockup dengan halaman aplikasi aktif.
11. **Belum terlihat rate limiting login/register**, sehingga rawan brute force pada deployment publik.
12. **Belum ada pagination pada transaksi/laporan**, sehingga tabel bisa berat jika data banyak.
13. **Belum ada fitur budget, target tabungan, recurring transaction, atau import bank statement** yang umumnya dibutuhkan aplikasi finance tracker.
14. **Tidak ada audit log** untuk perubahan transaksi/kategori/profil.

## 5. Temuan Kesalahan Sintaks / Penulisan Kode

### 5.1 Sudah Diperbaiki dalam Patch Ini

| Prioritas | File | Masalah | Dampak | Perbaikan |
| --- | --- | --- | --- | --- |
| P0 | `auth/login.php` | File berisi marker diff `@@ ...`, tidak diawali `<?php`, dan sebagian logic query/password hilang. | Login manual tidak berjalan sebagai PHP yang benar dan berisiko menampilkan source-like text. | Menulis ulang file dengan pembuka PHP, session, query user, `password_verify`, regenerate session, CSRF, dan UI form. |
| P0 | `auth/register.php` | File berisi marker diff, tidak diawali `<?php`, validasi password terpotong, form register ganda, serta cabang success/error tidak logis. | Register manual rusak dan alur session user baru tidak konsisten. | Menulis ulang file dengan validasi lengkap, insert user, password hash, regenerate session, dan satu form register. |
| P0 | `reports/index.php` | File terpotong pada baris tabel transaksi sehingga `php -l` menghasilkan `unexpected end of file`. | Halaman laporan tidak dapat dibuka. | Melengkapi baris tabel, `endforeach`, `endif`, penutup table/section/body/html. |
| P1 | `reports/index.php` | Link `Export PDF` tidak memiliki tag `</a>` sebelum link `Export Excel`. | HTML tidak valid dan tombol export dapat saling bersarang. | Menambahkan tag penutup anchor. |

### 5.2 Masih Direkomendasikan untuk Dikerjakan

| Prioritas | Area/File | Masalah | Rekomendasi |
| --- | --- | --- | --- |
| P1 | `config/database.php` | Menampilkan detail error koneksi database ke user. | Ganti dengan pesan generik untuk user dan log detail error ke file/log server. |
| P1 | `fintrack_pro.sql` + `migrations/add_google_oauth_to_users.sql` | Schema utama tidak memuat kolom yang dibutuhkan Google OAuth. | Gabungkan migration OAuth ke schema fresh install atau dokumentasikan urutan setup secara eksplisit. |
| P1 | `dashboard/index.php` | Label “Monthly Income/Expense” tidak sesuai query total semua waktu. | Ubah label menjadi “Total Income/Expense” atau ubah query agar hanya menghitung bulan berjalan. |
| P1 | `reports/export_pdf.php` | Nama export PDF mengarah ke HTML printable, bukan file PDF asli. | Ubah label menjadi “Print / Save PDF” atau gunakan library PDF server-side. |
| P2 | Root `*.html` | Banyak mockup statis tetap ada di root. | Pindahkan ke `docs/mockups/` atau hapus jika tidak digunakan. |
| P2 | `dashboard/index.php`, `includes/sidebar.php`, `includes/topbar.php` | Banyak HTML/PHP satu baris panjang. | Format ulang agar mudah dibaca dan direview. |
| P2 | Semua modul form | Belum ada validasi panjang maksimum yang konsisten sesuai ukuran kolom DB. | Tambahkan validasi `maxlength` server-side dan atribut HTML. |
| P2 | Login/Register | Belum ada rate limiting dan lockout sementara. | Tambahkan throttle per IP/email untuk deployment publik. |
| P2 | Transaksi/Laporan | Belum ada pagination. | Tambahkan pagination dan limit query. |
| P3 | Aplikasi umum | Belum ada automated test. | Tambahkan smoke test PHP, test helper function, dan test alur CRUD dengan database test. |

## 6. Rekomendasi Fitur Baru

| Prioritas | Fitur | Alasan |
| --- | --- | --- |
| P1 | Budget per kategori/bulan | User bisa mengontrol batas pengeluaran dan melihat progres. |
| P1 | Pagination + sorting transaksi | Penting saat data transaksi banyak. |
| P1 | Ringkasan dashboard bulan berjalan | Lebih sesuai untuk monitoring keuangan bulanan. |
| P1 | Filter laporan rentang tanggal custom | Bulan/tahun saja kurang fleksibel untuk periode mingguan/kuartal. |
| P2 | Recurring transaction | Memudahkan pencatatan gaji, langganan, cicilan, dan tagihan rutin. |
| P2 | Import CSV/Excel mutasi | Mempercepat input data massal dari bank/e-wallet. |
| P2 | Export PDF asli | Memudahkan arsip dan sharing laporan. |
| P2 | Notifikasi budget melewati batas | Meningkatkan manfaat aplikasi sebagai pengingat finansial. |
| P2 | Multi-account / wallet | Memisahkan kas, bank, e-wallet, kartu kredit, dan investasi. |
| P3 | Goal tabungan | Membantu user melacak target dana darurat/liburan/dll. |
| P3 | Audit log | Berguna untuk keamanan dan troubleshooting perubahan data. |
| P3 | Dark mode persisten | Meningkatkan UX dan personalisasi. |

## 7. Rekomendasi Refactor Teknis

1. Pisahkan logic query/form validation dari template HTML menjadi function/service kecil.
2. Gunakan Composer autoload untuk config, helper, dan service.
3. Buat helper response redirect/flash agar tidak menulis session flash secara manual di banyak file.
4. Standarkan formatter PHP, misalnya PHP-CS-Fixer atau Pint, untuk mengurangi style campuran.
5. Tambahkan config `APP_ENV=production` agar error display dimatikan pada production.
6. Tambahkan centralized logger untuk error database, OAuth, dan export.
7. Tambahkan test minimal:
   - `php -l` semua file PHP.
   - Test helper CSRF/flash.
   - Test validasi form transaksi/kategori.
   - Smoke test route utama dengan built-in server dan database test.

## 8. Tempat Task / Backlog

Gunakan bagian ini sebagai task board. Kolom prioritas: **P0 = blocker**, **P1 = penting**, **P2 = menengah**, **P3 = nice-to-have**.

| Status | Prioritas | Task | Owner | Catatan |
| --- | --- | --- | --- | --- |
| Done | P0 | Perbaiki login manual yang rusak akibat marker diff dan logic hilang. | AI | Selesai pada patch ini. |
| Done | P0 | Perbaiki register manual yang rusak akibat marker diff, validasi terpotong, dan form ganda. | AI | Selesai pada patch ini. |
| Done | P0 | Perbaiki parse error halaman reports. | AI | Selesai pada patch ini. |
| Done | P1 | Perbaiki anchor `Export PDF` yang tidak ditutup. | AI | Selesai pada patch ini. |
| Todo | P1 | Sinkronkan schema utama dengan migration Google OAuth. |  |  |
| Todo | P1 | Ubah dashboard agar angka monthly benar-benar bulan berjalan atau ubah label UI. |  |  |
| Todo | P1 | Tambahkan pagination transaksi dan laporan. |  |  |
| Todo | P1 | Sembunyikan detail error database pada production. |  |  |
| Todo | P2 | Tambahkan budget per kategori/bulan. |  |  |
| Todo | P2 | Tambahkan recurring transaction. |  |  |
| Todo | P2 | Tambahkan import CSV/Excel. |  |  |
| Todo | P2 | Rapikan format file yang masih satu baris panjang. |  |  |
| Todo | P2 | Tambahkan rate limiting login/register. |  |  |
| Todo | P3 | Pindahkan mockup HTML statis ke folder dokumentasi atau hapus. |  |  |
| Todo | P3 | Tambahkan audit log perubahan data. |  |  |

## 9. Checklist Verifikasi yang Disarankan

- Jalankan `php -l` untuk semua file PHP.
- Pastikan tidak ada marker merge/diff (`@@`, `<<<<<<<`, `=======`, `>>>>>>>`) di file aplikasi.
- Uji manual login, register, logout, create/edit/delete transaksi, create/edit/delete kategori, reports, dan export.
- Uji setup fresh database memakai `fintrack_pro.sql` lalu jalankan migration Google OAuth jika fitur Google login digunakan.
