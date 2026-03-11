<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/template.php';
require_once __DIR__ . '/../includes/util.php';

require_login();
require_role(['owner']);

$pdo = get_pdo();
$products = $pdo->query('SELECT nama_produk, harga_produk FROM products ORDER BY nama_produk')->fetchAll();

render_header('Owner - Produk', 'products');
?>
<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold h4 mb-0 text-dark">Daftar Produk</h2>
        <p class="text-muted">Lihat daftar layanan dan harga laundry yang aktif</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
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
                        <td class="px-4 py-3 fw-bold text-dark"><?= sanitize($product['nama_produk']) ?></td>
                        <td class="px-4 py-3 text-end fw-bold text-primary">Rp <?= number_format((float)$product['harga_produk'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="2" class="text-center py-5 text-muted">Belum ada produk yang tersedia.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
render_footer();
