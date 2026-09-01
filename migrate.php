<?php
/**
 * Safe migration runner. Run this in your browser once:
 *   http://localhost/cruise_luggage_system/migrate.php
 *
 * Unlike a raw .sql file, this checks what already exists in YOUR database
 * before creating/altering anything, so it works regardless of whether you
 * have the old schema, the new schema, or a half-applied migration - and
 * it's safe to run more than once. It avoids "ADD COLUMN IF NOT EXISTS"
 * entirely, since that's a MariaDB-only extension that plain MySQL rejects.
 */
require_once __DIR__ . '/config/db.php';

function log_line(string $msg): void {
    echo $msg . "<br>\n";
}

function table_exists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return $stmt->fetchColumn() > 0;
}

function column_exists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->execute([$table, $column]);
    return $stmt->fetchColumn() > 0;
}

echo "<h2>Running migration...</h2>";

// ---- ships table ----
if (!table_exists($pdo, 'ships')) {
    $pdo->exec("CREATE TABLE ships (
        ship_id INT AUTO_INCREMENT PRIMARY KEY,
        ship_name VARCHAR(100) NOT NULL,
        status ENUM('active','maintenance','retired') DEFAULT 'active'
    )");
    $pdo->exec("INSERT INTO ships (ship_name, status) VALUES ('MS Horizon', 'active')");
    log_line("Created 'ships' table and seeded MS Horizon.");
} else {
    log_line("'ships' table already exists, skipped.");
}

// ---- ship_decks.ship_id ----
if (table_exists($pdo, 'ship_decks') && !column_exists($pdo, 'ship_decks', 'ship_id')) {
    $pdo->exec("ALTER TABLE ship_decks ADD COLUMN ship_id INT NULL AFTER deck_id");
    $pdo->exec("UPDATE ship_decks SET ship_id = 1 WHERE ship_id IS NULL");
    log_line("Added 'ship_id' to ship_decks and backfilled to ship 1.");
} else {
    log_line("'ship_decks.ship_id' already present or table missing, skipped.");
}

// ---- voyages.ship_id ----
if (table_exists($pdo, 'voyages') && !column_exists($pdo, 'voyages', 'ship_id')) {
    $pdo->exec("ALTER TABLE voyages ADD COLUMN ship_id INT NULL AFTER voyage_id");
    $pdo->exec("UPDATE voyages SET ship_id = 1 WHERE ship_id IS NULL");
    log_line("Added 'ship_id' to voyages and backfilled to ship 1.");
} else {
    log_line("'voyages.ship_id' already present or table missing, skipped.");
}

// ---- scan_queue table ----
if (!table_exists($pdo, 'scan_queue')) {
    $pdo->exec("CREATE TABLE scan_queue (
        queue_id INT AUTO_INCREMENT PRIMARY KEY,
        tag_code VARCHAR(30) NOT NULL,
        crew_id INT NULL,
        status ENUM('pending','processed','failed') DEFAULT 'pending',
        result_message VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        processed_at DATETIME NULL
    )");
    log_line("Created 'scan_queue' table.");
} else {
    log_line("'scan_queue' table already exists, skipped.");
}

// ---- notifications_log table ----
if (!table_exists($pdo, 'notifications_log')) {
    $pdo->exec("CREATE TABLE notifications_log (
        notif_id INT AUTO_INCREMENT PRIMARY KEY,
        luggage_id INT NULL,
        type ENUM('email','sms') NOT NULL,
        recipient VARCHAR(150) NOT NULL,
        message VARCHAR(255) NOT NULL,
        status ENUM('sent','failed','simulated') DEFAULT 'simulated',
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    log_line("Created 'notifications_log' table.");
} else {
    log_line("'notifications_log' table already exists, skipped.");
}

// ---- crew.status ----
if (table_exists($pdo, 'crew') && !column_exists($pdo, 'crew', 'status')) {
    $pdo->exec("ALTER TABLE crew ADD COLUMN status ENUM('active','inactive') DEFAULT 'active'");
    log_line("Added 'status' column to crew.");
} else {
    log_line("'crew.status' already present or table missing, skipped.");
}

echo "<h3>Migration complete. You can delete migrate.php now, or leave it - re-running it is always safe.</h3>";
echo "<p><a href='public/index.php'>Go to the app &raquo;</a></p>";
