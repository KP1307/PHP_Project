<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_admin_login() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: /cruise_luggage_system/admin/login.php');
        exit;
    }
}

function require_crew_login() {
    if (!isset($_SESSION['crew_id'])) {
        header('Location: /cruise_luggage_system/crew/login.php');
        exit;
    }
}

function current_crew_id() {
    return $_SESSION['crew_id'] ?? null;
}

function current_crew_name() {
    return $_SESSION['crew_name'] ?? '';
}
