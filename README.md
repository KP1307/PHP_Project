# Wavepoint — Cruise Luggage Management & Routing System (PHP)

## Bug fix (this round)
The "Barcode" option on the luggage tag page showed nothing because the
JsBarcode CDN link pointed at version `3.11.5`, which was never actually
published — the script silently 404'd and the library never loaded. Fixed
to the real published version `3.12.3`. QR was unaffected, which is why only
barcode looked broken.

## Visual overhaul (new)
The whole app now shares one commercial-style design system instead of raw
unstyled HTML:
- `assets/css/style.css` — a full design system (navy/teal/gold cruise-line
  palette, Inter/Poppins fonts, cards, stat tiles, badges, buttons, forms,
  tables) shared by every page.
- `includes/layout.php` + `includes/icons.php` — shared PHP helpers that
  render the sidebar app-shell (admin/crew), the public marketing nav +
  hero landing page, login cards, flash/alert messages, and status badges,
  so every screen looks consistent without repeating markup.
- Admin and crew now sit inside a proper dashboard shell (dark sidebar +
  topbar + content), with color-coded stat cards on the admin dashboard.
- The public landing page (`public/index.php`) is now a real marketing
  homepage with a hero section and portal cards for each user type.
- `public/track.php` shows a vertical stage-tracker/timeline instead of a
  plain list.
- Luggage tags and boarding passes are styled as printable cards (barcode/QR
  still generated client-side via JsBarcode/qrcode.js).
- Added a **date-range report** panel to the admin dashboard (bags added /
  delivered / pending / lost between two dates) — this was the one item
  previously listed as "not built" under Reports.

## What's built (everything from the spec, all working)

| Module | Status |
|---|---|
| 1. Passenger Management (register, cabin assign, boarding pass) | ✅ Done |
| 2. Luggage Management (add luggage, barcode/QR tag generation) | ✅ Done |
| 3. Barcode/QR Scanner (simulated input, works with real USB scanners) | ✅ Done |
| 4. Routing Engine — web-triggered version | ✅ Done |
| 4b. Routing Engine — literal CLI background daemon | ✅ Done (`bin/routing_daemon.php`) |
| 5. Conveyor Route Simulation (visual stage tracker) | ✅ Done |
| 6. Crew Dashboard (pending luggage, scan, report lost, history) | ✅ Done, now live-refreshing |
| 7. Admin Panel: passengers, ships, decks/cabins, voyages, staff, search by tag | ✅ Done |
| 8. Reports (counts, delivered vs pending, avg processing time) | ✅ Done, now live-refreshing |
| QR code option | ✅ Done |
| Email/SMS notifications on delivery/lost | ✅ Done (email real, SMS simulated/log-only) |
| Real-time / live dashboard | ✅ Done (5s polling via `api/`) |
| Multiple ships and voyages | ✅ Done |
| AI-based optimal routing | ⏳ Not built — the current routing is a fixed sequence, which is standard for this kind of system. Tell me if you specifically need optimization logic. |
| IoT RFID hardware integration | ⏳ Can't test without real hardware, but the scan input fields already work with any USB barcode/RFID reader configured as a keyboard-wedge device — no extra code needed. |

## Setup (XAMPP / local server)

**Fresh install:**
1. Copy the `cruise_luggage_system` folder into your `htdocs` folder.
2. Start Apache + MySQL (e.g. via XAMPP control panel).
3. Open **phpMyAdmin**, create nothing manually — just import `sql/schema.sql`
   (it creates the `cruise_luggage` database, all tables including ships/scan_queue/
   notifications_log, and seed data).
4. Edit `config/db.php` if your MySQL username/password differ from XAMPP defaults.
5. In your browser, visit:
   `http://localhost/cruise_luggage_system/seed.php`
   This creates the admin and crew login accounts (uses real password hashing).
   **Delete seed.php after running it once.**
6. Visit `http://localhost/cruise_luggage_system/public/index.php` to start.

