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
                log_activity($pdo, 'Admin menambah user baru');
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
            log_activity($pdo, 'Admin mengupdate user');
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
<?php if ($error): ?>
    <div class="alert alert-danger"><?= sanitize($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= sanitize($success) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-header">Tambah Pengguna</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="role">Peran</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Pilih peran</option>
                            <option value="kasir">Kasir</option>
                            <option value="admin">Admin</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card mb-4">
            <div class="card-header">Daftar Pengguna</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead>
                        <tr>
                            <th>Username</th>
                            <th>Peran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= sanitize($user['username']) ?></td>
                                <td><?= sanitize($user['role']) ?></td>
                                <td><?= $user['active'] ? 'Aktif' : 'Nonaktif' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editUser<?= $user['id'] ?>">Ubah</button>
                                </td>
                            </tr>
                            <div class="modal fade" id="editUser<?= $user['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Ubah Pengguna</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label" for="username_<?= $user['id'] ?>">Username</label>
                                                    <input type="text" class="form-control" id="username_<?= $user['id'] ?>" name="username" value="<?= sanitize($user['username']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label" for="password_<?= $user['id'] ?>">Password (kosongkan jika tidak diubah)</label>
                                                    <input type="password" class="form-control" id="password_<?= $user['id'] ?>" name="password">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label" for="role_<?= $user['id'] ?>">Peran</label>
                                                    <select class="form-select" id="role_<?= $user['id'] ?>" name="role" required>
                                                        <option value="kasir" <?= $user['role'] === 'kasir' ? 'selected' : '' ?>>Kasir</option>
                                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                        <option value="owner" <?= $user['role'] === 'owner' ? 'selected' : '' ?>>Owner</option>
                                                    </select>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="active_<?= $user['id'] ?>" name="active" value="1" <?= $user['active'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="active_<?= $user['id'] ?>">Aktif</label>
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
