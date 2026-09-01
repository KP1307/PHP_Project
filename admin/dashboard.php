<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_admin_login();

// ---- Reports ----
$total_luggage   = $pdo->query("SELECT COUNT(*) FROM luggage")->fetchColumn();
$delivered_count = $pdo->query("SELECT COUNT(*) FROM luggage WHERE status = 'delivered'")->fetchColumn();
$pending_count   = $pdo->query("SELECT COUNT(*) FROM luggage WHERE status = 'in_transit'")->fetchColumn();
$lost_count      = $pdo->query("SELECT COUNT(*) FROM luggage WHERE status = 'lost'")->fetchColumn();

$today_count = $pdo->query(
    "SELECT COUNT(*) FROM luggage WHERE DATE(created_at) = CURDATE()"
)->fetchColumn();

// Average processing time (Check-in -> Delivered), in minutes, for delivered bags
$avg_minutes = $pdo->query(
    "SELECT AVG(TIMESTAMPDIFF(MINUTE, first_scan.ts, last_scan.ts)) AS avg_min
     FROM luggage l
     JOIN (SELECT luggage_id, MIN(scanned_at) AS ts FROM routing_log GROUP BY luggage_id) first_scan
        ON first_scan.luggage_id = l.luggage_id
     JOIN (SELECT luggage_id, MAX(scanned_at) AS ts FROM routing_log GROUP BY luggage_id) last_scan
        ON last_scan.luggage_id = l.luggage_id
     WHERE l.status = 'delivered'"
)->fetchColumn();

// ---- Date-range report ----
$range_from = trim($_GET['from'] ?? '');
$range_to   = trim($_GET['to'] ?? '');
$range_result = null;
if ($range_from !== '' && $range_to !== '') {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total,
                SUM(status = 'delivered') AS delivered,
                SUM(status = 'in_transit') AS pending,
                SUM(status = 'lost') AS lost
         FROM luggage
         WHERE DATE(created_at) BETWEEN ? AND ?"
    );
    $stmt->execute([$range_from, $range_to]);
    $range_result = $stmt->fetch();
}

// ---- Search luggage by tag ----
$search_result = null;
$search_tag = trim($_GET['tag_code'] ?? '');
if ($search_tag !== '') {
    $stmt = $pdo->prepare(
        "SELECT l.*, p.full_name, d.deck_name, c.cabin_number
         FROM luggage l
         JOIN bookings b ON l.booking_id = b.booking_id
         JOIN passengers p ON b.passenger_id = p.passenger_id
         JOIN cabins c ON b.cabin_id = c.cabin_id
         JOIN ship_decks d ON c.deck_id = d.deck_id
         WHERE l.tag_code = ?"
    );
    $stmt->execute([$search_tag]);
    $search_result = $stmt->fetch();
}
?>
<?php admin_shell_start('Admin Dashboard', 'dashboard.php', 'Fleet Dashboard'); ?>

<div class="flex-between" style="margin-bottom:18px;">
    <p class="text-muted mb-0">Live overview across all ships &mdash; <span class="live-dot">Live · updated <span id="last-updated">just now</span></span></p>
    <div class="flex">
        <a href="../modules/register_passenger.php" class="btn btn-primary btn-sm"><?= icon('user-plus') ?> Register Passenger</a>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card accent-navy">
        <span class="stat-icon" style="background:var(--navy-800);"><?= icon('suitcase') ?></span>
        <div class="stat-label">Total Luggage</div>
        <div class="stat-value" id="rc-total"><?= (int)$total_luggage ?></div>
    </div>
    <div class="stat-card accent-gold">
        <span class="stat-icon" style="background:var(--gold-500);"><?= icon('calendar') ?></span>
        <div class="stat-label">Added Today</div>
        <div class="stat-value"><?= (int)$today_count ?></div>
    </div>
    <div class="stat-card accent-success">
        <span class="stat-icon" style="background:var(--success-600);"><?= icon('check') ?></span>
        <div class="stat-label">Delivered</div>
        <div class="stat-value" id="rc-delivered"><?= (int)$delivered_count ?></div>
    </div>
    <div class="stat-card accent-teal">
        <span class="stat-icon" style="background:var(--teal-600);"><?= icon('truck') ?></span>
        <div class="stat-label">Pending / In Transit</div>
        <div class="stat-value" id="rc-pending"><?= (int)$pending_count ?></div>
    </div>
    <div class="stat-card accent-danger">
        <span class="stat-icon" style="background:var(--danger-600);"><?= icon('alert') ?></span>
        <div class="stat-label">Lost</div>
        <div class="stat-value" id="rc-lost"><?= (int)$lost_count ?></div>
    </div>
    <div class="stat-card accent-navy">
        <span class="stat-icon" style="background:var(--navy-700);"><?= icon('clock') ?></span>
        <div class="stat-label">Avg. Processing Time</div>
        <div class="stat-value" style="font-size:1.4rem;"><?= $avg_minutes !== null ? round($avg_minutes, 1) . ' min' : 'N/A' ?></div>
    </div>
