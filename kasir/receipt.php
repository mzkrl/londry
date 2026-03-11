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

render_header('Bukti Transaksi', 'kasir');
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="mb-4 d-print-none d-flex justify-content-between">
            <a href="/kasir/index.php" class="btn btn-light border">
                &larr; Kembali ke Kasir
            </a>
            <button class="btn btn-primary shadow-sm" onclick="window.print()">
                Cetak Bukti Transaksi
            </button>
        </div>

        <div class="card border-0 shadow-sm receipt-card">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h4 class="fw-bold mb-0">LONDRY</h4>
                    <p class="text-muted small mb-0">Solusi Laundry Modern & Terpercaya</p>
                    <hr class="my-4 border-2 opacity-10">
                </div>

                <div class="d-flex justify-content-between mb-4">
                    <div>
                        <div class="small text-muted text-uppercase fw-bold">Nomor Pesanan</div>
                        <div class="fw-bold text-primary fs-5">#<?= sanitize($transaction['nomor_unik']) ?></div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted text-uppercase fw-bold">Tanggal</div>
                        <div class="small"><?= date('d M Y, H:i', strtotime($transaction['created_at'])) ?></div>
                    </div>
                </div>

                <div class="bg-light rounded-3 p-3 mb-4">
                    <div class="small text-muted text-uppercase fw-bold mb-1">Pelanggan</div>
                    <div class="fw-bold text-dark"><?= sanitize($transaction['nama_pelanggan']) ?></div>
                </div>

                <table class="table table-borderless mb-4">
                    <thead>
                        <tr class="border-bottom small text-muted text-uppercase fw-bold">
                            <th class="ps-0 py-2">Layanan</th>
                            <th class="pe-0 py-2 text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="ps-0 py-3">
                                <div class="fw-bold text-dark"><?= sanitize($transaction['nama_produk']) ?></div>
                                <div class="small text-muted">1 Unit / Paket</div>
                            </td>
                            <td class="pe-0 py-3 text-end fw-bold">
                                Rp <?= number_format((float)$transaction['harga_produk'], 0, ',', '.') ?>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-top">
                        <tr>
                            <td class="ps-0 pt-4 pb-2 fw-bold text-dark">Total Tagihan</td>
                            <td class="pe-0 pt-4 pb-2 text-end fw-bold fs-5 text-primary">
                                Rp <?= number_format((float)$transaction['harga_produk'], 0, ',', '.') ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-0 py-1 small text-muted">Uang Bayar</td>
                            <td class="pe-0 py-1 text-end small">
                                Rp <?= number_format((float)$transaction['uang_bayar'], 0, ',', '.') ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-0 py-1 small text-muted">Uang Kembali</td>
                            <td class="pe-0 py-1 text-end small fw-bold text-success">
                                Rp <?= number_format((float)$transaction['uang_kembali'], 0, ',', '.') ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <div class="text-center mt-5">
                    <p class="small text-muted mb-0">Terima kasih atas kepercayaan Anda!</p>
                    <p class="small text-muted">Simpan bukti ini sebagai tanda terima sah.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .navbar, .footer, .d-print-none, .main-content {
            padding-top: 0 !important;
        }
        body {
            background-color: white !important;
        }
        .container {
            width: 100% !important;
            max-width: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .card {
            box-shadow: none !important;
            border: none !important;
        }
        .receipt-card {
            width: 100% !important;
        }
    }
</style>
<?php
render_footer();