**Already had the earlier version installed (or your import partially failed)?**
Just visit `http://localhost/cruise_luggage_system/migrate.php` in your
browser. It's a PHP script, not a raw `.sql` file, so it checks what already
exists in your database column-by-column before changing anything — this
avoids the MySQL/MariaDB syntax differences that raw migration `.sql` files
often hit (e.g. `ADD COLUMN IF NOT EXISTS` isn't supported on plain MySQL).
It's safe to run more than once, so if you're not sure what state your
database is in, just run it — it'll skip anything already there.

## New features and how to demo them

- **QR code**: on the luggage tag page, click "QR Code" instead of "Barcode"
  (`modules/luggage_tag.php?luggage_id=X&format=qr`). Tag generation now
  defaults to QR since that's what the camera scanner reads.
- **Download tag as PNG**: the tag page has a "Download Tag (PNG)" button that
  captures the actual rendered tag card (passenger, destination, code) and
  saves it as a real image file via `html2canvas` — no server-side image
  generation needed.
- **Camera scanning (crew + admin)**: every scan/search box (`crew/scan.php`,
  `crew/scan_async.php`, `crew/report_lost.php`, `crew/route_history.php`,
  `admin/dashboard.php`) now has a "Scan with Camera" button next to the text
  field, using `html5-qrcode` to read a QR/barcode straight from the device
  camera and auto-fill the field (auto-submitting where that makes sense).
  **Browsers only allow camera access on `https://` or `http://localhost`** —
  if you're testing from another device on your network via a plain `http://`
  IP address, the browser will block it and you'll only be able to use the
  manual text field (which still works exactly as before, and is still the
  right choice for a real USB scanner anyway).
- **CLI background daemon**: open a terminal, `cd` into `cruise_luggage_system`,
  run `php bin/routing_daemon.php` — it prints and stays running. Then in a
  browser, log in as crew and go to **"Scan Luggage (Daemon Mode)"**
  (`crew/scan_async.php`). Scans there are queued into the `scan_queue` table;
  watch your terminal process them within ~2 seconds, and refresh the page to
  see status flip from `pending` to `processed`. This is the literal
  always-on background process version of the spec's Module 4.
- **Notifications**: whenever a bag reaches "Delivered" or is reported "Lost",
  the system tries to email the passenger (via PHP's `mail()`) and logs an SMS
  attempt. View everything sent/simulated at `admin/notifications.php`. If
  your local server has no mail server configured, emails just log as
  "simulated" instead of erroring — the demo still works either way.
- **Live dashboard**: crew and admin dashboards auto-refresh every 5 seconds
  via `api/pending_luggage.php` and `api/report_counts.php` — no page reload
  needed. Scan a bag in one browser tab and watch the dashboard update in
  another.
- **Multiple ships/voyages**: `admin/ships.php` to add ships,
  `admin/decks_cabins.php` to add decks/cabins per ship, `admin/voyages.php`
  to schedule voyages per ship. Passenger registration already lets you pick
  any active voyage/cabin.
- **Staff management**: `admin/staff.php` — add crew, deactivate/reactivate
  logins, reset passwords. Deactivated crew can't log in.

### Default logins
- Admin: `admin` / `admin123`
- Crew: `crew@ship.com` / `crew123`

## How the flow works end-to-end (for your demo/viva)

1. **Check-in desk** → `modules/register_passenger.php`
   Registers passenger, creates booking, assigns cabin, shows boarding pass.
2. From the boarding pass, click **"+ Add luggage"** → `modules/add_luggage.php`
   Generates a unique tag code and shows a printable barcode tag
   (`modules/luggage_tag.php`, using JsBarcode).
3. **Crew logs in** → `crew/login.php` → `crew/scan.php`
   Each scan calls `process_scan()` in `includes/routing_engine.php`, which:
   - looks up the bag by tag code
   - finds the passenger's assigned deck/cabin
   - advances it to the next stage in the fixed sequence:
     `Check-in → Security → Sorting Area → Deck Transfer → Cabin Delivery → Delivered`
   - writes a row to `routing_log` (the audit trail)
4. **Passengers** can self-track at `public/track.php` using their tag code —
   shows a visual stage-by-stage progress list.
5. **Admin** (`admin/dashboard.php`) can search any bag by tag ID, see live counts
   (delivered/pending/lost), and view lost-luggage reports.

## Note on the "Routing Engine (Daemon)"

The spec describes it as a background daemon. There are now genuinely **two
working versions**:
1. **Web-triggered** (`crew/scan.php`) — the routing logic runs on each scan
   HTTP request. Simplest to demo, no terminal needed.
2. **True CLI daemon** (`bin/routing_daemon.php` + `crew/scan_async.php`) —
   an always-running background process that polls a queue table, exactly
   as described in the spec. Needs a terminal open during your demo.

Both call the exact same `process_scan()` function in
`includes/routing_engine.php`, so the business logic is identical either way
— pick whichever is easier for your demo/viva.

## Still not built (only genuinely deferred items)
- AI-based optimal routing (the current routing engine uses a fixed stage
  sequence, which is realistic for this kind of system — flag if you
  specifically need optimization/pathfinding logic instead)
- Real IoT/RFID hardware wiring (untestable without physical hardware, but
  every scan input field already accepts input from a real USB scanner)
