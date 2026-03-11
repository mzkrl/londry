-- Database schema for Londry — MySQL version (default)
-- For PostgreSQL, replace INT AUTO_INCREMENT with SERIAL and remove ENGINE=InnoDB.

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(10) NOT NULL CHECK (role IN ('kasir', 'admin', 'owner')),
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(150) NOT NULL,
    harga_produk DECIMAL(12,2) NOT NULL CHECK (harga_produk > 0),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produk INT NOT NULL,
    nama_pelanggan VARCHAR(150) NOT NULL,
    nomor_unik VARCHAR(50) NOT NULL UNIQUE,
    uang_bayar DECIMAL(12,2) NOT NULL CHECK (uang_bayar >= 0),
    uang_kembali DECIMAL(12,2) NOT NULL CHECK (uang_kembali >= 0),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_products FOREIGN KEY (id_produk) REFERENCES products (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    activity TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_users FOREIGN KEY (id_user) REFERENCES users (id)
) ENGINE=InnoDB;


# alternatif 

-- CREATE TABLE IF NOT EXISTS transactions (
--     id SERIAL PRIMARY KEY,
--     id_produk BIGINT UNSIGNED NOT NULL,
--     nama_pelanggan VARCHAR(150) NOT NULL,
--     nomor_unik VARCHAR(50) NOT NULL UNIQUE,
--     uang_bayar NUMERIC(12,2) NOT NULL CHECK (uang_bayar >= 0),
--     uang_kembali NUMERIC(12,2) NOT NULL CHECK (uang_kembali >= 0),
--     created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     CONSTRAINT fk_transactions_products 
--         FOREIGN KEY (id_produk) REFERENCES products (id)
-- );

-- CREATE TABLE IF NOT EXISTS log (
--     id SERIAL PRIMARY KEY,
--     id_user BIGINT UNSIGNED NOT NULL,
--     activity TEXT NOT NULL,
--     created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     CONSTRAINT fk_log_users 
--         FOREIGN KEY (id_user) REFERENCES users (id)
-- );