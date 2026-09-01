-- =====================================================
-- Migration v2: Ships, Async Scan Queue, Notifications
-- Run this AFTER schema.sql if you already have the v1 database.
-- (If you're setting up fresh, just import the updated schema.sql instead.)
-- =====================================================
USE cruise_luggage;

-- ---------------------------------------------------
-- MULTI-SHIP SUPPORT
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS ships (
    ship_id INT AUTO_INCREMENT PRIMARY KEY,
    ship_name VARCHAR(100) NOT NULL,
    status ENUM('active','maintenance','retired') DEFAULT 'active'
);

INSERT INTO ships (ship_name, status) VALUES ('MS Horizon', 'active');

ALTER TABLE voyages ADD COLUMN IF NOT EXISTS ship_id INT NULL AFTER voyage_id;
UPDATE voyages SET ship_id = 1 WHERE ship_id IS NULL;
ALTER TABLE ship_decks ADD COLUMN IF NOT EXISTS ship_id INT NULL AFTER deck_id;
UPDATE ship_decks SET ship_id = 1 WHERE ship_id IS NULL;

-- ---------------------------------------------------
-- ASYNC SCAN QUEUE (feeds the CLI Routing Daemon)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS scan_queue (
    queue_id INT AUTO_INCREMENT PRIMARY KEY,
    tag_code VARCHAR(30) NOT NULL,
    crew_id INT NULL,
    status ENUM('pending','processed','failed') DEFAULT 'pending',
    result_message VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL
);

-- ---------------------------------------------------
-- NOTIFICATIONS LOG (email/SMS)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications_log (
    notif_id INT AUTO_INCREMENT PRIMARY KEY,
    luggage_id INT NULL,
    type ENUM('email','sms') NOT NULL,
    recipient VARCHAR(150) NOT NULL,
    message VARCHAR(255) NOT NULL,
    status ENUM('sent','failed','simulated') DEFAULT 'simulated',
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------
-- STAFF MANAGEMENT: active/inactive flag for crew
-- ---------------------------------------------------
ALTER TABLE crew ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') DEFAULT 'active';
