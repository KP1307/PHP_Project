<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

header('Content-Type: application/json');

echo json_encode([
    'total'     => (int)$pdo->query("SELECT COUNT(*) FROM luggage")->fetchColumn(),
    'delivered' => (int)$pdo->query("SELECT COUNT(*) FROM luggage WHERE status = 'delivered'")->fetchColumn(),
    'pending'   => (int)$pdo->query("SELECT COUNT(*) FROM luggage WHERE status = 'in_transit'")->fetchColumn(),
    'lost'      => (int)$pdo->query("SELECT COUNT(*) FROM luggage WHERE status = 'lost'")->fetchColumn(),
    'timestamp' => date('H:i:s'),
]);
