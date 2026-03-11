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
$dateError = '';

// Validate date range per F-010-RQ-002
if ($start !== '' && $end !== '' && $start > $end) {
    $dateError = 'Tanggal "Dari" tidak boleh lebih besar dari tanggal "Sampai".';
    $start = '';
    $end = '';
}

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
<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold h4 mb-0 text-dark">Laporan Pendapatan</h2>
        <p class="text-muted">Pantau performa bisnis Anda secara berkala</p>
    </div>
</div>

<?php if ($dateError): ?>
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
        <?= sanitize($dateError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="card-title mb-0 fw-bold">Filter Rentang Tanggal</h5>
    </div>
    <div class="card-body p-4 pt-0">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <label for="start" class="form-label small fw-bold text-muted">Dari Tanggal</label>
                <input type="date" class="form-control bg-light border-0" id="start" name="start" value="<?= sanitize($start) ?>">
            </div>
            <div class="col-md-4">
                <label for="end" class="form-label small fw-bold text-muted">Sampai Tanggal</label>
                <input type="date" class="form-control bg-light border-0" id="end" name="end" value="<?= sanitize($end) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary px-4 me-2 shadow-sm">Terapkan Filter</button>
                <a href="/owner/reports.php" class="btn btn-light border px-4">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body p-4 text-center">
                <h6 class="text-white-50 fw-bold text-uppercase small mb-2">Total Pendapatan</h6>
                <h3 class="fw-bold mb-0">Rp <?= number_format($total, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white">
            <div class="card-body p-4 text-center">
                <h6 class="text-muted fw-bold text-uppercase small mb-2">Jumlah Transaksi</h6>
                <h3 class="fw-bold mb-0 text-dark"><?= count($transactions) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="card-title mb-0 fw-bold">Detail Transaksi</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                <tr>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">No. Pesanan</th>
                    <th class="px-4 py-3">Pelanggan</th>
                    <th class="px-4 py-3">Produk</th>
                    <th class="px-4 py-3 text-end">Harga</th>
                    <th class="px-4 py-3 text-end">Dibayar</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions as $txn): ?>
                    <tr>
                        <td class="px-4 py-3 small"><?= date('d/m/Y H:i', strtotime($txn['created_at'])) ?></td>
                        <td class="px-4 py-3 fw-bold text-primary">#<?= sanitize($txn['nomor_unik']) ?></td>
                        <td class="px-4 py-3"><?= sanitize($txn['nama_pelanggan']) ?></td>
                        <td class="px-4 py-3"><?= sanitize($txn['nama_produk']) ?></td>
                        <td class="px-4 py-3 text-end fw-bold">Rp <?= number_format((float)$txn['harga_produk'], 0, ',', '.') ?></td>
                        <td class="px-4 py-3 text-end text-muted">Rp <?= number_format((float)$txn['uang_bayar'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            Tidak ada data transaksi ditemukan untuk periode ini.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
render_footer();
