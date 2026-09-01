<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_admin_login();

$notifs = $pdo->query(
    "SELECT n.*, l.tag_code FROM notifications_log n
     LEFT JOIN luggage l ON n.luggage_id = l.luggage_id
     ORDER BY n.sent_at DESC LIMIT 100"
)->fetchAll();
?>
<?php admin_shell_start('Notifications Log', 'notifications.php'); ?>

<?php flash("Emails send via PHP's mail() when your server has an MTA configured; otherwise they're recorded here as \"simulated\" so nothing is lost. SMS always logs as simulated until a gateway is wired into includes/notifications.php.", 'info'); ?>

<div class="card-head"><h3><?= icon('bell') ?> Notification History <span class="badge badge-neutral"><?= count($notifs) ?></span></h3></div>
<div class="table-wrap">
    <table class="dt">
        <tr><th>Type</th><th>Tag</th><th>Recipient</th><th>Message</th><th>Status</th><th>Sent At</th></tr>
        <?php if (!$notifs): ?><tr><td colspan="6" class="table-empty">No notifications sent yet.</td></tr><?php endif; ?>
        <?php foreach ($notifs as $n): ?>
        <tr>
            <td><span class="badge badge-info"><?= icon($n['type'] === 'email' ? 'mail' : 'bell') ?> <?= h($n['type']) ?></span></td>
            <td><code><?= h($n['tag_code'] ?? '') ?></code></td>
            <td><?= h($n['recipient']) ?></td>
            <td><?= h($n['message']) ?></td>
            <td><?= status_badge($n['status']) ?></td>
            <td><?= h($n['sent_at']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php shell_end(); ?>
