<?php
/**
 * Routing Engine Daemon (CLI)
 * ----------------------------
 * Run this from a terminal (NOT through a browser):
 *   php bin/routing_daemon.php
 *
 * It runs forever, polling the `scan_queue` table for new scan events
 * (rows inserted by crew/scan_async.php, which simulates a scanner/RFID
 * reader pushing events into a queue instead of calling the engine
 * directly over HTTP). For each pending row it calls the exact same
 * process_scan() function used by the synchronous web version, so both
 * modes share identical business logic.
 *
 * Stop with Ctrl+C.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/routing_engine.php';

echo "[Routing Daemon] Started. Polling scan_queue every 2 seconds. Press Ctrl+C to stop.\n";

while (true) {
    $stmt = $pdo->query(
        "SELECT * FROM scan_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 10"
    );
    $jobs = $stmt->fetchAll();

    foreach ($jobs as $job) {
        echo "[" . date('Y-m-d H:i:s') . "] Processing tag '{$job['tag_code']}' (queue #{$job['queue_id']})... ";

        $result = process_scan($pdo, $job['tag_code'], $job['crew_id']);

        $newStatus = $result['success'] ? 'processed' : 'failed';
        $pdo->prepare(
            "UPDATE scan_queue SET status = ?, result_message = ?, processed_at = NOW() WHERE queue_id = ?"
        )->execute([$newStatus, $result['message'], $job['queue_id']]);

        echo strtoupper($newStatus) . ": {$result['message']}\n";
    }

    if (empty($jobs)) {
        // Nothing to do - avoid hammering the DB
        sleep(2);
    }
}
