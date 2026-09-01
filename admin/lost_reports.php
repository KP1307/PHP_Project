<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'])) {
    $pdo->prepare("UPDATE lost_luggage_reports SET status = ? WHERE report_id = ?")
        ->execute([$_POST['status'], (int)$_POST['report_id']]);
}

$reports = $pdo->query(
    "SELECT r.report_id, r.reported_at, r.status, r.notes, l.tag_code, l.description,
            p.full_name, cr.full_name AS reported_by
     FROM lost_luggage_reports r
     JOIN luggage l ON r.luggage_id = l.luggage_id
     JOIN bookings b ON l.booking_id = b.booking_id
     JOIN passengers p ON b.passenger_id = p.passenger_id
     LEFT JOIN crew cr ON r.reported_by_id = cr.crew_id
     ORDER BY r.reported_at DESC"
)->fetchAll();
?>
<?php admin_shell_start('Lost Luggage Reports', 'lost_reports.php'); ?>

<div class="card-head"><h3><?= icon('alert') ?> Lost Luggage Reports <span class="badge badge-neutral"><?= count($reports) ?></span></h3></div>
<div class="table-wrap">
    <table class="dt">
        <tr><th>Tag</th><th>Item</th><th>Passenger</th><th>Reported By</th><th>Reported At</th><th>Status</th><th>Notes</th><th>Update</th></tr>
        <?php if (!$reports): ?><tr><td colspan="8" class="table-empty">No lost luggage reports &mdash; nice and clean!</td></tr><?php endif; ?>
        <?php foreach ($reports as $r): ?>
        <tr>
            <td><code><?= h($r['tag_code']) ?></code></td>
            <td><?= h($r['description']) ?></td>
            <td><?= h($r['full_name']) ?></td>
            <td><?= h($r['reported_by'] ?? 'Crew') ?></td>
            <td><?= h($r['reported_at']) ?></td>
            <td><?= status_badge($r['status']) ?></td>
            <td><?= h($r['notes'] ?? '') ?></td>
            <td>
                <form method="post" class="inline">
                    <input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                    <select name="status">
                        <option value="open" <?= $r['status']==='open'?'selected':'' ?>>Open</option>
                        <option value="investigating" <?= $r['status']==='investigating'?'selected':'' ?>>Investigating</option>
                        <option value="resolved" <?= $r['status']==='resolved'?'selected':'' ?>>Resolved</option>
                    </select>
                    <button type="submit" class="btn btn-outline btn-sm">Update</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php shell_end(); ?>
