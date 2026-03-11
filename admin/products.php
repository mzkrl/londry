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
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fw-bold h4 mb-0 text-dark">Manajemen Produk</h2>
        <p class="text-muted mb-0">Kelola daftar layanan dan harga laundry</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
            <span class="me-1">+</span> Tambah Produk Baru
        </button>
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

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                <tr>
                    <th class="px-4 py-3">Nama Produk</th>
                    <th class="px-4 py-3 text-end">Harga (Rp)</th>
                    <th class="px-4 py-3 text-center" style="width: 200px;">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td class="px-4 py-3">
                            <div class="fw-bold text-dark"><?= sanitize($product['nama_produk']) ?></div>
                            <small class="text-muted">ID: #<?= $product['id'] ?></small>
                        </td>
                        <td class="px-4 py-3 text-end fw-bold text-primary">
                            Rp <?= number_format((float)$product['harga_produk'], 0, ',', '.') ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="d-flex justify-content-center gap-2">
                                <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editModal<?= $product['id'] ?>">
                                    Ubah
                                </button>
                                <form method="post" onsubmit="return confirm('Hapus produk ini?');" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?= $product['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title fw-bold">Ubah Produk</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post">
                                    <div class="modal-body p-4">
                                        <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted">Nama Produk</label>
                                            <input type="text" class="form-control bg-light border-0" name="nama_produk" value="<?= sanitize($product['nama_produk']) ?>" required>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label small fw-bold text-muted">Harga (Rp)</label>
                                            <input type="number" step="100" class="form-control bg-light border-0" name="harga_produk" value="<?= $product['harga_produk'] ?>" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-light" data-bs-toggle="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="3" class="text-center py-5 text-muted">
                            Belum ada produk yang ditambahkan.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Tambah Produk Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nama Produk</label>
                        <input type="text" class="form-control bg-light border-0" name="nama_produk" placeholder="Contoh: Cuci Kering 1kg" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted">Harga (Rp)</label>
                        <input type="number" step="100" class="form-control bg-light border-0" name="harga_produk" placeholder="0" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
render_footer();
