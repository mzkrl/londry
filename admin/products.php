<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/template.php';
require_once __DIR__ . '/../includes/util.php';

require_login();
require_role(['admin']);

$pdo = get_pdo();
$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        flash('error', 'Token CSRF tidak valid.');
        redirect('/admin/products.php');
    }

    $action = $_POST['action'] ?? '';
    $namaProduk = trim($_POST['nama_produk'] ?? '');
    $hargaProduk = (float)($_POST['harga_produk'] ?? 0);

    if ($action === 'create') {
        if ($namaProduk === '' || $hargaProduk <= 0) {
            flash('error', 'Nama produk dan harga wajib diisi dengan benar.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO products (nama_produk, harga_produk) VALUES (:nama_produk, :harga_produk)');
            $stmt->execute([':nama_produk' => $namaProduk, ':harga_produk' => $hargaProduk]);
            log_activity($pdo, 'Admin menambah produk ' . $namaProduk);
            flash('success', 'Produk berhasil ditambahkan.');
        }
        redirect('/admin/products.php');
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || $namaProduk === '' || $hargaProduk <= 0) {
            flash('error', 'Data tidak lengkap untuk pembaruan.');
            redirect('/admin/products.php');
        }
        $stmt = $pdo->prepare('UPDATE products SET nama_produk = :nama_produk, harga_produk = :harga_produk WHERE id = :id');
        $stmt->execute([
            ':nama_produk' => $namaProduk,
            ':harga_produk' => $hargaProduk,
            ':id' => $id,
        ]);
        log_activity($pdo, 'Admin mengupdate produk ' . $namaProduk);
        flash('success', 'Produk berhasil diperbarui.');
        redirect('/admin/products.php');
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Produk tidak valid.');
            redirect('/admin/products.php');
        }
        try {
            $nameStmt = $pdo->prepare('SELECT nama_produk FROM products WHERE id = :id');
            $nameStmt->execute([':id' => $id]);
            $deletedName = $nameStmt->fetchColumn() ?: 'ID ' . $id;
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
            $stmt->execute([':id' => $id]);
            log_activity($pdo, 'Admin menghapus produk ' . $deletedName);
            flash('success', 'Produk berhasil dihapus.');
        } catch (PDOException $e) {
            flash('error', 'Produk tidak dapat dihapus karena memiliki transaksi terkait.');
        }
        redirect('/admin/products.php');
    }
}

$products = $pdo->query('SELECT id, nama_produk, harga_produk FROM products ORDER BY id DESC')->fetchAll();

render_header('Admin - Produk', 'products');
?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= sanitize($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= sanitize($success) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-header">Tambah Produk</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label" for="nama_produk">Nama Produk</label>
                        <input type="text" class="form-control" id="nama_produk" name="nama_produk" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="harga_produk">Harga (Rp)</label>
                        <input type="number" step="100" class="form-control" id="harga_produk" name="harga_produk" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card mb-4">
            <div class="card-header">Daftar Produk</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Harga (Rp)</th>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= sanitize($product['nama_produk']) ?></td>
                                <td><?= number_format((float)$product['harga_produk'], 0, ',', '.') ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?= $product['id'] ?>">Ubah</button>
                                        <form method="post" onsubmit="return confirm('Hapus produk ini?');">
                                            <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="editModal<?= $product['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Ubah Produk</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label" for="nama_produk_<?= $product['id'] ?>">Nama Produk</label>
                                                    <input type="text" class="form-control" id="nama_produk_<?= $product['id'] ?>" name="nama_produk" value="<?= sanitize($product['nama_produk']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label" for="harga_produk_<?= $product['id'] ?>">Harga (Rp)</label>
                                                    <input type="number" step="100" class="form-control" id="harga_produk_<?= $product['id'] ?>" name="harga_produk" value="<?= $product['harga_produk'] ?>" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
render_footer();
