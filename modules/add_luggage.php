<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/layout.php';

$booking_id = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description']);
    $weight_kg   = $_POST['weight_kg'] !== '' ? (float)$_POST['weight_kg'] : null;
    $tag_code    = generate_tag_code();

    $stmt = $pdo->prepare(
        "INSERT INTO luggage (tag_code, booking_id, description, weight_kg, current_stage, status)
         VALUES (?, ?, ?, ?, 'Check-in', 'in_transit')"
    );
    $stmt->execute([$tag_code, $booking_id, $description, $weight_kg]);
    $luggage_id = $pdo->lastInsertId();

    $pdo->prepare(
        "INSERT INTO routing_log (luggage_id, stage, scanned_by_type, notes)
         VALUES (?, 'Check-in', 'system', 'Luggage registered at check-in')"
    )->execute([$luggage_id]);

    header("Location: luggage_tag.php?luggage_id=" . $luggage_id);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT p.full_name, c.cabin_number FROM bookings b
     JOIN passengers p ON b.passenger_id = p.passenger_id
     JOIN cabins c ON b.cabin_id = c.cabin_id
     WHERE b.booking_id = ?"
);
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking not found.");
}
?>
<?php page_head('Add Luggage', '..'); ?>
<?php public_topnav('..'); ?>

<div class="section" style="padding-top:44px;padding-bottom:70px;">
    <div class="card" style="max-width:520px;margin:0 auto;">
        <div class="card-head">
            <h3><?= icon('suitcase') ?> Add Luggage</h3>
        </div>
        <p class="text-muted">For <strong><?= h($booking['full_name']) ?></strong> &middot; Cabin <?= h($booking['cabin_number']) ?></p>

        <form method="post">
            <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" placeholder="e.g. Black suitcase" required>
            </div>
            <div class="form-group">
                <label>Weight (kg)</label>
                <input type="number" step="0.1" name="weight_kg" placeholder="e.g. 18.5">
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= icon('scan') ?> Add Luggage & Generate Tag</button>
        </form>
    </div>
</div>

<?php site_footer(); ?>
<?php page_foot(); ?>
