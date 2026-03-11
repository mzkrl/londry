# Londry (Sistem Laundry)

Aplikasi web POS dan manajemen operasional laundry menggunakan PHP native sesuai `tech_spec.md`. Tanpa dependensi eksternal (tanpa Composer/NPM), siap dijalankan di hosting standar PHP.

## Fitur Utama
- **Autentikasi** berbasis peran (Kasir, Admin, Owner) dengan sesi PHP, cookie HttpOnly & SameSite.
- **Kasir:** lihat produk, proses transaksi tunai, cetak bukti transaksi (nomor unik anti-collision), pencatatan log otomatis.
- **Admin:** CRUD produk, kelola pengguna (aktif/nonaktif, ubah peran & password), pencatatan log otomatis.
- **Owner:** lihat katalog produk, laporan transaksi dengan filter tanggal (validasi range), tinjau log aktivitas (read-only).
- Pencatatan aktivitas detail pada tabel `log` (termasuk nama produk/user/nomor transaksi).
- UI responsif dengan Bootstrap 5.3.8 via CDN.

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
2. Buat database dan jalankan schema yang sesuai:
   - **MySQL (default):** `schema.sql`
   - **PostgreSQL:** `schema_pgsql.sql`
3. Buat hash password untuk akun admin awal:
   ```bash
   php hash.php
   ```
   Lalu INSERT ke tabel `users`:
   ```sql
   INSERT INTO users (username, password, role) VALUES ('admin', '<hash_dari_hash.php>', 'admin');
   ```

## Struktur Direktori
```
londry/
├── includes/    # Utilitas: koneksi PDO, autentikasi, CSRF, template, logger
├── kasir/       # Transaksi dan cetak struk
├── admin/       # Kelola produk & pengguna
├── owner/       # Laporan transaksi & log aktivitas (read-only)
├── schema.sql   # DDL MySQL
├── schema_pgsql.sql  # DDL PostgreSQL
├── hash.php     # Helper: buat hash password untuk init admin
└── .env.example # Contoh konfigurasi database
```

## Keamanan
- Semua input difilter dengan prepared statement PDO (anti SQL injection).
- CSRF token pada seluruh form tulis.
- Password disimpan dengan `password_hash()` / `password_verify()`.
- Session cookie: HttpOnly, SameSite=Strict.
- Peran Owner hanya memiliki akses baca — tidak ada kontrol tulis.
- Role isolation: akses ke halaman role lain akan redirect ke dashboard sendiri.
- Log aktivitas bersifat immutable (INSERT-only, tidak bisa diubah/dihapus).

## Menjalankan
Jalankan server PHP built-in:
```bash
php -S localhost:8000 -t .
```
Lalu buka `http://localhost:8000/login.php`.
