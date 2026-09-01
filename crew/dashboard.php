<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_crew_login();

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
?>
<?php crew_shell_start('Crew Dashboard', 'dashboard.php', 'Welcome back, ' . current_crew_name()); ?>

<div class="flex-between" style="margin-bottom:18px;">
    <p class="text-muted mb-0"><span class="live-dot">Live · auto-refreshing every 5s</span></p>
    <div class="flex">
        <a href="scan.php" class="btn btn-accent btn-sm"><?= icon('scan') ?> Scan Luggage</a>
        <a href="report_lost.php" class="btn btn-outline btn-sm"><?= icon('alert') ?> Report Lost</a>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3><?= icon('truck') ?> Luggage Awaiting Delivery <span class="badge badge-info" id="pending-count"><?= count($pending) ?></span></h3>
    </div>
    <div class="table-wrap">
        <table class="dt" id="pending-table">
            <tr>
                <th>Tag Code</th><th>Item</th><th>Passenger</th><th>Destination</th><th>Current Stage</th>
            </tr>
            <?php if (!$pending): ?><tr><td colspan="5" class="table-empty">Nothing pending &mdash; all caught up!</td></tr><?php endif; ?>
            <?php foreach ($pending as $row): ?>
            <tr>
                <td><code><?= h($row['tag_code']) ?></code></td>
                <td><?= h($row['description']) ?></td>
                <td><?= h($row['full_name']) ?></td>
                <td><?= h($row['deck_name']) ?> - <?= h($row['cabin_number']) ?></td>
                <td><span class="badge badge-warning"><?= h($row['current_stage']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<script>
async function refreshPending() {
    try {
        const res = await fetch('../api/pending_luggage.php');
        const rows = await res.json();
        const table = document.getElementById('pending-table');
        table.innerHTML = '<tr><th>Tag Code</th><th>Item</th><th>Passenger</th><th>Destination</th><th>Current Stage</th></tr>';
        if (rows.length === 0) {
            table.innerHTML += '<tr><td colspan="5" class="table-empty">Nothing pending — all caught up!</td></tr>';
        }
        rows.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td><code>${r.tag_code}</code></td><td>${r.description}</td><td>${r.full_name}</td><td>${r.deck_name} - ${r.cabin_number}</td><td><span class="badge badge-warning">${r.current_stage}</span></td>`;
            table.appendChild(tr);
        });
        document.getElementById('pending-count').textContent = rows.length;
    } catch (e) {
        console.error('Live refresh failed', e);
    }
}
setInterval(refreshPending, 5000);
</script>

<?php shell_end(); ?>
