<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
// Either crew or admin session may call this
if (!isset($_SESSION['crew_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

header('Content-Type: application/json');

$pending = $pdo->query(
    "SELECT l.tag_code, l.description, l.current_stage, p.full_name, d.deck_name, c.cabin_number
     FROM luggage l
     JOIN bookings b ON l.booking_id = b.booking_id
     JOIN passengers p ON b.passenger_id = p.passenger_id
     JOIN cabins c ON b.cabin_id = c.cabin_id
     JOIN ship_decks d ON c.deck_id = d.deck_id
     WHERE l.status = 'in_transit'
     ORDER BY l.created_at DESC"
)->fetchAll();

echo json_encode($pending);
