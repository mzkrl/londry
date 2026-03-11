<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

start_session();
$user = current_user();

if ($user) {
    redirect_dashboard();
}

header('Location: /login.php');
exit;
