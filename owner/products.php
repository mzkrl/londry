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
<div class="card">
    <div class="card-header">Produk</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>Nama</th>
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
<?php
render_footer();
