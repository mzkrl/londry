<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/template.php';
require_once __DIR__ . '/../includes/util.php';

require_login();
require_role(['owner']);

$pdo = get_pdo();

$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

$conditions = [];
$params = [];
if ($start !== '') {
    $conditions[] = 't.created_at >= :start';
    $params[':start'] = $start . ' 00:00:00';
}
if ($end !== '') {
    $conditions[] = 't.created_at <= :end';
    $params[':end'] = $end . ' 23:59:59';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$stmt = $pdo->prepare("SELECT t.id, t.nomor_unik, t.nama_pelanggan, t.uang_bayar, t.uang_kembali, t.created_at, p.nama_produk, p.harga_produk 
    FROM transactions t 
    JOIN products p ON p.id = t.id_produk 
    {$where}
    ORDER BY t.created_at DESC");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$total = array_reduce($transactions, fn($carry, $item) => $carry + (float)$item['harga_produk'], 0);

render_header('Owner - Laporan', 'reports');
?>
<div class="card mb-3">
    <div class="card-header">Filter Tanggal</div>
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <label for="start" class="form-label">Dari</label>
                <input type="date" class="form-control" id="start" name="start" value="<?= sanitize($start) ?>">
            </div>
            <div class="col-md-4">
                <label for="end" class="form-label">Sampai</label>
                <input type="date" class="form-control" id="end" name="end" value="<?= sanitize($end) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Terapkan</button>
                <a href="/owner/reports.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Laporan Transaksi</span>
        <span>Total Pendapatan: <strong>Rp <?= number_format($total, 0, ',', '.') ?></strong></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nomor Pesanan</th>
                    <th>Nama Pelanggan</th>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Uang Bayar</th>
                    <th>Uang Kembali</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions as $txn): ?>
                    <tr>
                        <td><?= sanitize($txn['created_at']) ?></td>
                        <td><?= sanitize($txn['nomor_unik']) ?></td>
                        <td><?= sanitize($txn['nama_pelanggan']) ?></td>
                        <td><?= sanitize($txn['nama_produk']) ?></td>
                        <td>Rp <?= number_format((float)$txn['harga_produk'], 0, ',', '.') ?></td>
                        <td>Rp <?= number_format((float)$txn['uang_bayar'], 0, ',', '.') ?></td>
                        <td>Rp <?= number_format((float)$txn['uang_kembali'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
render_footer();
