<?php
/**
 * Notification helpers.
 *
 * EMAIL uses PHP's built-in mail() function. This works out of the box on a
 * real server with an MTA configured (or via XAMPP + a tool like Mercury Mail
 * / a fake SMTP catcher for local testing). If mail() isn't configured on
 * your machine, sending will fail silently to log-only mode below - the
 * notification is still recorded in notifications_log either way, so the
 * rest of the system (and your demo) keeps working.
 *
 * SMS has no free/local simulator, so send_sms_notification() logs the
 * message to notifications_log as 'simulated' instead of calling a real
 * gateway. To go live, drop in a provider's API call (e.g. Twilio) where
 * marked below - the function signature won't need to change.
 */

function notify_email(PDO $pdo, ?int $luggage_id, string $to, string $subject, string $body): bool {
    if ($to === '') {
        return false;
    }

    $sent = false;
    if (function_exists('mail')) {
        $headers = "From: no-reply@cruiseship.local\r\nContent-Type: text/plain; charset=UTF-8";
        $sent = @mail($to, $subject, $body, $headers);
    }

    $pdo->prepare(
        "INSERT INTO notifications_log (luggage_id, type, recipient, message, status)
         VALUES (?, 'email', ?, ?, ?)"
    )->execute([$luggage_id, $to, $subject . ' - ' . $body, $sent ? 'sent' : 'simulated']);

    return $sent;
}

function notify_sms(PDO $pdo, ?int $luggage_id, string $to, string $message): bool {
    if ($to === '') {
        return false;
    }

    // ---- Real SMS gateway integration point ----
    // Example (Twilio, uncomment and configure if you have an account):
    //
    // $sid = 'YOUR_TWILIO_SID';
    // $token = 'YOUR_TWILIO_TOKEN';
    // $from = 'YOUR_TWILIO_NUMBER';
    // $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json");
    // curl_setopt($ch, CURLOPT_POST, true);
    // curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
    // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['To'=>$to,'From'=>$from,'Body'=>$message]));
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // $response = curl_exec($ch);
    // $sent = (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 201);

    $sent = false; // no gateway configured -> logged as simulated

    $pdo->prepare(
        "INSERT INTO notifications_log (luggage_id, type, recipient, message, status)
         VALUES (?, 'sms', ?, ?, ?)"
    )->execute([$luggage_id, $to, $message, $sent ? 'sent' : 'simulated']);

    return $sent;
}

/**
 * Fires the right notification(s) for a luggage stage change.
 * Called from the routing engine after every successful scan.
 */
function notify_stage_change(PDO $pdo, array $luggage): void {
    // Look up passenger contact info (luggage array from routing_engine already
    // has full_name, but we need email/phone which aren't joined there yet)
    $stmt = $pdo->prepare(
        "SELECT p.email, p.phone FROM luggage l
         JOIN bookings b ON l.booking_id = b.booking_id
         JOIN passengers p ON b.passenger_id = p.passenger_id
         WHERE l.luggage_id = ?"
    );
    $stmt->execute([$luggage['luggage_id']]);
    $contact = $stmt->fetch();
    if (!$contact) return;

    $stage = $luggage['current_stage'];

    if ($stage === 'Delivered') {
        $subject = "Your luggage has been delivered";
        $msg = "Hi {$luggage['full_name']}, your bag ({$luggage['description']}) has been delivered to {$luggage['deck_name']} - Cabin {$luggage['cabin_number']}.";
        notify_email($pdo, $luggage['luggage_id'], $contact['email'] ?? '', $subject, $msg);
        notify_sms($pdo, $luggage['luggage_id'], $contact['phone'] ?? '', $msg);
    } elseif ($stage === 'Lost') {
        $subject = "Update on your luggage";
        $msg = "Hi {$luggage['full_name']}, we're sorry to inform you your bag ({$luggage['description']}) has been reported lost. Our crew is investigating.";
        notify_email($pdo, $luggage['luggage_id'], $contact['email'] ?? '', $subject, $msg);
        notify_sms($pdo, $luggage['luggage_id'], $contact['phone'] ?? '', $msg);
    }
}
