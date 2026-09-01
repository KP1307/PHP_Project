<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_admin_login();

$passengers = $pdo->query(
    "SELECT p.passenger_id, p.full_name, p.email, p.phone, b.booking_ref,
            d.deck_name, c.cabin_number, b.status,
            (SELECT COUNT(*) FROM luggage l WHERE l.booking_id = b.booking_id) AS luggage_count
     FROM passengers p
     JOIN bookings b ON b.passenger_id = p.passenger_id
     JOIN cabins c ON b.cabin_id = c.cabin_id
     JOIN ship_decks d ON c.deck_id = d.deck_id
     ORDER BY p.created_at DESC"
)->fetchAll();
?>
<?php admin_shell_start('Passengers', 'passengers.php'); ?>

<div class="card-head">
    <h3><?= icon('users') ?> All Passengers <span class="badge badge-neutral"><?= count($passengers) ?></span></h3>
    <a href="../modules/register_passenger.php" class="btn btn-primary btn-sm"><?= icon('plus') ?> Register New Passenger</a>
</div>

<div class="table-wrap">
    <table class="dt">
        <tr>
            <th>Name</th><th>Email</th><th>Phone</th><th>Booking Ref</th>
            <th>Cabin</th><th>Status</th><th>Luggage</th>
        </tr>
        <?php if (!$passengers): ?>
            <tr><td colspan="7" class="table-empty">No passengers registered yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($passengers as $p): ?>
        <tr>
            <td><strong><?= h($p['full_name']) ?></strong></td>
            <td><?= h($p['email'] ?? '') ?></td>
            <td><?= h($p['phone'] ?? '') ?></td>
            <td><code><?= h($p['booking_ref']) ?></code></td>
            <td><?= h($p['deck_name']) ?> &middot; <?= h($p['cabin_number']) ?></td>
            <td><?= status_badge($p['status']) ?></td>
            <td><span class="badge badge-neutral"><?= icon('suitcase') ?> <?= (int)$p['luggage_count'] ?></span></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php shell_end(); ?>
