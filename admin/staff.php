<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_admin_login();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']) ?: 'porter';
        $password = $_POST['password'];

        if ($name === '' || $email === '' || $password === '') {
            $message = "All fields are required.";
        } else {
            $stmt = $pdo->prepare("SELECT crew_id FROM crew WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $message = "A crew member with that email already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare(
                    "INSERT INTO crew (full_name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'active')"
                )->execute([$name, $email, $hash, $role]);
                $message = "Crew member added.";
            }
        }
    } elseif ($action === 'toggle_status') {
        $crew_id = (int)$_POST['crew_id'];
        $status = $_POST['status'];
        $pdo->prepare("UPDATE crew SET status = ? WHERE crew_id = ?")->execute([$status, $crew_id]);
        $message = "Status updated.";
    } elseif ($action === 'reset_password') {
        $crew_id = (int)$_POST['crew_id'];
        $newPassword = $_POST['new_password'];
        if ($newPassword !== '') {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE crew SET password_hash = ? WHERE crew_id = ?")->execute([$hash, $crew_id]);
            $message = "Password reset.";
        }
    } elseif ($action === 'delete') {
        $crew_id = (int)$_POST['crew_id'];
        $inUse = $pdo->prepare("SELECT COUNT(*) FROM routing_log WHERE scanned_by_id = ? AND scanned_by_type = 'crew'");
        $inUse->execute([$crew_id]);
        if ($inUse->fetchColumn() > 0) {
            $pdo->prepare("UPDATE crew SET status = 'inactive' WHERE crew_id = ?")->execute([$crew_id]);
            $message = "This crew member has scan history, so they were deactivated instead of deleted.";
        } else {
            $pdo->prepare("DELETE FROM crew WHERE crew_id = ?")->execute([$crew_id]);
            $message = "Crew member deleted.";
        }
    }
}

$crew = $pdo->query("SELECT * FROM crew ORDER BY created_at DESC")->fetchAll();
?>
<?php admin_shell_start('Staff / Crew', 'staff.php'); ?>

<?php if ($message): ?><?php flash($message, str_starts_with($message, 'All fields') || str_starts_with($message, 'A crew') ? 'error' : 'success'); ?><?php endif; ?>

<div class="card">
    <h3><?= icon('user-plus') ?> Add Crew Member</h3>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="form-grid">
            <div class="form-group"><label>Full Name</label><input type="text" name="full_name" placeholder="Full name" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="Email" required></div>
            <div class="form-group"><label>Role</label><input type="text" name="role" placeholder="e.g. porter, supervisor"></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="Password" required></div>
        </div>
        <button type="submit" class="btn btn-primary"><?= icon('user-plus') ?> Add Crew Member</button>
    </form>
</div>

<div class="card-head"><h3><?= icon('users') ?> All Crew <span class="badge badge-neutral"><?= count($crew) ?></span></h3></div>
<div class="table-wrap">
    <table class="dt">
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Reset Password</th><th></th></tr>
        <?php if (!$crew): ?><tr><td colspan="6" class="table-empty">No crew accounts yet.</td></tr><?php endif; ?>
        <?php foreach ($crew as $c): ?>
        <tr>
            <td><strong><?= h($c['full_name']) ?></strong></td>
            <td><?= h($c['email']) ?></td>
            <td><span class="badge badge-info"><?= h($c['role']) ?></span></td>
            <td>
                <form method="post" class="inline">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="crew_id" value="<?= $c['crew_id'] ?>">
                    <select name="status" onchange="this.form.submit()">
                        <option value="active" <?= $c['status']==='active'?'selected':'' ?>>Active</option>
                        <option value="inactive" <?= $c['status']==='inactive'?'selected':'' ?>>Inactive</option>
                    </select>
                </form>
            </td>
            <td>
                <form method="post" class="inline">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="crew_id" value="<?= $c['crew_id'] ?>">
                    <input type="password" name="new_password" placeholder="New password" style="width:130px;display:inline-block;">
                    <button type="submit" class="btn btn-outline btn-sm">Reset</button>
                </form>
            </td>
            <td>
                <form method="post" class="inline" onsubmit="return confirm('Remove this crew member?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="crew_id" value="<?= $c['crew_id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php shell_end(); ?>
