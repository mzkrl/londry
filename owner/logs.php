<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/template.php';
require_once __DIR__ . '/../includes/util.php';

require_login();
require_role(['owner']);

$pdo = get_pdo();
$logs = $pdo->query('SELECT l.activity, l.created_at, u.username, u.role FROM log l JOIN users u ON u.id = l.id_user ORDER BY l.created_at DESC')->fetchAll();

render_header('Owner - Log Aktivitas', 'logs');
?>
<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold h4 mb-0 text-dark">Log Aktivitas</h2>
        <p class="text-muted">Pantau riwayat aktivitas semua pengguna dalam sistem</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                <tr>
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Pengguna</th>
                    <th class="px-4 py-3">Peran</th>
                    <th class="px-4 py-3">Aktivitas</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="px-4 py-3 small text-muted"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                        <td class="px-4 py-3 fw-bold"><?= sanitize($log['username']) ?></td>
                        <td class="px-4 py-3">
                            <span class="badge bg-<?= $log['role'] === 'admin' ? 'danger' : ($log['role'] === 'owner' ? 'info' : 'success') ?> bg-opacity-10 text-<?= $log['role'] === 'admin' ? 'danger' : ($log['role'] === 'owner' ? 'info' : 'success') ?> text-uppercase" style="font-size: 0.7rem;">
                                <?= sanitize($log['role']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3"><?= sanitize($log['activity']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">Belum ada log aktivitas.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
render_footer();
