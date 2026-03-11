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
<div class="card">
    <div class="card-header">Log Aktivitas</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Peran</th>
                    <th>Aktivitas</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= sanitize($log['created_at']) ?></td>
                        <td><?= sanitize($log['username']) ?></td>
                        <td><?= sanitize($log['role']) ?></td>
                        <td><?= sanitize($log['activity']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
render_footer();
