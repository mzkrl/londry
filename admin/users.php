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
        redirect('/admin/users.php');
    }

    $action = $_POST['action'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? '';

    if ($action === 'create') {
        $password = $_POST['password'] ?? '';
        if ($username === '' || $password === '' || !in_array($role, ['kasir', 'admin', 'owner'], true)) {
            flash('error', 'Semua field wajib diisi.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, :role)');
            try {
                $stmt->execute([':username' => $username, ':password' => $hash, ':role' => $role]);
                log_activity($pdo, 'Admin menambah user ' . $username);
                flash('success', 'Pengguna berhasil ditambahkan.');
            } catch (PDOException $e) {
                flash('error', 'Username sudah digunakan.');
            }
        }
        redirect('/admin/users.php');
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $active = isset($_POST['active']) ? (bool)$_POST['active'] : false;
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || $username === '' || !in_array($role, ['kasir', 'admin', 'owner'], true)) {
            flash('error', 'Data tidak lengkap untuk pembaruan.');
            redirect('/admin/users.php');
        }

        $params = [
            ':username' => $username,
            ':role' => $role,
            ':active' => $active ? 1 : 0,
            ':id' => $id,
        ];
        $sql = 'UPDATE users SET username = :username, role = :role, active = :active';
        if ($password !== '') {
            $sql .= ', password = :password';
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = :id';

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $logMsg = 'Admin mengupdate user ' . $username;
            if (!$active) {
                $logMsg = 'Admin menonaktifkan user ' . $username;
            }
            log_activity($pdo, $logMsg);
            flash('success', 'Pengguna berhasil diperbarui.');
        } catch (PDOException $e) {
            flash('error', 'Username sudah digunakan.');
        }
        redirect('/admin/users.php');
    }
}

$users = $pdo->query('SELECT id, username, role, active, created_at FROM users ORDER BY id DESC')->fetchAll();

render_header('Admin - Pengguna', 'users');
?>
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fw-bold h4 mb-0 text-dark">Manajemen Pengguna</h2>
        <p class="text-muted mb-0">Kelola akses akun kasir, admin, dan owner</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addUser">
            <span class="me-1">+</span> Tambah Pengguna Baru
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
                    <th class="px-4 py-3">Username</th>
                    <th class="px-4 py-3">Peran</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center" style="width: 150px;">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center me-2 fw-bold" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= sanitize($user['username']) ?></div>
                                    <small class="text-muted">Dibuat: <?= date('d M Y', strtotime($user['created_at'])) ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-uppercase small fw-bold">
                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'owner' ? 'info' : 'success') ?> bg-opacity-10 text-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'owner' ? 'info' : 'success') ?>">
                                <?= sanitize($user['role']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($user['active']): ?>
                                <span class="badge rounded-pill bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge rounded-pill bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editUser<?= $user['id'] ?>">Ubah</button>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editUser<?= $user['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title fw-bold">Ubah Pengguna</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post">
                                    <div class="modal-body p-4">
                                        <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted">Username</label>
                                            <input type="text" class="form-control bg-light border-0" name="username" value="<?= sanitize($user['username']) ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted">Password Baru</label>
                                            <input type="password" class="form-control bg-light border-0" name="password" placeholder="Kosongkan jika tidak ingin diubah">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted">Peran</label>
                                            <select class="form-select bg-light border-0" name="role" required>
                                                <option value="kasir" <?= $user['role'] === 'kasir' ? 'selected' : '' ?>>Kasir</option>
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                <option value="owner" <?= $user['role'] === 'owner' ? 'selected' : '' ?>>Owner</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" id="active_<?= $user['id'] ?>" name="active" value="1" <?= $user['active'] ? 'checked' : '' ?>>
                                            <label class="form-check-label small fw-bold text-muted" for="active_<?= $user['id'] ?>">Status Akun Aktif</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Username</label>
                        <input type="text" class="form-control bg-light border-0" name="username" placeholder="Masukkan username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Password</label>
                        <input type="password" class="form-control bg-light border-0" name="password" placeholder="Masukkan password" required>
                    </div>
                    
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted">Peran</label>
                        <select class="form-select bg-light border-0" name="role" required>
                            <option value="">Pilih peran...</option>
                            <option value="kasir">Kasir</option>
                            <option value="admin">Admin</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
render_footer();
