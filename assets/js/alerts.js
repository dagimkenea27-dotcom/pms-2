/**
 * Stock Alert System
 * Polls server for low stock and triggers popup + sound.
 */

const AlertSystem = {
    // Configuration
    CHECK_INTERVAL: 3600000, // 1 hour in milliseconds
    SNOOZE_DURATION: 10 * 60 * 1000, // 10 minutes
    // Dynamically determine API path - works for both root and subdirectory installations
    API_URL: (() => {
        // Get the directory where the current page is located
        const currentPath = window.location.pathname;
        const pathParts = currentPath.split('/').filter(p => p);

        // Remove the last part (filename) and any 'products', 'categories', etc folders
        // to get back to the application root
        let basePath = '';

        // Check if we're in a subdirectory installation (like /stock_management/)
        if (currentPath.includes('/stock_management/')) {
            basePath = '/stock_management';
        } else {
            // We're at domain root - just use absolute path
            basePath = '';
        }

        return basePath + '/api/check_alerts.php';
    })(),
    STORAGE_KEY_SNOOZE: 'stock_alert_snooze_until',

    // Base64 Beep Sound (Short, non-intrusive)
    BEEP_SOUND: 'data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU', // Placeholder short beep, will replace with fuller sound if needed or rely on browser default approach if blocked.
    // Actually, let's use a slightly longer beep for visibility (synthesized data uri is safest)

    // Init
    init: function () {
        // Run immediately on load, then every hour
        this.checkAlerts();
        setInterval(() => this.checkAlerts(), this.CHECK_INTERVAL);
        
        // Also check on page focus/visibility change to catch updates
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkAlerts();
            }
        });
        
        // Check when window regains focus
        window.addEventListener('focus', () => this.checkAlerts());

        // Inject Modal HTML if not present
        if (!document.getElementById('stockAlertModal')) {
            this.injectModal();
        }
    },

    // Check API
    checkAlerts: function () {
        // Check Snooze
        const snoozeTime = localStorage.getItem(this.STORAGE_KEY_SNOOZE);
        if (snoozeTime && new Date().getTime() < parseInt(snoozeTime)) {
            console.log('Alerts snoozed.');
            return;
        }

        fetch(this.API_URL)
            .then(response => {
                // Check if response is OK (2xx status)
                if (!response.ok) {
                    // If unauthorized (401) or redirected to login page, redirect user
                    if (response.status === 401) {
                        window.location.href = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + 'login.php';
                        return;
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Check content type to see if it's JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    // Likely redirected to login page
                    window.location.href = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + 'login.php';
                    return;
                }
                
                return response.json();
            })
            .then(data => {
                if (data && data.alert_count > 0) {
                    this.triggerAlert(data);
                }
            })
            .catch(err => {
                console.error('Alert check failed', err);
                // If there's a network error or parsing error, it might be due to session timeout
                // Redirect to login page to handle re-authentication
                if (err instanceof TypeError || err.message.includes('JSON')) {
                    window.location.href = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + 'login.php';
                }
            });
    },

    // Trigger Popup & Sound
    triggerAlert: function (data) {
        // Update Modal Content
        const list = data.items.map(item =>
            `<li class="list-group-item d-flex justify-content-between align-items-center">
                ${item.name}
                <span class="badge bg-danger rounded-pill">${item.quantity} / ${item.reorder_point}</span>
            </li>`
        ).join('');

        document.getElementById('alertItemList').innerHTML = list;
        document.getElementById('alertTotalCount').innerText = data.alert_count;

        // Show Modal
        const modalEl = document.getElementById('stockAlertModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // Play Sound
        this.playSound();
    },

    // Play Audio
    playSound: function () {
        try {
            // Using a generated oscillator for a pleasant "Ding" to avoid file issues
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                const ctx = new AudioContext();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.type = 'sine';
                osc.frequency.setValueAtTime(500, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(1000, ctx.currentTime + 0.1);

                gain.gain.setValueAtTime(0.1, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);

                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.5);
            }
        } catch (e) {
            console.warn('Audio play failed', e);
        }
    },

    // Snooze Function
    snooze: function () {
        const snoozeUntil = new Date().getTime() + this.SNOOZE_DURATION;
        localStorage.setItem(this.STORAGE_KEY_SNOOZE, snoozeUntil);

        // Hide Modal
        const modalEl = document.getElementById('stockAlertModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    },

    // Inject Modal DOM
    injectModal: function () {
        const modalHTML = `
        <div class="modal fade" id="stockAlertModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-left-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="lead">Attention! <strong><span id="alertTotalCount">0</span></strong> items are below reorder levels.</p>
                        <ul class="list-group mb-3" id="alertItemList">
                            <!-- Items injected here -->
                        </ul>
                        <div class="text-center">
                            <a href="products/reorder_suggestions.php" class="btn btn-outline-danger btn-sm">View All Reorder Items</a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="AlertSystem.snooze()">Snooze (10m)</button>
                        <a href="products/stock_in.php" class="btn btn-danger">Restock Now</a>
                    </div>
                </div>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
};

// Start
document.addEventListener('DOMContentLoaded', () => {
    // Delay slightly to let page load
    setTimeout(() => AlertSystem.init(), 1000);
});
