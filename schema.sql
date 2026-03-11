-- Database schema for Londry (MySQL 8.4+ / PostgreSQL 17+ compatible)

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(10) NOT NULL CHECK (role IN ('kasir', 'admin', 'owner')),
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    nama_produk VARCHAR(150) NOT NULL,
    harga_produk NUMERIC(12,2) NOT NULL CHECK (harga_produk > 0),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transactions (
    id SERIAL PRIMARY KEY,
    id_produk INTEGER NOT NULL,
    nama_pelanggan VARCHAR(150) NOT NULL,
    nomor_unik VARCHAR(50) NOT NULL UNIQUE,
    uang_bayar NUMERIC(12,2) NOT NULL CHECK (uang_bayar >= 0),
    uang_kembali NUMERIC(12,2) NOT NULL CHECK (uang_kembali >= 0),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_products FOREIGN KEY (id_produk) REFERENCES products (id)
);

CREATE TABLE IF NOT EXISTS log (
    id SERIAL PRIMARY KEY,
    id_user INTEGER NOT NULL,
    activity TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_users FOREIGN KEY (id_user) REFERENCES users (id)
);
