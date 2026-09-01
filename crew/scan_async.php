<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_crew_login();

$queued = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tag_code = trim($_POST['tag_code']);
    if ($tag_code !== '') {
        $pdo->prepare("INSERT INTO scan_queue (tag_code, crew_id, status) VALUES (?, ?, 'pending')")
            ->execute([$tag_code, current_crew_id()]);
        $queued = $tag_code;
    }
}

$recent = $pdo->query(
    "SELECT * FROM scan_queue ORDER BY created_at DESC LIMIT 15"
)->fetchAll();
?>
<?php crew_shell_start('Scan (Daemon Mode)', 'scan_async.php', 'Async Scan &mdash; Daemon Mode'); ?>

<?php flash('This page does NOT process the scan directly. It drops the scan into the scan_queue table, and the background daemon (bin/routing_daemon.php, run separately with `php bin/routing_daemon.php`) picks it up and processes it. Refresh after a couple of seconds to see status flip from "pending" to "processed".', 'info'); ?>

<div class="card" style="max-width:560px;">
    <h3><?= icon('truck') ?> Queue a Scan</h3>

    <button type="button" id="cameraScanBtn" class="btn btn-outline btn-block"><?= icon('scan') ?> Scan with Camera</button>
    <div id="cameraReader" class="camera-reader"></div>
    <div class="scan-divider">or type it manually</div>

    <form method="post" class="flex" id="scanForm">
        <input type="text" name="tag_code" id="tagCodeInput" autofocus placeholder="Scan or type tag code" style="flex:1;">
        <button type="submit" class="btn btn-accent"><?= icon('scan') ?> Queue Scan</button>
    </form>
    <?php if ($queued): ?>
        <div style="margin-top:16px;"><?php flash("Queued tag '$queued'. Waiting for daemon to pick it up...", 'success'); ?></div>
    <?php endif; ?>
</div>

<div class="card-head"><h3><?= icon('history') ?> Recent Queue Activity</h3></div>
<div class="table-wrap">
    <table class="dt">
        <tr><th>Tag</th><th>Status</th><th>Result</th><th>Queued At</th><th>Processed At</th></tr>
        <?php if (!$recent): ?><tr><td colspan="5" class="table-empty">No queued scans yet.</td></tr><?php endif; ?>
        <?php foreach ($recent as $r): ?>
        <tr>
            <td><code><?= h($r['tag_code']) ?></code></td>
            <td><?= status_badge($r['status']) ?></td>
            <td><?= h($r['result_message'] ?? '') ?></td>
            <td><?= h($r['created_at']) ?></td>
            <td><?= h($r['processed_at'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<p style="margin-top:16px;"><a href="scan.php"><?= icon('scan') ?> Use synchronous scan instead &raquo;</a></p>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script src="../assets/js/scanner.js"></script>
<script>
initCameraScanner({
    buttonId: 'cameraScanBtn',
    readerId: 'cameraReader',
    startLabel: 'Scan with Camera',
    onDecoded: function (text) {
        document.getElementById('tagCodeInput').value = text;
        document.getElementById('scanForm').submit();
    }
});
</script>

<?php shell_end(); ?>
