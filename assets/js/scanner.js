/**
 * Reusable camera-based QR/barcode scanner.
 * Wraps the html5-qrcode library behind a simple start/stop toggle button,
 * and hands the decoded text back to the caller via onDecoded(text).
 *
 * Usage:
 *   initCameraScanner({
 *     buttonId: 'cameraScanBtn',
 *     readerId: 'cameraReader',
 *     onDecoded: function(text) { ... }
 *   });
 *
 * Requires html5-qrcode to already be loaded on the page.
 */
function initCameraScanner(opts) {
    const btn = document.getElementById(opts.buttonId);
    const readerEl = document.getElementById(opts.readerId);
    if (!btn || !readerEl) return;

    let scanner = null;
    let running = false;

    async function start() {
        if (typeof Html5Qrcode === 'undefined') {
            alert('Camera scanner library failed to load. Check your internet connection and try again.');
            return;
        }
        readerEl.style.display = 'block';
        btn.textContent = 'Starting camera...';
        btn.disabled = true;

        scanner = new Html5Qrcode(opts.readerId);

        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 240, height: 240 } },
                function (decodedText) {
                    // Successful decode - stop the camera and hand off the result
                    opts.onDecoded(decodedText);
                    stop();
                },
                function () {
                    // per-frame "no code found yet" - ignore, this fires constantly while scanning
                }
            );
            running = true;
            btn.textContent = 'Stop Camera';
            btn.disabled = false;
        } catch (err) {
            console.error('Camera start failed', err);
            alert('Could not access the camera. Make sure you\'ve allowed camera permission, and that you\'re on HTTPS or localhost (browsers block camera access on plain HTTP over a network).');
            readerEl.style.display = 'none';
            btn.textContent = opts.startLabel || 'Scan with Camera';
            btn.disabled = false;
        }
    }

    async function stop() {
        if (scanner && running) {
            try {
                await scanner.stop();
                scanner.clear();
            } catch (e) {
                // already stopped, ignore
            }
        }
        running = false;
        readerEl.style.display = 'none';
        btn.textContent = opts.startLabel || 'Scan with Camera';
    }

    btn.addEventListener('click', function () {
        if (running) {
            stop();
        } else {
            start();
        }
    });

    // Clean up if the user navigates away mid-scan
    window.addEventListener('beforeunload', function () {
        if (running && scanner) {
            scanner.stop().catch(function () {});
        }
    });
}
