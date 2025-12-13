/**
 * Barcode Scanner Component
 * Reusable barcode scanner using Html5-QRCode library
 * Supports camera scanning and USB barcode scanners
 */

class BarcodeScanner {
    constructor(options = {}) {
        this.options = {
            containerId: options.containerId || 'barcode-scanner-container',
            onScanSuccess: options.onScanSuccess || this.defaultOnSuccess,
            onScanError: options.onScanError || this.defaultOnError,
            fps: options.fps || 10,
            qrbox: options.qrbox || { width: 250, height: 150 },
            supportedFormats: options.supportedFormats || [
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E
            ]
        };

        this.scanner = null;
        this.isScanning = false;
        this.lastScanTime = 0;
        this.scanCooldown = 2000; // 2 seconds between scans
    }

    /**
     * Initialize the scanner
     */
    async init() {
        try {
            this.scanner = new Html5Qrcode(this.options.containerId);
            return true;
        } catch (error) {
            console.error('Failed to initialize scanner:', error);
            return false;
        }
    }

    /**
     * Start scanning with camera
     */
    async startCamera(cameraId = null) {
        if (this.isScanning) {
            console.warn('Scanner already running');
            return;
        }

        const config = {
            fps: this.options.fps,
            qrbox: this.options.qrbox,
            formatsToSupport: this.options.supportedFormats
        };

        try {
            if (cameraId) {
                await this.scanner.start(
                    cameraId,
                    config,
                    this.handleScanSuccess.bind(this),
                    this.handleScanFailure.bind(this)
                );
            } else {
                await this.scanner.start(
                    { facingMode: "environment" }, // Use back camera on mobile
                    config,
                    this.handleScanSuccess.bind(this),
                    this.handleScanFailure.bind(this)
                );
            }

            this.isScanning = true;
            this.showStatus('Scanner ready. Point camera at barcode.', 'info');
        } catch (error) {
            console.error('Failed to start camera:', error);
            this.showStatus('Failed to start camera. Please check permissions.', 'error');
            throw error;
        }
    }

    /**
     * Stop scanning
     */
    async stop() {
        if (!this.isScanning) return;

        try {
            await this.scanner.stop();
            this.isScanning = false;
            this.showStatus('Scanner stopped', 'info');
        } catch (error) {
            console.error('Failed to stop scanner:', error);
        }
    }

    /**
     * Handle successful scan
     */
    handleScanSuccess(decodedText, decodedResult) {
        // Prevent duplicate scans
        const now = Date.now();
        if (now - this.lastScanTime < this.scanCooldown) {
            return;
        }
        this.lastScanTime = now;

        // Play success sound
        this.playBeep();

        // Show success message
        this.showStatus(`Scanned: ${decodedText}`, 'success');

        // Call user callback
        this.options.onScanSuccess(decodedText, decodedResult);
    }

    /**
     * Handle scan failure (not an error, just no barcode detected)
     */
    handleScanFailure(error) {
        // This is called frequently when no barcode is in view
        // We don't need to do anything here
    }

    /**
     * Default success handler
     */
    defaultOnSuccess(decodedText, decodedResult) {
        console.log('Barcode scanned:', decodedText);
    }

    /**
     * Default error handler
     */
    defaultOnError(error) {
        console.error('Scan error:', error);
    }

    /**
     * Get available cameras
     */
    async getCameras() {
        try {
            const devices = await Html5Qrcode.getCameras();
            return devices;
        } catch (error) {
            console.error('Failed to get cameras:', error);
            return [];
        }
    }

    /**
     * Show status message
     */
    showStatus(message, type = 'info') {
        const statusEl = document.getElementById('scanner-status');
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.className = `scanner-status scanner-status-${type}`;

            // Auto-hide after 3 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    statusEl.textContent = '';
                    statusEl.className = 'scanner-status';
                }, 3000);
            }
        }
    }

    /**
     * Play beep sound on successful scan
     */
    playBeep() {
        // Create a simple beep using Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
        } catch (error) {
            // Silently fail if audio not supported
        }
    }

    /**
     * Clean up resources
     */
    async destroy() {
        await this.stop();
        this.scanner = null;
    }
}

/**
 * Create and show barcode scanner modal
 */
function showBarcodeScannerModal(onScanCallback) {
    // Create modal HTML
    const modalHTML = `
        <div class="modal fade" id="barcodeScannerModal" tabindex="-1" aria-labelledby="barcodeScannerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="barcodeScannerModalLabel">
                            <i class="fas fa-barcode"></i> Scan Barcode
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="scanner-status" class="scanner-status mb-3"></div>
                        <div id="barcode-scanner-container" class="barcode-scanner-viewport"></div>
                        <div class="scanner-controls mt-3">
                            <select id="camera-select" class="form-select mb-2">
                                <option value="">Loading cameras...</option>
                            </select>
                            <div class="d-flex gap-2">
                                <button id="start-scan-btn" class="btn btn-success flex-fill">
                                    <i class="fas fa-play"></i> Start Scanner
                                </button>
                                <button id="stop-scan-btn" class="btn btn-danger flex-fill" style="display: none;">
                                    <i class="fas fa-stop"></i> Stop Scanner
                                </button>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3">
                            <small>
                                <i class="fas fa-info-circle"></i> 
                                <strong>Tip:</strong> Hold the barcode steady in front of the camera. 
                                The scanner will automatically detect and read it.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if present
    const existingModal = document.getElementById('barcodeScannerModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Initialize scanner
    const scanner = new BarcodeScanner({
        containerId: 'barcode-scanner-container',
        onScanSuccess: (decodedText, decodedResult) => {
            // Call user callback
            onScanCallback(decodedText, decodedResult);

            // Close modal after successful scan
            const modal = bootstrap.Modal.getInstance(document.getElementById('barcodeScannerModal'));
            if (modal) {
                modal.hide();
            }
        }
    });

    // Get modal element
    const modalEl = document.getElementById('barcodeScannerModal');
    const modal = new bootstrap.Modal(modalEl);

    // Load cameras when modal opens
    modalEl.addEventListener('shown.bs.modal', async () => {
        await scanner.init();

        const cameras = await scanner.getCameras();
        const cameraSelect = document.getElementById('camera-select');

        if (cameras && cameras.length > 0) {
            cameraSelect.innerHTML = cameras.map((camera, index) =>
                `<option value="${camera.id}">${camera.label || `Camera ${index + 1}`}</option>`
            ).join('');
        } else {
            cameraSelect.innerHTML = '<option value="">No cameras found</option>';
        }
    });

    // Clean up when modal closes
    modalEl.addEventListener('hidden.bs.modal', async () => {
        await scanner.destroy();
        modalEl.remove();
    });

    // Start button handler
    document.getElementById('start-scan-btn').addEventListener('click', async () => {
        const cameraId = document.getElementById('camera-select').value;
        try {
            await scanner.startCamera(cameraId);
            document.getElementById('start-scan-btn').style.display = 'none';
            document.getElementById('stop-scan-btn').style.display = 'block';
        } catch (error) {
            alert('Failed to start camera. Please check permissions.');
        }
    });

    // Stop button handler
    document.getElementById('stop-scan-btn').addEventListener('click', async () => {
        await scanner.stop();
        document.getElementById('start-scan-btn').style.display = 'block';
        document.getElementById('stop-scan-btn').style.display = 'none';
    });

    // Show modal
    modal.show();
}

// Keyboard shortcut to open scanner (Ctrl+B or Cmd+B)
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
        e.preventDefault();
        const scanBtn = document.getElementById('barcode-scan-btn');
        if (scanBtn) {
            scanBtn.click();
        }
    }
});
