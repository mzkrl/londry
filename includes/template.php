<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function render_header(string $title, string $activeNav = ''): void
{
    $user = current_user();
    ?>
    <!doctype html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Londry</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if ($user): ?>
                        <?php if ($user['role'] === 'kasir'): ?>
                            <li class="nav-item"><a class="nav-link <?= $activeNav === 'kasir' ? 'active' : '' ?>" href="/kasir/index.php">Kasir</a></li>
                        <?php elseif ($user['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link <?= $activeNav === 'products' ? 'active' : '' ?>" href="/admin/products.php">Produk</a></li>
                            <li class="nav-item"><a class="nav-link <?= $activeNav === 'users' ? 'active' : '' ?>" href="/admin/users.php">Pengguna</a></li>
                        <?php elseif ($user['role'] === 'owner'): ?>
                            <li class="nav-item"><a class="nav-link <?= $activeNav === 'products' ? 'active' : '' ?>" href="/owner/products.php">Produk</a></li>
                            <li class="nav-item"><a class="nav-link <?= $activeNav === 'reports' ? 'active' : '' ?>" href="/owner/reports.php">Laporan</a></li>
                            <li class="nav-item"><a class="nav-link <?= $activeNav === 'logs' ? 'active' : '' ?>" href="/owner/logs.php">Log Aktivitas</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($user): ?>
                        <li class="nav-item"><span class="navbar-text me-3"><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>)</span></li>
                        <li class="nav-item"><a class="nav-link" href="/logout.php">Keluar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
    <?php
}

function render_footer(): void
{
    ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
