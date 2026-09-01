<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_admin_login();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_deck') {
        $ship_id = (int)$_POST['ship_id'];
        $deck_number = trim($_POST['deck_number']);
        $deck_name = trim($_POST['deck_name']);
        if ($ship_id && $deck_number !== '' && $deck_name !== '') {
            $pdo->prepare("INSERT INTO ship_decks (ship_id, deck_number, deck_name) VALUES (?, ?, ?)")
                ->execute([$ship_id, $deck_number, $deck_name]);
            $message = "Deck added.";
        }
    } elseif ($action === 'delete_deck') {
        $deck_id = (int)$_POST['deck_id'];
        $inUse = $pdo->prepare("SELECT COUNT(*) FROM cabins WHERE deck_id = ?");
        $inUse->execute([$deck_id]);
        if ($inUse->fetchColumn() > 0) {
            $message = "Cannot delete: this deck still has cabins assigned to it.";
        } else {
            $pdo->prepare("DELETE FROM ship_decks WHERE deck_id = ?")->execute([$deck_id]);
            $message = "Deck deleted.";
        }
    } elseif ($action === 'add_cabin') {
        $deck_id = (int)$_POST['deck_id'];
        $cabin_number = trim($_POST['cabin_number']);
        $capacity = (int)$_POST['capacity'];
        if ($deck_id && $cabin_number !== '') {
            $pdo->prepare("INSERT INTO cabins (cabin_number, deck_id, capacity, status) VALUES (?, ?, ?, 'available')")
                ->execute([$cabin_number, $deck_id, $capacity ?: 2]);
            $message = "Cabin added.";
        }
    } elseif ($action === 'update_cabin_status') {
        $cabin_id = (int)$_POST['cabin_id'];
        $status = $_POST['status'];
        $pdo->prepare("UPDATE cabins SET status = ? WHERE cabin_id = ?")->execute([$status, $cabin_id]);
        $message = "Cabin status updated.";
    } elseif ($action === 'delete_cabin') {
        $cabin_id = (int)$_POST['cabin_id'];
        $inUse = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE cabin_id = ?");
        $inUse->execute([$cabin_id]);
        if ($inUse->fetchColumn() > 0) {
            $message = "Cannot delete: this cabin has booking history.";
        } else {
            $pdo->prepare("DELETE FROM cabins WHERE cabin_id = ?")->execute([$cabin_id]);
            $message = "Cabin deleted.";
        }
    }
}

$ships = $pdo->query("SELECT ship_id, ship_name FROM ships ORDER BY ship_name")->fetchAll();
$decks = $pdo->query(
    "SELECT d.*, s.ship_name FROM ship_decks d JOIN ships s ON d.ship_id = s.ship_id ORDER BY s.ship_name, d.deck_number"
)->fetchAll();
$cabins = $pdo->query(
    "SELECT c.*, d.deck_name, s.ship_name FROM cabins c
     JOIN ship_decks d ON c.deck_id = d.deck_id
     JOIN ships s ON d.ship_id = s.ship_id
     ORDER BY s.ship_name, d.deck_number, c.cabin_number"
)->fetchAll();
?>
<?php admin_shell_start('Decks & Cabins', 'decks_cabins.php'); ?>

<?php if ($message): ?><?php flash($message, str_starts_with($message, 'Cannot') ? 'error' : 'success'); ?><?php endif; ?>

<div class="card">
    <h3><?= icon('plus') ?> Add Deck</h3>
    <form method="post">
        <input type="hidden" name="action" value="add_deck">
        <div class="form-grid">
            <div class="form-group">
                <label>Ship</label>
                <select name="ship_id" required>
                    <?php foreach ($ships as $s): ?>
                        <option value="<?= $s['ship_id'] ?>"><?= h($s['ship_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Deck Number</label><input type="text" name="deck_number" placeholder="e.g. D7" required></div>
            <div class="form-group"><label>Deck Name</label><input type="text" name="deck_name" placeholder="e.g. Deck 7" required></div>
        </div>
        <button type="submit" class="btn btn-primary"><?= icon('layers') ?> Add Deck</button>
    </form>
</div>

<div class="card-head"><h3><?= icon('layers') ?> All Decks <span class="badge badge-neutral"><?= count($decks) ?></span></h3></div>
<div class="table-wrap" style="margin-bottom:24px;">
    <table class="dt">
        <tr><th>Ship</th><th>Deck #</th><th>Deck Name</th><th></th></tr>
        <?php if (!$decks): ?><tr><td colspan="4" class="table-empty">No decks yet.</td></tr><?php endif; ?>
        <?php foreach ($decks as $d): ?>
        <tr>
            <td><?= h($d['ship_name']) ?></td>
            <td><code><?= h($d['deck_number']) ?></code></td>
            <td><strong><?= h($d['deck_name']) ?></strong></td>
            <td>
                <form method="post" class="inline" onsubmit="return confirm('Delete this deck?');">
                    <input type="hidden" name="action" value="delete_deck">
                    <input type="hidden" name="deck_id" value="<?= $d['deck_id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="card">
    <h3><?= icon('plus') ?> Add Cabin</h3>
    <form method="post">
        <input type="hidden" name="action" value="add_cabin">
        <div class="form-grid">
            <div class="form-group">
                <label>Deck</label>
                <select name="deck_id" required>
                    <?php foreach ($decks as $d): ?>
                        <option value="<?= $d['deck_id'] ?>"><?= h($d['ship_name']) ?> - <?= h($d['deck_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Cabin Number</label><input type="text" name="cabin_number" placeholder="e.g. 513" required></div>
            <div class="form-group"><label>Capacity</label><input type="number" name="capacity" value="2"></div>
        </div>
        <button type="submit" class="btn btn-primary"><?= icon('plus') ?> Add Cabin</button>
    </form>
</div>

<div class="card-head"><h3><?= icon('suitcase') ?> All Cabins <span class="badge badge-neutral"><?= count($cabins) ?></span></h3></div>
<div class="table-wrap">
    <table class="dt">
        <tr><th>Ship</th><th>Deck</th><th>Cabin #</th><th>Capacity</th><th>Status</th><th></th></tr>
        <?php if (!$cabins): ?><tr><td colspan="6" class="table-empty">No cabins yet.</td></tr><?php endif; ?>
        <?php foreach ($cabins as $c): ?>
        <tr>
            <td><?= h($c['ship_name']) ?></td>
            <td><?= h($c['deck_name']) ?></td>
            <td><strong><?= h($c['cabin_number']) ?></strong></td>
            <td><?= (int)$c['capacity'] ?></td>
            <td>
                <form method="post" class="inline">
                    <input type="hidden" name="action" value="update_cabin_status">
                    <input type="hidden" name="cabin_id" value="<?= $c['cabin_id'] ?>">
                    <select name="status" onchange="this.form.submit()">
                        <option value="available" <?= $c['status']==='available'?'selected':'' ?>>Available</option>
                        <option value="occupied" <?= $c['status']==='occupied'?'selected':'' ?>>Occupied</option>
                        <option value="maintenance" <?= $c['status']==='maintenance'?'selected':'' ?>>Maintenance</option>
                    </select>
                </form>
            </td>
            <td>
                <form method="post" class="inline" onsubmit="return confirm('Delete this cabin?');">
                    <input type="hidden" name="action" value="delete_cabin">
                    <input type="hidden" name="cabin_id" value="<?= $c['cabin_id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php shell_end(); ?>
