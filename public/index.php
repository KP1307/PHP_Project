<?php require_once __DIR__ . '/../includes/layout.php'; ?>
<?php page_head('Cruise Luggage Control', '..'); ?>
<?php public_topnav('..'); ?>

<div class="hero">
    <span class="eyebrow"><?= icon('anchor') ?> Fleet-wide baggage automation</span>
    <h1>Every bag, tracked from <span>gangway to cabin door.</span></h1>
    <p class="lead">
        Wavepoint routes luggage across check-in, security, sorting and deck
        delivery automatically &mdash; with live crew dashboards and a
        tracking page your passengers can use themselves.
    </p>
    <div class="hero-actions">
        <a class="btn btn-accent" href="../modules/register_passenger.php"><?= icon('user-plus') ?> Register a Passenger</a>
        <a class="btn btn-outline" style="background:rgba(255,255,255,.08);color:#fff;border-color:rgba(255,255,255,.3);" href="track.php"><?= icon('search') ?> Track My Luggage</a>
    </div>
    <div class="hero-trust">
        <span><?= icon('check') ?> Barcode &amp; QR tagging</span>
        <span><?= icon('check') ?> Live crew dashboards</span>
        <span><?= icon('check') ?> Multi-ship &amp; multi-voyage</span>
    </div>
</div>

<div class="portal-grid">
    <div class="portal-card">
        <span class="icon icon-navy"><?= icon('suitcase') ?></span>
        <h3>Check-in Desk</h3>
        <p>Register passengers, assign cabins and print barcode / QR luggage tags in seconds.</p>
        <a class="link" href="../modules/register_passenger.php">Open check-in <?= icon('chevron-right') ?></a>
    </div>
    <div class="portal-card">
        <span class="icon icon-teal"><?= icon('scan') ?></span>
        <h3>Crew Portal</h3>
        <p>Scan bags at every stage, resolve lost-luggage reports and watch the live delivery queue.</p>
        <a class="link" href="../crew/login.php">Crew sign in <?= icon('chevron-right') ?></a>
    </div>
    <div class="portal-card">
        <span class="icon icon-gold"><?= icon('bar-chart') ?></span>
        <h3>Admin Console</h3>
        <p>Manage ships, decks, cabins, voyages and staff, and monitor fleet-wide reports.</p>
        <a class="link" href="../admin/login.php">Admin sign in <?= icon('chevron-right') ?></a>
    </div>
    <div class="portal-card">
        <span class="icon icon-navy"><?= icon('map') ?></span>
        <h3>Passenger Tracking</h3>
        <p>Guests enter their tag code to watch their bag move through every stage in real time.</p>
        <a class="link" href="track.php">Track a bag <?= icon('chevron-right') ?></a>
    </div>
</div>


<div class="media-band">
    <figure class="media-figure">
        <img src="../assets/images/photo-tags.jpg" alt="Suitcases with printed barcode luggage tags at a cruise terminal" width="1200" height="912" loading="lazy">
    </figure>
    <div class="media-copy">
        <span class="eyebrow-dark">Check-in</span>
        <h2>Tag a bag once, follow it everywhere.</h2>
        <p>Register the passenger, assign the cabin and print a barcode or QR tag straight from the browser. Every tag becomes the bag's identity for the rest of the voyage.</p>
        <ul>
            <li><?= icon('check') ?> Printable luggage tags and boarding passes</li>
            <li><?= icon('check') ?> Cabin and deck assignment at check-in</li>
            <li><?= icon('check') ?> Works with any USB barcode or RFID reader</li>
        </ul>
        <a class="btn btn-accent" href="../modules/register_passenger.php"><?= icon('user-plus') ?> Start a check-in</a>
    </div>
</div>

<div class="media-band reverse">
    <figure class="media-figure">
        <img src="../assets/images/photo-crew.jpg" alt="Crew member scanning a luggage barcode with a handheld scanner" width="1200" height="912" loading="lazy">
    </figure>
    <div class="media-copy">
        <span class="eyebrow-dark">On board</span>
        <h2>Crew scan it. The dashboard updates itself.</h2>
        <p>Each scan advances the bag through the routing pipeline and refreshes the live queues, so nobody has to ask where a bag has got to.</p>
        <ul>
            <li><?= icon('check') ?> Live pending-delivery queue, refreshed automatically</li>
            <li><?= icon('check') ?> Lost-luggage reports with resolution tracking</li>
            <li><?= icon('check') ?> Email and SMS alerts on delivery or loss</li>
        </ul>
        <a class="btn btn-outline" href="../crew/login.php"><?= icon('scan') ?> Open the crew portal</a>
    </div>
</div>

<div class="section">
    <h2 class="section-title text-center">How the routing engine works</h2>
    <p class="section-sub">One consistent pipeline, from the moment a bag is tagged to the moment it's outside the cabin door.</p>
    <div class="feature-strip">
        <div class="feat"><div class="num">01</div><div class="lbl">Check-in</div></div>
        <div class="feat"><div class="num">02</div><div class="lbl">Security</div></div>
        <div class="feat"><div class="num">03</div><div class="lbl">Sorting Area</div></div>
        <div class="feat"><div class="num">04</div><div class="lbl">Deck Transfer</div></div>
        <div class="feat"><div class="num">05</div><div class="lbl">Cabin Delivery</div></div>
        <div class="feat"><div class="num">06</div><div class="lbl">Delivered</div></div>
    </div>
</div>

<?php site_footer(); ?>
<?php page_foot(); ?>
