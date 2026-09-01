<?php
// Run this ONCE in your browser after importing schema.sql, then delete it.
require_once __DIR__ . '/config/db.php';

$adminUser = 'admin';
$adminPass = password_hash('admin123', PASSWORD_DEFAULT);

$crewName = 'John Porter';
$crewEmail = 'crew@ship.com';
$crewPass = password_hash('crew123', PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("SELECT admin_id FROM admin_users WHERE username = ?");
    $stmt->execute([$adminUser]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)")
            ->execute([$adminUser, $adminPass]);
        echo "Admin account created (admin / admin123)<br>";
    } else {
        echo "Admin account already exists<br>";
    }

    $stmt = $pdo->prepare("SELECT crew_id FROM crew WHERE email = ?");
    $stmt->execute([$crewEmail]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO crew (full_name, email, password_hash, role) VALUES (?, ?, ?, 'porter')")
            ->execute([$crewName, $crewEmail, $crewPass]);
        echo "Crew account created (crew@ship.com / crew123)<br>";
    } else {
        echo "Crew account already exists<br>";
    }

    echo "<br><strong>Done. Please delete seed.php now for security.</strong>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
