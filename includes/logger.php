<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function log_activity(PDO $pdo, string $activity): void
{
    $user = current_user();
    if (!$user) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO log (id_user, activity) VALUES (:id_user, :activity)');
    $stmt->execute([
        ':id_user' => $user['id'],
        ':activity' => $activity,
    ]);
}
