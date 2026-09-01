<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/layout.php';

$tag_code = trim($_GET['tag_code'] ?? '');
$luggage = null;

if ($tag_code !== '') {
    $stmt = $pdo->prepare(
        "SELECT l.*, p.full_name, d.deck_name, c.cabin_number
         FROM luggage l
         JOIN bookings b ON l.booking_id = b.booking_id
         JOIN passengers p ON b.passenger_id = p.passenger_id
         JOIN cabins c ON b.cabin_id = c.cabin_id
         JOIN ship_decks d ON c.deck_id = d.deck_id
         WHERE l.tag_code = ?"
    );
    $stmt->execute([$tag_code]);
    $luggage = $stmt->fetch();
}

$stages = stage_sequence();
$currentIndex = $luggage ? array_search($luggage['current_stage'], $stages, true) : -1;
?>
<?php page_head('Track My Luggage', '..'); ?>
<?php public_topnav('..'); ?>

<div class="section" style="padding-top:48px;padding-bottom:70px;">
    <h1 class="text-center"><?= icon('map') ?> Track My Luggage</h1>
    <p class="section-sub">Enter the tag code printed on your luggage receipt to see exactly where your bag is right now.</p>

    <div class="card" style="max-width:520px;margin:0 auto 28px;">
        <form method="get" class="search-form" style="margin-bottom:0;">
            <input type="text" name="tag_code" value="<?= h($tag_code) ?>" placeholder="e.g. LUG-AB12CD34">
            <button type="submit" class="btn btn-primary"><?= icon('search') ?> Track</button>
        </form>
    </div>

    <?php if ($tag_code !== '' && !$luggage): ?>
        <div class="card" style="max-width:520px;margin:0 auto;">
            <?php flash("No luggage found for tag code \"$tag_code\". Double check the code and try again.", 'error'); ?>
        </div>
    <?php elseif ($luggage): ?>
        <div class="card" style="max-width:560px;margin:0 auto;">
            <div class="card-head">
                <div>
                    <h3 style="margin-bottom:2px;"><?= h($luggage['description']) ?></h3>
                    <small>Passenger: <?= h($luggage['full_name']) ?> &middot; Destination: <?= h($luggage['deck_name']) ?> - Cabin <?= h($luggage['cabin_number']) ?></small>
                </div>
                <?= status_badge($luggage['status']) ?>
            </div>

            <?php if ($luggage['status'] === 'lost'): ?>
                <?php flash('This item has been reported LOST. Please contact crew or visit the guest services desk.', 'error'); ?>
            <?php else: ?>
                <ul class="stage-tracker">
                <?php foreach ($stages as $i => $stage): ?>
                    <?php $cls = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : ''); ?>
                    <li class="<?= $cls ?>">
                        <span class="stage-dot"><?= $i < $currentIndex ? '&#10003;' : $i + 1 ?></span>
                        <div>
                            <div class="stage-label"><?= h($stage) ?></div>
                            <?php if ($i === $currentIndex): ?><div class="stage-sub">Currently here</div><?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php site_footer(); ?>
<?php page_foot(); ?>
