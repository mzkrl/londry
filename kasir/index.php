<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/template.php';
require_once __DIR__ . '/../includes/util.php';

require_login();
require_role(['kasir']);

$pdo = get_pdo();

// Fetch products
$products = $pdo->query('SELECT id, nama_produk, harga_produk FROM products ORDER BY nama_produk')->fetchAll();

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        flash('error', 'Token CSRF tidak valid.');
        redirect('/kasir/index.php');
    }

    $namaPelanggan = trim($_POST['nama_pelanggan'] ?? '');
    $idProduk = (int)($_POST['id_produk'] ?? 0);
    $uangBayar = (float)($_POST['uang_bayar'] ?? 0);

    if ($namaPelanggan === '' || $idProduk === 0 || $uangBayar <= 0) {
        flash('error', 'Semua field wajib diisi dengan benar.');
        redirect('/kasir/index.php');
    }

    $stmt = $pdo->prepare('SELECT id, harga_produk, nama_produk FROM products WHERE id = :id');
    $stmt->execute([':id' => $idProduk]);
    $product = $stmt->fetch();

    if (!$product) {
        flash('error', 'Produk tidak ditemukan.');
        redirect('/kasir/index.php');
    }

    $hargaProduk = (float)$product['harga_produk'];
    if ($uangBayar < $hargaProduk) {
        flash('error', 'Uang bayar kurang dari harga produk.');
        redirect('/kasir/index.php');
    }

    $uangKembali = $uangBayar - $hargaProduk;

    // Generate unique order number with collision retry per F-003-RQ-005
    $maxRetries = 5;
    $nomorUnik = '';
    $isUnique = false;
    for ($i = 0; $i < $maxRetries; $i++) {
        $nomorUnik = strtoupper(bin2hex(random_bytes(4)));
        $check = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE nomor_unik = :nomor_unik');
        $check->execute([':nomor_unik' => $nomorUnik]);
        if ((int)$check->fetchColumn() === 0) {
            $isUnique = true;
            break;
        }
    }
    if (!$isUnique) {
        flash('error', 'Gagal membuat nomor pesanan unik. Coba lagi.');
        redirect('/kasir/index.php');
    }

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'pgsql') {
        $insert = $pdo->prepare('INSERT INTO transactions (id_produk, nama_pelanggan, nomor_unik, uang_bayar, uang_kembali) VALUES (:id_produk, :nama_pelanggan, :nomor_unik, :uang_bayar, :uang_kembali) RETURNING id');
        $insert->execute([
            ':id_produk' => $idProduk,
            ':nama_pelanggan' => $namaPelanggan,
            ':nomor_unik' => $nomorUnik,
            ':uang_bayar' => $uangBayar,
            ':uang_kembali' => $uangKembali,
        ]);
        $transactionId = (int)$insert->fetchColumn();
    } else {
        $insert = $pdo->prepare('INSERT INTO transactions (id_produk, nama_pelanggan, nomor_unik, uang_bayar, uang_kembali) VALUES (:id_produk, :nama_pelanggan, :nomor_unik, :uang_bayar, :uang_kembali)');
        $insert->execute([
            ':id_produk' => $idProduk,
            ':nama_pelanggan' => $namaPelanggan,
            ':nomor_unik' => $nomorUnik,
            ':uang_bayar' => $uangBayar,
            ':uang_kembali' => $uangKembali,
        ]);
        $transactionId = (int)$pdo->lastInsertId();
    }
    log_activity($pdo, 'Kasir menambah transaksi ' . $nomorUnik . ' untuk ' . $namaPelanggan);

    redirect('/kasir/receipt.php?id=' . $transactionId . '&print=1');
}

render_header('Kasir - Transaksi', 'kasir');
?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= sanitize($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= sanitize($success) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">Produk</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                        <tr>
                            <th>Nama Produk</th>
                            <th>Harga (Rp)</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= sanitize($product['nama_produk']) ?></td>
                                <td><?= number_format((float)$product['harga_produk'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">Proses Transaksi</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
                    <div class="mb-3">
                        <label class="form-label" for="nama_pelanggan">Nama Pelanggan</label>
                        <input type="text" class="form-control" id="nama_pelanggan" name="nama_pelanggan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="id_produk">Produk</label>
                        <select class="form-select" id="id_produk" name="id_produk" required>
                            <option value="">Pilih produk</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>"><?= sanitize($product['nama_produk']) ?> - Rp <?= number_format((float)$product['harga_produk'], 0, ',', '.') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="uang_bayar">Uang Bayar (Rp)</label>
                        <input type="number" step="100" class="form-control" id="uang_bayar" name="uang_bayar" required>
                    </div>
                    <button type="submit" class="btn btn-success">Simpan & Cetak</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
render_footer();
