<?php
/**
 * Generates a unique luggage tag code, e.g. LUG-7F3K9A2C
 * This value is what gets encoded into the barcode/QR image and
 * is what the crew "scans" (types or reads) at each stage.
 */
function generate_tag_code(): string {
    return 'LUG-' . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Generates a unique booking reference, e.g. BKG-4X9P2Q
 */
function generate_booking_ref(): string {
    return 'BKG-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * The fixed order of stages a bag moves through.
 * This is the backbone of the Routing Engine.
 */
function stage_sequence(): array {
    return [
        'Check-in',
        'Security',
        'Sorting Area',
        'Deck Transfer',
        'Cabin Delivery',
        'Delivered',
    ];
}

/**
 * Given the current stage, return the next stage in sequence.
 * Returns null if already at the final stage.
 */
function next_stage(string $currentStage): ?string {
    $seq = stage_sequence();
    $idx = array_search($currentStage, $seq, true);
    if ($idx === false || $idx === count($seq) - 1) {
        return null;
    }
    return $seq[$idx + 1];
}

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Polyfill for PHP < 8.0, in case of an older XAMPP install.
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}
