<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notifications.php';

/**
 * Processes a single scan event for a piece of luggage.
 *
 * This is the "Routing Engine" described in the project spec. In a real
 * background-daemon deployment, a CLI process would sit in a loop reading
 * from a scanner device / message queue and call this same function for
 * every scan event. In this web version, it's triggered per HTTP request,
 * but the logic is identical - so this function is what you'd lift
 * straight into bin/routing_daemon.php if you add real RFID hardware later.
 *
 * @param PDO $pdo
 * @param string $tag_code   The scanned barcode/RFID value
 * @param int|null $crew_id  Who performed the scan (null = system/kiosk)
 * @return array ['success' => bool, 'message' => string, 'luggage' => array|null]
 */
function process_scan(PDO $pdo, string $tag_code, ?int $crew_id = null): array {
    // 1. Find the luggage by tag
    $stmt = $pdo->prepare(
        "SELECT l.*, p.full_name, d.deck_name, c.cabin_number
         FROM luggage l
         JOIN bookings b ON l.booking_id = b.booking_id
         JOIN passengers p ON b.passenger_id = p.passenger_id
         JOIN cabins c ON b.cabin_id = c.cabin_id
         JOIN ship_decks d ON c.deck_id = d.deck_id
         WHERE l.tag_code = ?"
    );
    $stmt->execute([$tag_code]);
    $luggage = $stmt->fetch();

    if (!$luggage) {
        return ['success' => false, 'message' => "No luggage found for tag '$tag_code'.", 'luggage' => null];
    }

    if ($luggage['status'] === 'lost') {
        return ['success' => false, 'message' => "This bag was previously reported LOST. Please alert crew supervisor.", 'luggage' => $luggage];
    }

    if ($luggage['current_stage'] === 'Delivered') {
        return ['success' => false, 'message' => "This bag is already marked as Delivered.", 'luggage' => $luggage];
    }

    // 2. Determine the next stage in the route
    $next = next_stage($luggage['current_stage']);

    if ($next === null) {
        return ['success' => false, 'message' => "Bag is already at the final stage.", 'luggage' => $luggage];
    }

    // 3. Update luggage record
    $newStatus = ($next === 'Delivered') ? 'delivered' : 'in_transit';
    $pdo->prepare("UPDATE luggage SET current_stage = ?, status = ? WHERE luggage_id = ?")
        ->execute([$next, $newStatus, $luggage['luggage_id']]);

    // 4. Log the movement
    $scannedByType = $crew_id ? 'crew' : 'system';
    $notes = "Advanced from '{$luggage['current_stage']}' to '$next'. Destination: {$luggage['deck_name']} - Cabin {$luggage['cabin_number']}.";
    $pdo->prepare(
        "INSERT INTO routing_log (luggage_id, stage, scanned_by_type, scanned_by_id, notes)
         VALUES (?, ?, ?, ?, ?)"
    )->execute([$luggage['luggage_id'], $next, $scannedByType, $crew_id, $notes]);

    $luggage['current_stage'] = $next;
    $luggage['status'] = $newStatus;

    if ($next === 'Delivered') {
        notify_stage_change($pdo, $luggage);
    }

    return [
        'success' => true,
        'message' => "Bag for {$luggage['full_name']} moved to '$next' (destination: {$luggage['deck_name']} - Cabin {$luggage['cabin_number']}).",
        'luggage' => $luggage,
    ];
}

/**
 * Marks a luggage item as lost and creates a lost-luggage report.
 */
function report_lost(PDO $pdo, string $tag_code, ?int $crew_id, string $notes = ''): array {
    $stmt = $pdo->prepare(
        "SELECT l.*, p.full_name, d.deck_name, c.cabin_number
         FROM luggage l
         JOIN bookings b ON l.booking_id = b.booking_id
         JOIN passengers p ON b.passenger_id = p.passenger_id
         JOIN cabins c ON b.cabin_id = c.cabin_id
         JOIN ship_decks d ON c.deck_id = d.deck_id
         WHERE l.tag_code = ?"
    );
    $stmt->execute([$tag_code]);
    $luggage = $stmt->fetch();

    if (!$luggage) {
        return ['success' => false, 'message' => "No luggage found for tag '$tag_code'."];
    }

    $pdo->prepare("UPDATE luggage SET status = 'lost', current_stage = 'Lost' WHERE luggage_id = ?")
        ->execute([$luggage['luggage_id']]);

    $pdo->prepare(
        "INSERT INTO routing_log (luggage_id, stage, scanned_by_type, scanned_by_id, notes)
         VALUES (?, 'Lost', 'crew', ?, ?)"
    )->execute([$luggage['luggage_id'], $crew_id, "Reported lost. $notes"]);

    $pdo->prepare(
        "INSERT INTO lost_luggage_reports (luggage_id, reported_by_id, notes) VALUES (?, ?, ?)"
    )->execute([$luggage['luggage_id'], $crew_id, $notes]);

    $luggage['current_stage'] = 'Lost';
    notify_stage_change($pdo, $luggage);

    return ['success' => true, 'message' => "Luggage $tag_code marked as LOST and reported."];
}
