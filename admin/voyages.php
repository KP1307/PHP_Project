<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_admin_login();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $ship_id = (int)$_POST['ship_id'];
        $name = trim($_POST['voyage_name']);
        $departure = $_POST['departure_date'];
        $return = $_POST['return_date'];
        if ($ship_id && $name !== '') {
            $pdo->prepare(
                "INSERT INTO voyages (ship_id, voyage_name, departure_date, return_date, status)
                 VALUES (?, ?, ?, ?, 'upcoming')"
            )->execute([$ship_id, $name, $departure ?: null, $return ?: null]);
            $message = "Voyage added.";
        }
    } elseif ($action === 'update_status') {
        $voyage_id = (int)$_POST['voyage_id'];
        $status = $_POST['status'];
        $pdo->prepare("UPDATE voyages SET status = ? WHERE voyage_id = ?")->execute([$status, $voyage_id]);
        $message = "Voyage status updated.";
    } elseif ($action === 'delete') {
        $voyage_id = (int)$_POST['voyage_id'];
        $inUse = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE voyage_id = ?");
        $inUse->execute([$voyage_id]);
        if ($inUse->fetchColumn() > 0) {
            $message = "Cannot delete: this voyage has bookings attached.";
        } else {
            $pdo->prepare("DELETE FROM voyages WHERE voyage_id = ?")->execute([$voyage_id]);
            $message = "Voyage deleted.";
        }
    }
}

$ships = $pdo->query("SELECT ship_id, ship_name FROM ships ORDER BY ship_name")->fetchAll();
$voyages = $pdo->query(
    "SELECT v.*, s.ship_name FROM voyages v JOIN ships s ON v.ship_id = s.ship_id ORDER BY v.departure_date DESC"
)->fetchAll();
?>
<?php admin_shell_start('Voyages', 'voyages.php'); ?>

<?php if ($message): ?><?php flash($message, str_starts_with($message, 'Cannot') ? 'error' : 'success'); ?><?php endif; ?>

<div class="card">
    <h3><?= icon('plus') ?> Add Voyage</h3>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="form-grid">
            <div class="form-group">
                <label>Ship</label>
                <select name="ship_id" required>
                    <?php foreach ($ships as $s): ?>
                        <option value="<?= $s['ship_id'] ?>"><?= h($s['ship_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Voyage Name</label><input type="text" name="voyage_name" placeholder="e.g. Caribbean 7-Night" required></div>
            <div class="form-group"><label>Departure</label><input type="date" name="departure_date"></div>
            <div class="form-group"><label>Return</label><input type="date" name="return_date"></div>
        </div>
        <button type="submit" class="btn btn-primary"><?= icon('compass') ?> Add Voyage</button>
    </form>
</div>

<div class="card-head"><h3><?= icon('compass') ?> All Voyages <span class="badge badge-neutral"><?= count($voyages) ?></span></h3></div>
<div class="table-wrap">
    <table class="dt">
        <tr><th>Ship</th><th>Voyage</th><th>Departure</th><th>Return</th><th>Status</th><th></th></tr>
        <?php if (!$voyages): ?><tr><td colspan="6" class="table-empty">No voyages scheduled yet.</td></tr><?php endif; ?>
        <?php foreach ($voyages as $v): ?>
        <tr>
            <td><?= h($v['ship_name']) ?></td>
            <td><strong><?= h($v['voyage_name']) ?></strong></td>
            <td><?= h($v['departure_date'] ?? '&mdash;') ?></td>
            <td><?= h($v['return_date'] ?? '&mdash;') ?></td>
            <td>
                <form method="post" class="inline">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="voyage_id" value="<?= $v['voyage_id'] ?>">
                    <select name="status" onchange="this.form.submit()">
                        <option value="upcoming" <?= $v['status']==='upcoming'?'selected':'' ?>>Upcoming</option>
                        <option value="active" <?= $v['status']==='active'?'selected':'' ?>>Active</option>
                        <option value="completed" <?= $v['status']==='completed'?'selected':'' ?>>Completed</option>
                    </select>
                </form>
            </td>
            <td>
                <form method="post" class="inline" onsubmit="return confirm('Delete this voyage?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="voyage_id" value="<?= $v['voyage_id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php shell_end(); ?>
