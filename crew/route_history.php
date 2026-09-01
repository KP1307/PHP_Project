<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_crew_login();

$tag_code = trim($_GET['tag_code'] ?? '');
$history = [];
$luggage = null;

if ($tag_code !== '') {
    $stmt = $pdo->prepare("SELECT luggage_id, tag_code, description FROM luggage WHERE tag_code = ?");
    $stmt->execute([$tag_code]);
    $luggage = $stmt->fetch();

    if ($luggage) {
        $stmt = $pdo->prepare(
            "SELECT rl.stage, rl.scanned_at, rl.notes, rl.scanned_by_type, c.full_name AS crew_name
             FROM routing_log rl
             LEFT JOIN crew c ON rl.scanned_by_id = c.crew_id
             WHERE rl.luggage_id = ?
             ORDER BY rl.scanned_at ASC"
        );
        $stmt->execute([$luggage['luggage_id']]);
        $history = $stmt->fetchAll();
    }
}
?>
<?php crew_shell_start('Route History', 'route_history.php'); ?>

<div class="card" style="max-width:560px;">
    <h3><?= icon('history') ?> Look Up Route History</h3>

    <button type="button" id="cameraScanBtn" class="btn btn-outline btn-block"><?= icon('scan') ?> Scan with Camera</button>
    <div id="cameraReader" class="camera-reader"></div>
    <div class="scan-divider">or type it manually</div>

    <form method="get" class="flex" id="searchForm">
        <input type="text" name="tag_code" id="tagCodeInput" value="<?= h($tag_code) ?>" placeholder="Tag code, e.g. LUG-AB12CD34" required style="flex:1;">
        <button type="submit" class="btn btn-primary"><?= icon('search') ?> Search</button>
    </form>
</div>

<?php if ($tag_code !== '' && !$luggage): ?>
    <div class="card" style="max-width:560px;"><?php flash('No luggage found for that tag code.', 'error'); ?></div>
<?php elseif ($luggage): ?>
    <div class="card-head"><h3><?= icon('suitcase') ?> <?= h($luggage['description']) ?> <code><?= h($luggage['tag_code']) ?></code></h3></div>
    <div class="table-wrap">
        <table class="dt">
            <tr><th>Stage</th><th>Timestamp</th><th>Scanned By</th><th>Notes</th></tr>
            <?php if (!$history): ?><tr><td colspan="4" class="table-empty">No history yet.</td></tr><?php endif; ?>
            <?php foreach ($history as $row): ?>
            <tr>
                <td><span class="badge badge-info"><?= h($row['stage']) ?></span></td>
                <td><?= h($row['scanned_at']) ?></td>
                <td><?= $row['scanned_by_type'] === 'crew' ? h($row['crew_name'] ?? 'Crew') : 'System' ?></td>
                <td><?= h($row['notes'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

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
