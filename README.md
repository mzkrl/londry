# Londry (Sistem Laundry)

A native PHP POS dan sistem operasional laundry sesuai spesifikasi di `tech_spec.md`. Aplikasi ini tidak menggunakan dependensi eksternal (tanpa Composer/NPM) dan siap dijalankan di hosting standar PHP.

## Fitur Utama
- Autentikasi berbasis peran (Kasir, Admin, Owner) dengan sesi PHP.
- Kasir: lihat produk, proses transaksi tunai, cetak bukti transaksi, pencatatan log otomatis.
- Admin: CRUD produk, kelola pengguna (aktif/nonaktif, ubah peran & password), pencatatan log otomatis.
- Owner: lihat katalog produk, laporan transaksi dengan filter tanggal, tinjau log aktivitas (read-only).
- Pencatatan aktivitas pada tabel `log` untuk semua tindakan Kasir/Admin.

## Konfigurasi
1. Salin `.env.example` menjadi `.env` lalu isi kredensial database:
   ```
   DB_DRIVER=mysql  # atau pgsql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=londry
   DB_USER=root
   DB_PASS=password
   ```
2. Buat database dan jalankan `schema.sql` pada MySQL 8.4+ atau PostgreSQL 17+.
3. Buat akun admin awal langsung di tabel `users` (password harus di-hash dengan `password_hash`), lalu login via `/login.php` untuk membuat pengguna lain.

## Struktur Direktori
- `includes/` utilitas bersama (koneksi PDO, autentikasi, CSRF, template, logger).
- `kasir/` transaksi dan cetak struk.
- `admin/` kelola produk & pengguna.
- `owner/` laporan transaksi & log aktivitas.

## Keamanan
- Semua input difilter dengan prepared statement PDO.
- CSRF token pada seluruh form tulis.
- Password disimpan dengan `password_hash()` / `password_verify()`.
- Peran Owner hanya memiliki akses baca.

## Menjalankan
Jalankan server PHP built-in:
```bash
php -S localhost:8000 -t .
```
Lalu buka `http://localhost:8000/login.php`.
