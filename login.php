<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/template.php';
require_once __DIR__ . '/includes/util.php';

start_session();

if (current_user()) {
    redirect_dashboard();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } else {
            $pdo = get_pdo();
            $stmt = $pdo->prepare('SELECT id, username, password, role, active FROM users WHERE username = :username LIMIT 1');
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && $user['active'] && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                ];
                redirect_dashboard();
            } else {
                $error = 'Kredensial tidak valid atau akun nonaktif.';
            }
        }
    }
}

render_header('Masuk');
?>
<div class="row justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="col-md-5 col-lg-4">
        <div class="card border-0 shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <span class="fs-2 fw-bold">L</span>
                    </div>
                    <h3 class="fw-bold">Selamat Datang</h3>
                    <p class="text-muted">Silakan masuk ke akun Anda</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center border-0 small" role="alert">
                        <div><?= sanitize($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrf_token()) ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label small fw-bold">Username</label>
                        <input type="text" class="form-control form-control-lg bg-light border-0" id="username" name="username" placeholder="Masukkan username" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label small fw-bold">Password</label>
                        <input type="password" class="form-control form-control-lg bg-light border-0" id="password" name="password" placeholder="Masukkan password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">Masuk Sekarang</button>
                </form>
            </div>
        </div>
        <div class="text-center mt-4">
            <p class="small text-muted">&copy; <?= date('Y') ?> Londry System. Versi 1.0</p>
        </div>
    </div>
</div>
<?php
render_footer();
