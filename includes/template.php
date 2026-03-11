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
        <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> - Londry</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            :root {
                --bs-primary: #0d6efd;
                --bs-primary-rgb: 13, 110, 253;
            }
            body {
                background-color: #f8f9fa;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            .navbar {
                box-shadow: 0 2px 4px rgba(0,0,0,.08);
            }
            .main-content {
                flex: 1;
                padding-top: 2rem;
                padding-bottom: 3rem;
            }
            .card {
                border: none;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
                border-radius: 0.75rem;
            }
            .card-header {
                background-color: #fff;
                border-bottom: 1px solid rgba(0,0,0,.05);
                font-weight: 600;
                padding: 1rem 1.25rem;
                border-top-left-radius: 0.75rem !important;
                border-top-right-radius: 0.75rem !important;
            }
            .btn {
                border-radius: 0.5rem;
                padding: 0.5rem 1rem;
                font-weight: 500;
            }
            .table thead th {
                background-color: #f8f9fa;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.05em;
                font-weight: 700;
                border-top: none;
            }
        </style>
    </head>
    <body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/">
                <span class="fw-bold tracking-tight">LONDRY</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <?php if ($user): ?>
                        <?php if ($user['role'] === 'kasir'): ?>
                            <li class="nav-item"><a class="nav-link px-3 <?= $activeNav === 'kasir' ? 'active fw-bold' : '' ?>" href="/kasir/index.php">Kasir</a></li>
                        <?php elseif ($user['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link px-3 <?= $activeNav === 'products' ? 'active fw-bold' : '' ?>" href="/admin/products.php">Produk</a></li>
                            <li class="nav-item"><a class="nav-link px-3 <?= $activeNav === 'users' ? 'active fw-bold' : '' ?>" href="/admin/users.php">Pengguna</a></li>
                        <?php elseif ($user['role'] === 'owner'): ?>
                            <li class="nav-item"><a class="nav-link px-3 <?= $activeNav === 'products' ? 'active fw-bold' : '' ?>" href="/owner/products.php">Produk</a></li>
                            <li class="nav-item"><a class="nav-link px-3 <?= $activeNav === 'reports' ? 'active fw-bold' : '' ?>" href="/owner/reports.php">Laporan</a></li>
                            <li class="nav-item"><a class="nav-link px-3 <?= $activeNav === 'logs' ? 'active fw-bold' : '' ?>" href="/owner/logs.php">Log Aktivitas</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav align-items-lg-center">
                    <?php if ($user): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem; font-weight: bold;">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </div>
                                <span><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="userDropdown">
                                <li><h6 class="dropdown-header small text-muted text-uppercase">Role: <?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/logout.php">Keluar</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="main-content">
    <div class="container">
    <?php
}

function render_footer(): void
{
    ?>
    </div>
    </main>
    <footer class="footer mt-auto py-3 bg-white border-top">
        <div class="container text-center">
            <span class="text-muted small">&copy; <?= date('Y') ?> Londry System. All rights reserved.</span>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
