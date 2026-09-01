<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/routing_engine.php';
require_once __DIR__ . '/../includes/layout.php';
require_crew_login();

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tag_code = trim($_POST['tag_code']);
    if ($tag_code !== '') {
        $result = process_scan($pdo, $tag_code, current_crew_id());
    }
}
?>
<?php crew_shell_start('Scan Luggage', 'scan.php'); ?>

<div class="card" style="max-width:560px;">
    <h3><?= icon('scan') ?> Scan or Enter Tag Code</h3>
    <p class="text-muted">Point a USB barcode scanner at a tag, or type/paste the tag code below and press Enter.
    A real scanner acts like a keyboard: it types the code and hits Enter automatically,
    so this exact same input field works with real hardware &mdash; no extra code needed.</p>

    <button type="button" id="cameraScanBtn" class="btn btn-outline btn-block"><?= icon('scan') ?> Scan with Camera</button>
    <div id="cameraReader" class="camera-reader"></div>

    <div class="scan-divider">or type it manually</div>

    <form method="post" class="flex" id="scanForm">
        <input type="text" name="tag_code" id="tagCodeInput" autofocus placeholder="Scan or type tag code, e.g. LUG-AB12CD34" style="flex:1;">
        <button type="submit" class="btn btn-accent"><?= icon('scan') ?> Submit Scan</button>
    </form>

    <?php if ($result): ?>
        <div style="margin-top:16px;">
            <?php flash($result['message'], $result['success'] ? 'success' : 'error'); ?>
        </div>
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
        document.getElementById('scanForm').submit();
    }
});
</script>

<?php shell_end(); ?>
