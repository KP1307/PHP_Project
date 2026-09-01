<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM crew WHERE email = ?");
    $stmt->execute([$email]);
    $crew = $stmt->fetch();

    if ($crew && password_verify($password, $crew['password_hash'])) {
        if (($crew['status'] ?? 'active') !== 'active') {
            $error = "This account has been deactivated. Contact an administrator.";
        } else {
            $_SESSION['crew_id'] = $crew['crew_id'];
            $_SESSION['crew_name'] = $crew['full_name'];
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<?php auth_start('Crew Portal', 'Scan luggage and manage deliveries on the move'); ?>

<?php if ($error): ?><?php flash($error, 'error'); ?><?php endif; ?>

<form method="post">
    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="crew@ship.com" required autofocus>
    </div>
    <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
    </div>
    <button type="submit" class="btn btn-accent btn-block"><?= icon('scan') ?> Sign in to Crew Portal</button>
</form>

<div class="demo-creds">Default demo login: <strong>crew@ship.com</strong> / <strong>crew123</strong> (after running <code>seed.php</code>)</div>

<?php auth_end(); ?>
