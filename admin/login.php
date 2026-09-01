<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<?php auth_start('Admin Console', 'Manage ships, staff and fleet-wide reports'); ?>

<?php if ($error): ?><?php flash($error, 'error'); ?><?php endif; ?>

<form method="post">
    <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="admin" required autofocus>
    </div>
    <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block"><?= icon('shield') ?> Sign in to Admin Console</button>
</form>

<div class="demo-creds">Default demo login: <strong>admin</strong> / <strong>admin123</strong> (after running <code>seed.php</code>)</div>

<?php auth_end(); ?>
