-- =====================================================
-- Cruise Luggage Management & Routing System
-- Database Schema
-- =====================================================

CREATE DATABASE IF NOT EXISTS cruise_luggage;
USE cruise_luggage;

-- ---------------------------------------------------
-- SHIPS (multi-ship support)
-- ---------------------------------------------------
CREATE TABLE ships (
    ship_id INT AUTO_INCREMENT PRIMARY KEY,
    ship_name VARCHAR(100) NOT NULL,
    status ENUM('active','maintenance','retired') DEFAULT 'active'
);

-- ---------------------------------------------------
-- SHIP STRUCTURE
-- ---------------------------------------------------
CREATE TABLE ship_decks (
    deck_id INT AUTO_INCREMENT PRIMARY KEY,
    ship_id INT NOT NULL,
    deck_number VARCHAR(10) NOT NULL,
    deck_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (ship_id) REFERENCES ships(ship_id)
);

CREATE TABLE cabins (
    cabin_id INT AUTO_INCREMENT PRIMARY KEY,
    cabin_number VARCHAR(10) NOT NULL,
    deck_id INT NOT NULL,
    capacity INT DEFAULT 2,
    status ENUM('available','occupied','maintenance') DEFAULT 'available',
    FOREIGN KEY (deck_id) REFERENCES ship_decks(deck_id)
);

-- ---------------------------------------------------
-- VOYAGES (supports "multiple ships/voyages" later)
-- ---------------------------------------------------
CREATE TABLE voyages (
    voyage_id INT AUTO_INCREMENT PRIMARY KEY,
    ship_id INT NOT NULL,
    voyage_name VARCHAR(100) NOT NULL,
    departure_date DATE,
    return_date DATE,
    status ENUM('upcoming','active','completed') DEFAULT 'upcoming',
    FOREIGN KEY (ship_id) REFERENCES ships(ship_id)
);

-- ---------------------------------------------------
-- PASSENGERS & BOOKINGS
-- ---------------------------------------------------
CREATE TABLE passengers (
    passenger_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    passport_no VARCHAR(30),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_ref VARCHAR(20) UNIQUE NOT NULL,
    passenger_id INT NOT NULL,
    voyage_id INT NOT NULL,
    cabin_id INT NOT NULL,
    check_in_date DATETIME,
    status ENUM('booked','checked_in','completed','cancelled') DEFAULT 'booked',
    FOREIGN KEY (passenger_id) REFERENCES passengers(passenger_id),
    FOREIGN KEY (voyage_id) REFERENCES voyages(voyage_id),
    FOREIGN KEY (cabin_id) REFERENCES cabins(cabin_id)
);

-- ---------------------------------------------------
-- CREW & ADMIN (simple auth)
-- ---------------------------------------------------
CREATE TABLE crew (
    crew_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'porter',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin_users (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------
-- LUGGAGE
-- ---------------------------------------------------
CREATE TABLE luggage (
    luggage_id INT AUTO_INCREMENT PRIMARY KEY,
    tag_code VARCHAR(30) UNIQUE NOT NULL,   -- barcode/RFID value encoded on the tag
    booking_id INT NOT NULL,
    description VARCHAR(150),
    weight_kg DECIMAL(5,2),
    current_stage ENUM(
        'Check-in',
        'Security',
        'Sorting Area',
        'Deck Transfer',
        'Cabin Delivery',
        'Delivered',
        'Lost'
    ) DEFAULT 'Check-in',
    status ENUM('in_transit','delivered','lost') DEFAULT 'in_transit',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);

-- ---------------------------------------------------
-- ROUTING LOG (every scan/movement event)
-- ---------------------------------------------------
CREATE TABLE routing_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    luggage_id INT NOT NULL,
    stage VARCHAR(50) NOT NULL,
    scanned_by_type ENUM('crew','system') DEFAULT 'crew',
    scanned_by_id INT NULL,           -- crew_id if scanned_by_type = crew
    scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes VARCHAR(255),
    FOREIGN KEY (luggage_id) REFERENCES luggage(luggage_id)
);

-- ---------------------------------------------------
-- LOST LUGGAGE REPORTS
-- ---------------------------------------------------
CREATE TABLE lost_luggage_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    luggage_id INT NOT NULL,
    reported_by_id INT,
    reported_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('open','investigating','resolved') DEFAULT 'open',
    notes VARCHAR(255),
    FOREIGN KEY (luggage_id) REFERENCES luggage(luggage_id)
);

-- ---------------------------------------------------
-- ASYNC SCAN QUEUE (feeds the CLI Routing Daemon - bin/routing_daemon.php)
-- ---------------------------------------------------
CREATE TABLE scan_queue (
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
CREATE TABLE notifications_log (
    notif_id INT AUTO_INCREMENT PRIMARY KEY,
    luggage_id INT NULL,
    type ENUM('email','sms') NOT NULL,
    recipient VARCHAR(150) NOT NULL,
    message VARCHAR(255) NOT NULL,
    status ENUM('sent','failed','simulated') DEFAULT 'simulated',
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- SEED DATA (so the system is testable immediately)
-- =====================================================

INSERT INTO ships (ship_name, status) VALUES ('MS Horizon', 'active');

INSERT INTO ship_decks (ship_id, deck_number, deck_name) VALUES
(1, 'D3','Deck 3'), (1, 'D4','Deck 4'), (1, 'D5','Deck 5'), (1, 'D6','Deck 6');

INSERT INTO cabins (cabin_number, deck_id, capacity) VALUES
('301', 1, 2), ('302', 1, 2),
('401', 2, 4), ('402', 2, 2),
('501', 3, 2), ('512', 3, 2),
('601', 4, 4);

INSERT INTO voyages (ship_id, voyage_name, departure_date, return_date, status) VALUES
(1, 'Mediterranean Explorer - Aug 2026', '2026-08-10', '2026-08-20', 'upcoming');

-- NOTE: default admin/crew login accounts are NOT inserted here because their
-- passwords must be hashed by PHP's password_hash() function, not hand-written.
-- After importing this schema, run seed.php once in your browser
-- (e.g. http://localhost/cruise_luggage_system/seed.php) to create:
--   Admin login  -> username: admin   / password: admin123
--   Crew login   -> email: crew@ship.com / password: crew123
-- Delete seed.php after running it once.
