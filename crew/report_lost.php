<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/routing_engine.php';
require_once __DIR__ . '/../includes/layout.php';
require_crew_login();

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tag_code = trim($_POST['tag_code']);
    $notes = trim($_POST['notes']);
    $result = report_lost($pdo, $tag_code, current_crew_id(), $notes);
}
?>
<?php crew_shell_start('Report Lost Luggage', 'report_lost.php'); ?>

<div class="card" style="max-width:520px;">
    <h3><?= icon('alert') ?> Report Lost Luggage</h3>

    <button type="button" id="cameraScanBtn" class="btn btn-outline btn-block"><?= icon('scan') ?> Scan with Camera</button>
    <div id="cameraReader" class="camera-reader"></div>
    <div class="scan-divider">or type it manually</div>

    <form method="post">
        <div class="form-group">
            <label>Tag Code</label>
            <input type="text" name="tag_code" id="tagCodeInput" placeholder="e.g. LUG-AB12CD34" required>
        </div>
        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" placeholder="Where/when it was last seen, any relevant details..."></textarea>
        </div>
        <button type="submit" class="btn btn-danger btn-block"><?= icon('alert') ?> Report as Lost</button>
    </form>

    <?php if ($result): ?>
        <div style="margin-top:16px;"><?php flash($result['message'], $result['success'] ? 'success' : 'error'); ?></div>
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
    }
});
</script>

<?php shell_end(); ?>
