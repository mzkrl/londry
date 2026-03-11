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
<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold h4 mb-0 text-dark">Panel Transaksi</h2>
        <p class="text-muted">Kelola pesanan pelanggan dengan mudah</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
        <?= sanitize($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show" role="alert">
        <?= sanitize($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold">Daftar Produk</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3">Nama Produk</th>
                            <th class="px-4 py-3 text-end">Harga (Rp)</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="px-4 py-3 fw-medium"><?= sanitize($product['nama_produk']) ?></td>
                                <td class="px-4 py-3 text-end fw-bold text-primary">Rp <?= number_format((float)$product['harga_produk'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card border-0 shadow-sm bg-white">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="card-title mb-0 fw-bold">Proses Transaksi</h5>
            </div>
            <div class="card-body p-4">
                <form method="post" id="transactionForm">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted" for="nama_pelanggan">Nama Pelanggan</label>
                        <input type="text" class="form-control form-control-lg bg-light border-0 shadow-none" id="nama_pelanggan" name="nama_pelanggan" placeholder="Nama lengkap pelanggan" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted" for="id_produk">Pilih Produk</label>
                        <select class="form-select form-select-lg bg-light border-0 shadow-none" id="id_produk" name="id_produk" required>
                            <option value="">Pilih layanan...</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>" data-price="<?= $product['harga_produk'] ?>">
                                    <?= sanitize($product['nama_produk']) ?> - Rp <?= number_format((float)$product['harga_produk'], 0, ',', '.') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted" for="uang_bayar">Uang Bayar (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 fw-bold">Rp</span>
                            <input type="number" step="100" class="form-control form-control-lg bg-light border-0 shadow-none" id="uang_bayar" name="uang_bayar" placeholder="0" required>
                        </div>
                    </div>

                    <hr class="my-4 opacity-10">

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Harga</span>
                        <span class="fw-bold" id="display-total">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="text-muted">Kembalian</span>
                        <span class="fw-bold text-success" id="display-change">Rp 0</span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 py-3 shadow-sm fw-bold">
                        Selesaikan Transaksi & Cetak
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const productSelect = document.getElementById('id_produk');
    const cashInput = document.getElementById('uang_bayar');
    const displayTotal = document.getElementById('display-total');
    const displayChange = document.getElementById('display-change');

    function updateCalculations() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const price = selectedOption && selectedOption.dataset.price ? parseFloat(selectedOption.dataset.price) : 0;
        const cash = cashInput.value ? parseFloat(cashInput.value) : 0;
        const change = cash - price;

        displayTotal.innerText = 'Rp ' + price.toLocaleString('id-ID');
        displayChange.innerText = 'Rp ' + (change >= 0 ? change.toLocaleString('id-ID') : '0');
        
        if (cash > 0 && change < 0) {
            displayChange.classList.add('text-danger');
            displayChange.classList.remove('text-success');
        } else {
            displayChange.classList.remove('text-danger');
            displayChange.classList.add('text-success');
        }
    }

    productSelect.addEventListener('change', updateCalculations);
    cashInput.addEventListener('input', updateCalculations);
</script>
<?php
render_footer();
