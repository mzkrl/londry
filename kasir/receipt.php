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
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT t.id, t.nomor_unik, t.nama_pelanggan, t.uang_bayar, t.uang_kembali, t.created_at, p.nama_produk, p.harga_produk 
    FROM transactions t 
    JOIN products p ON p.id = t.id_produk 
    WHERE t.id = :id');
$stmt->execute([':id' => $id]);
$transaction = $stmt->fetch();

if (!$transaction) {
    flash('error', 'Transaksi tidak ditemukan.');
    redirect('/kasir/index.php');
}

// Only log once per transaction view (from redirect after creation)
if (!empty($_GET['print'])) {
    log_activity($pdo, 'Kasir mencetak bukti transaksi ' . $transaction['nomor_unik']);
}

render_header('Bukti Transaksi');
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Bukti Transaksi</span>
        <div>
            <a href="/kasir/index.php" class="btn btn-secondary btn-sm me-2">Kembali</a>
            <button class="btn btn-primary btn-sm" onclick="window.print()">Cetak</button>
        </div>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-4">Nomor Pesanan</dt>
            <dd class="col-sm-8"><?= sanitize($transaction['nomor_unik']) ?></dd>

            <dt class="col-sm-4">Nama Pelanggan</dt>
            <dd class="col-sm-8"><?= sanitize($transaction['nama_pelanggan']) ?></dd>

            <dt class="col-sm-4">Produk</dt>
            <dd class="col-sm-8"><?= sanitize($transaction['nama_produk']) ?></dd>

            <dt class="col-sm-4">Harga Produk</dt>
            <dd class="col-sm-8">Rp <?= number_format((float)$transaction['harga_produk'], 0, ',', '.') ?></dd>

            <dt class="col-sm-4">Uang Bayar</dt>
            <dd class="col-sm-8">Rp <?= number_format((float)$transaction['uang_bayar'], 0, ',', '.') ?></dd>

            <dt class="col-sm-4">Uang Kembali</dt>
            <dd class="col-sm-8">Rp <?= number_format((float)$transaction['uang_kembali'], 0, ',', '.') ?></dd>

            <dt class="col-sm-4">Tanggal</dt>
            <dd class="col-sm-8"><?= sanitize($transaction['created_at']) ?></dd>
        </dl>
    </div>
</div>
<?php
render_footer();