</div>

<script>
async function refreshCounts() {
    try {
        const res = await fetch('../api/report_counts.php');
        const d = await res.json();
        document.getElementById('rc-total').textContent = d.total;
        document.getElementById('rc-delivered').textContent = d.delivered;
        document.getElementById('rc-pending').textContent = d.pending;
        document.getElementById('rc-lost').textContent = d.lost;
        document.getElementById('last-updated').textContent = d.timestamp;
    } catch (e) {
        console.error('Live refresh failed', e);
    }
}
setInterval(refreshCounts, 5000);
</script>

<div class="card">
    <h3><?= icon('calendar') ?> Date-Range Report</h3>
    <form method="get" class="flex" style="flex-wrap:wrap;">
        <div class="form-group mb-0"><label>From</label><input type="date" name="from" value="<?= h($range_from) ?>" required></div>
        <div class="form-group mb-0"><label>To</label><input type="date" name="to" value="<?= h($range_to) ?>" required></div>
        <button type="submit" class="btn btn-primary" style="margin-top:26px;"><?= icon('bar-chart') ?> Run Report</button>
    </form>
    <?php if ($range_result): ?>
        <table class="kv" style="margin-top:16px;">
            <tr><td>Bags Added</td><td><?= (int)$range_result['total'] ?></td></tr>
            <tr><td>Delivered</td><td><?= (int)$range_result['delivered'] ?></td></tr>
            <tr><td>Pending / In Transit</td><td><?= (int)$range_result['pending'] ?></td></tr>
            <tr><td>Lost</td><td><?= (int)$range_result['lost'] ?></td></tr>
        </table>
    <?php elseif ($range_from !== '' || $range_to !== ''): ?>
        <?php flash('Please select both a start and end date.', 'error'); ?>
    <?php endif; ?>
</div>

<div class="card">
    <h3><?= icon('search') ?> Search Luggage by Tag ID</h3>

    <button type="button" id="cameraScanBtn" class="btn btn-outline btn-block"><?= icon('scan') ?> Scan with Camera</button>
    <div id="cameraReader" class="camera-reader"></div>
    <div class="scan-divider">or type it manually</div>

    <form method="get" class="search-form" id="searchForm">
        <input type="text" name="tag_code" id="tagCodeInput" value="<?= h($search_tag) ?>" placeholder="e.g. LUG-AB12CD34">
        <button type="submit" class="btn btn-primary"><?= icon('search') ?> Search</button>
    </form>

    <?php if ($search_tag !== ''): ?>
        <?php if ($search_result): ?>
            <table class="kv">
                <tr><td>Tag</td><td><?= h($search_result['tag_code']) ?></td></tr>
                <tr><td>Passenger</td><td><?= h($search_result['full_name']) ?></td></tr>
                <tr><td>Destination</td><td><?= h($search_result['deck_name']) ?> - <?= h($search_result['cabin_number']) ?></td></tr>
                <tr><td>Current Stage</td><td><?= h($search_result['current_stage']) ?></td></tr>
                <tr><td>Status</td><td><?= status_badge($search_result['status']) ?></td></tr>
            </table>
            <p style="margin-top:14px;"><a href="../crew/route_history.php?tag_code=<?= urlencode($search_result['tag_code']) ?>"><?= icon('history') ?> View full route history &raquo;</a></p>
        <?php else: ?>
            <?php flash('No luggage found for that tag.', 'error'); ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script src="../assets/js/scanner.js"></script>
<script>
initCameraScanner({
    buttonId: 'cameraScanBtn',
    readerId: 'cameraReader',
    startLabel: 'Scan with Camera',
    onDecoded: function (text) {
        document.getElementById('tagCodeInput').value = text;
        document.getElementById('searchForm').submit();
    }
});
</script>

<?php shell_end(); ?>
