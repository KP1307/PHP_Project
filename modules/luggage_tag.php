<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/layout.php';

$luggage_id = (int)($_GET['luggage_id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT l.tag_code, l.description, p.full_name, d.deck_name, c.cabin_number
     FROM luggage l
     JOIN bookings b ON l.booking_id = b.booking_id
     JOIN passengers p ON b.passenger_id = p.passenger_id
     JOIN cabins c ON b.cabin_id = c.cabin_id
     JOIN ship_decks d ON c.deck_id = d.deck_id
     WHERE l.luggage_id = ?"
);
$stmt->execute([$luggage_id]);
$l = $stmt->fetch();

if (!$l) {
    die("Luggage not found.");
}
$format = ($_GET['format'] ?? 'qr') === 'barcode' ? 'barcode' : 'qr';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Luggage Tag &middot; <?= APP_NAME ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.12.3/JsBarcode.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body>
<?php public_topnav('..'); ?>

<div class="tag-wrap">
    <div class="tag-format-switch no-print">
        <a href="?luggage_id=<?= $luggage_id ?>&format=qr" class="btn <?= $format === 'qr' ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= icon('layers') ?> QR Code</a>
        <a href="?luggage_id=<?= $luggage_id ?>&format=barcode" class="btn <?= $format === 'barcode' ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= icon('scan') ?> Barcode</a>
    </div>

    <div class="tag-card" id="tag-capture">
        <div class="tag-head">
            <span class="tag-brand"><?= icon('ship') ?> WAVEPOINT</span>
            <span class="tag-kind">Luggage Tag</span>
        </div>
        <div class="tag-body">
            <div class="tag-row"><div class="l">Passenger</div><div class="v"><?= h($l['full_name']) ?></div></div>
            <div class="tag-row"><div class="l">Destination</div><div class="v"><?= h($l['deck_name']) ?> &middot; Cabin <?= h($l['cabin_number']) ?></div></div>
            <div class="tag-row"><div class="l">Item</div><div class="v"><?= h($l['description']) ?></div></div>

            <div class="tag-code-strip">
                <?php if ($format === 'barcode'): ?>
                    <svg id="barcode"></svg>
                <?php else: ?>
                    <div id="qrcode" style="display:flex;justify-content:center;"></div>
                    <p style="margin:10px 0 0;font-weight:700;"><?= h($l['tag_code']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tag-actions no-print">
        <button id="downloadBtn" class="btn btn-primary"><?= icon('download') ?> Download Tag (PNG)</button>
        <button onclick="window.print()" class="btn btn-outline"><?= icon('package') ?> Print Tag</button>
        <a href="../crew/scan.php" class="btn btn-outline"><?= icon('scan') ?> Go to Scan Simulator &raquo;</a>
    </div>
</div>

<script>
<?php if ($format === 'barcode'): ?>
JsBarcode("#barcode", "<?= h($l['tag_code']) ?>", {
    format: "CODE128",
    displayValue: true,
    lineColor: "#0b2140",
    width: 2,
    height: 60
});
<?php else: ?>
new QRCode(document.getElementById("qrcode"), {
    text: "<?= h($l['tag_code']) ?>",
    width: 160,
    height: 160,
    colorDark: "#0b2140"
});
<?php endif; ?>

document.getElementById('downloadBtn').addEventListener('click', function () {
    const btn = this;
    const originalLabel = btn.innerHTML;
    btn.disabled = true;
    btn.textContent = 'Preparing image...';

    // small delay so the QR/barcode library has finished painting the canvas/svg
    setTimeout(function () {
        html2canvas(document.getElementById('tag-capture'), {
            backgroundColor: '#ffffff',
            scale: 3
        }).then(function (canvas) {
            const link = document.createElement('a');
            link.download = 'luggage-tag-<?= h($l['tag_code']) ?>.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
            btn.disabled = false;
            btn.innerHTML = originalLabel;
        }).catch(function (err) {
            console.error('Tag download failed', err);
            alert('Could not generate the image. Please try the Print option instead.');
            btn.disabled = false;
            btn.innerHTML = originalLabel;
        });
    }, 150);
});
</script>

<?php site_footer(); ?>
</body>
</html>
