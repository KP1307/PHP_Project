<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/layout.php';

$booking_id = (int)($_GET['booking_id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT b.booking_ref, b.check_in_date, p.full_name, p.passport_no,
            v.voyage_name, v.departure_date, d.deck_name, c.cabin_number, b.booking_id
     FROM bookings b
     JOIN passengers p ON b.passenger_id = p.passenger_id
     JOIN voyages v ON b.voyage_id = v.voyage_id
     JOIN cabins c ON b.cabin_id = c.cabin_id
     JOIN ship_decks d ON c.deck_id = d.deck_id
     WHERE b.booking_id = ?"
);
$stmt->execute([$booking_id]);
$b = $stmt->fetch();

if (!$b) {
    die("Booking not found.");
}
?>
<?php page_head('Boarding Pass', '..'); ?>
<?php public_topnav('..'); ?>

<div class="section" style="padding-top:44px;padding-bottom:70px;">
    <?php flash('Checked in successfully! Here is your boarding pass.', 'success'); ?>

    <div class="pass-card">
        <div class="pass-head">
            <div>
                <div class="pass-brand"><?= icon('ship') ?> Wavepoint Boarding Pass</div>
                <div class="pass-ref">REF <?= h($b['booking_ref']) ?></div>
            </div>
            <span class="badge badge-success"><?= icon('check') ?> Checked In</span>
        </div>
        <div class="pass-body">
            <table class="kv">
                <tr><td>Passenger</td><td><?= h($b['full_name']) ?></td></tr>
                <tr><td>Passport No</td><td><?= h($b['passport_no']) ?></td></tr>
                <tr><td>Voyage</td><td><?= h($b['voyage_name']) ?></td></tr>
                <tr><td>Departure</td><td><?= h($b['departure_date']) ?></td></tr>
                <tr><td>Deck / Cabin</td><td><?= h($b['deck_name']) ?> - <?= h($b['cabin_number']) ?></td></tr>
                <tr><td>Checked in at</td><td><?= h($b['check_in_date']) ?></td></tr>
            </table>
        </div>
    </div>

    <div class="text-center" style="margin-top:24px;">
        <a href="add_luggage.php?booking_id=<?= $b['booking_id'] ?>" class="btn btn-primary"><?= icon('suitcase') ?> Add Luggage for This Passenger</a>
    </div>
</div>

<?php site_footer(); ?>
<?php page_foot(); ?>
