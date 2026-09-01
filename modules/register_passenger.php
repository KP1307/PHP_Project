<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/layout.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name   = trim($_POST['full_name']);
    $email       = trim($_POST['email']);
    $phone       = trim($_POST['phone']);
    $passport_no = trim($_POST['passport_no']);
    $voyage_id   = (int)$_POST['voyage_id'];
    $cabin_id    = (int)$_POST['cabin_id'];

    if ($full_name === '' || $voyage_id === 0 || $cabin_id === 0) {
        $message = "Full name, voyage and cabin are required.";
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO passengers (full_name, email, phone, passport_no) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$full_name, $email, $phone, $passport_no]);
            $passenger_id = $pdo->lastInsertId();

            $booking_ref = generate_booking_ref();
            $stmt = $pdo->prepare(
                "INSERT INTO bookings (booking_ref, passenger_id, voyage_id, cabin_id, check_in_date, status)
                 VALUES (?, ?, ?, ?, NOW(), 'checked_in')"
            );
            $stmt->execute([$booking_ref, $passenger_id, $voyage_id, $cabin_id]);
            $booking_id = $pdo->lastInsertId();

            $pdo->prepare("UPDATE cabins SET status = 'occupied' WHERE cabin_id = ?")
                ->execute([$cabin_id]);

            $pdo->commit();

            header("Location: boarding_pass.php?booking_id=" . $booking_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
        }
    }
}

$voyages = $pdo->query("SELECT voyage_id, voyage_name FROM voyages WHERE status != 'completed'")->fetchAll();
$cabins  = $pdo->query(
    "SELECT c.cabin_id, c.cabin_number, d.deck_name
     FROM cabins c JOIN ship_decks d ON c.deck_id = d.deck_id
     WHERE c.status = 'available'"
)->fetchAll();
?>
<?php page_head('Check-in Desk', '..'); ?>
<?php public_topnav('..'); ?>

<div class="section" style="padding-top:44px;padding-bottom:70px;">
    <div class="card" style="max-width:620px;margin:0 auto;">
        <div class="card-head">
            <h3><?= icon('user-plus') ?> Register Passenger & Create Booking</h3>
        </div>

        <?php if ($message): ?><?php flash($message, 'error'); ?><?php endif; ?>

        <?php if (!$voyages || !$cabins): ?>
            <?php flash('No active voyages or available cabins found. An admin needs to add ships, decks, cabins and a voyage before passengers can check in.', 'info'); ?>
        <?php endif; ?>

        <form method="post">
            <div class="form-grid">
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
                <div class="form-group"><label>Passport No.</label><input type="text" name="passport_no"></div>
            </div>

            <div class="form-group">
                <label>Voyage</label>
                <select name="voyage_id" required>
                    <option value="">-- select a voyage --</option>
                    <?php foreach ($voyages as $v): ?>
                        <option value="<?= $v['voyage_id'] ?>"><?= h($v['voyage_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Cabin</label>
                <select name="cabin_id" required>
                    <option value="">-- select a cabin --</option>
                    <?php foreach ($cabins as $c): ?>
                        <option value="<?= $c['cabin_id'] ?>"><?= h($c['deck_name']) ?> - Cabin <?= h($c['cabin_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= icon('check') ?> Register & Check In</button>
        </form>
    </div>
</div>

<?php site_footer(); ?>
<?php page_foot(); ?>
