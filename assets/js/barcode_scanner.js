/**
 * Enhanced Barcode Scanner
 * Uses html5-qrcode library with improved features
 */

const BarcodeScanner = {
    html5QrcodeScanner: null,
    isScanning: false,
    currentCameraId: null,
    lastScannedCode: null,
    config: {
        fps: 25,
        qrbox: (viewfinderWidth, viewfinderHeight) => {
            // High-performance dynamic scan box
            const width = viewfinderWidth * 0.8;
            const height = viewfinderHeight * 0.45;
            return {
                width: width < 250 ? 250 : width,
                height: height < 150 ? 150 : height
            };
        },
        aspectRatio: 1.0,
        disableFlip: false,
        rememberLastUsedCamera: true,
        showTorchButtonIfSupported: true,
        showZoomSliderIfSupported: true,
        experimentalFeatures: {
            useBarCodeDetectorIfSupported: true
        }
    },

    // Initialize and bind events
    init: function () {
        console.log('Enhanced Barcode Scanner Initialized');
        this.bindEvents();
        this.loadUserPreferences();
    },

    bindEvents: function () {
        // Global listener for opening scanner
        document.addEventListener('click', (e) => {
            if (e.target.closest('.start-barcode-scanner')) {
                e.preventDefault();
                this.openModal();
            }
        });

        // Modal events
        const modalEl = document.getElementById('barcodeScannerModal');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', () => {
                this.stopScanner();
            });

            modalEl.addEventListener('shown.bs.modal', () => {
                this.updateStatus('Ready to scan', '');
                document.getElementById('last_scanned').style.display = 'none';
            });
        }

        // File input change with preview
        const fileInput = document.getElementById('scanner_image_file');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const preview = document.getElementById('preview_image');
                        preview.src = e.target.result;
                        preview.style.display = 'block';

                        // Auto-scan after preview
                        setTimeout(() => {
                            this.handleFileUpload(file);
                        }, 500);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Camera Start Button
        const startBtn = document.getElementById('btn_start_camera');
        if (startBtn) {
            startBtn.addEventListener('click', () => {
                this.startCamera();
            });
        }

        // Preferences
        document.getElementById('scanner_auto_close')?.addEventListener('change', (e) => {
            this.savePreference('autoClose', e.target.checked);
        });

        document.getElementById('scanner_beep')?.addEventListener('change', (e) => {
            this.savePreference('playBeep', e.target.checked);
        });
    },

    loadUserPreferences: function () {
        const autoClose = localStorage.getItem('barcodeScanner_autoClose');
        const playBeep = localStorage.getItem('barcodeScanner_playBeep');

        if (autoClose !== null) {
            document.getElementById('scanner_auto_close').checked = autoClose === 'true';
        }
        if (playBeep !== null) {
            document.getElementById('scanner_beep').checked = playBeep === 'true';
        }
    },

    savePreference: function (key, value) {
        localStorage.setItem(`barcodeScanner_${key}`, value);
    },

    getPreference: function (key, defaultValue) {
        const value = localStorage.getItem(`barcodeScanner_${key}`);
        return value !== null ? value === 'true' : defaultValue;
    },

    openModal: function () {
        const modal = new bootstrap.Modal(document.getElementById('barcodeScannerModal'));
        modal.show();

        // Reset UI
        this.updateStatus('Ready to scan', '');
        document.getElementById('scanner_video_container').style.display = 'block';
        document.getElementById('scanner_upload_container').style.display = 'none';
        document.getElementById('btn_start_camera').style.display = 'inline-block';
        document.getElementById('btn_toggle_upload').style.display = 'inline-block';
        document.getElementById('btn_stop_camera').style.display = 'none';

        // Clear preview
        const preview = document.getElementById('preview_image');
        preview.src = '';
        preview.style.display = 'none';

        // Clear file input
        const fileInput = document.getElementById('scanner_image_file');
        if (fileInput) fileInput.value = '';
    },

    startCamera: async function () {
        if (this.isScanning) return;

        const readerId = "reader";

        try {
            if (typeof Html5Qrcode === 'undefined') {
                throw new Error("Scanner library not loaded. Please refresh.");
            }

            // Define formats
            const formats = [
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.CODE_93,
                Html5QrcodeSupportedFormats.QR_CODE
            ];

            this.config.formatsToSupport = formats;
            this.html5QrcodeScanner = new Html5Qrcode(readerId, {
                formatsToSupport: formats,
                verbose: false
            });

            const cameras = await Html5Qrcode.getCameras();

            if (cameras && cameras.length) {
                // Try to get last used camera
                let cameraId = this.getLastCameraId();

                if (!cameraId) {
                    // Prefer back camera
                    cameraId = cameras[0].id;
                    const backCamera = cameras.find(cam =>
                        cam.label.toLowerCase().includes('back') ||
                        cam.label.toLowerCase().includes('rear') ||
                        cam.label.toLowerCase().includes('environment')
                    );
                    if (backCamera) {
                        cameraId = backCamera.id;
                    }
                }

                this.currentCameraId = cameraId;
                this.updateStatus('Starting camera...', 'searching');

                await this.html5QrcodeScanner.start(
                    cameraId,
                    this.config,
                    (decodedText, decodedResult) => {
                        console.log("Code found:", decodedText, decodedResult);
                        this.handleScanSuccess(decodedText, decodedResult);
                    },
                    (errorMessage) => {
                        // Very verbose, only log if not "no code"
                        if (!errorMessage.includes('No QR code') && !errorMessage.includes('NotFoundException')) {
                            console.debug('Scanner hint:', errorMessage);
                        }
                    }
                );

                this.isScanning = true;
                this.updateStatus('Scanning... Point at barcode', 'searching');

                // Update UI
                document.getElementById('btn_start_camera').style.display = 'none';
                document.getElementById('btn_toggle_upload').style.display = 'none';
                document.getElementById('btn_stop_camera').style.display = 'inline-block';

            } else {
                this.updateStatus('No cameras found', 'error');
                this.showNoCameraMessage();
            }
        } catch (err) {
            console.error('Camera start error:', err);
            this.updateStatus('Error: ' + err.message, 'error');
        }
    },

    getLastCameraId: function () {
        return localStorage.getItem('barcodeScanner_lastCameraId');
    },

    saveLastCameraId: function (cameraId) {
        localStorage.setItem('barcodeScanner_lastCameraId', cameraId);
    },

    showNoCameraMessage: function () {
        this.updateStatus(`
            <div class="text-center">
                <i class="fas fa-video-slash fa-2x mb-2"></i><br>
                No camera detected.<br>
                <small class="text-muted">Try uploading an image instead.</small>
            </div>
        `, 'error');
    },

    stopScanner: async function () {
        if (this.html5QrcodeScanner && this.isScanning) {
            try {
                await this.html5QrcodeScanner.stop();
                this.html5QrcodeScanner.clear();
            } catch (ignore) {
                // Ignore stop errors
            }
            this.isScanning = false;
        }

        // Update UI
        document.getElementById('btn_start_camera').style.display = 'inline-block';
        document.getElementById('btn_toggle_upload').style.display = 'inline-block';
        document.getElementById('btn_stop_camera').style.display = 'none';
        this.updateStatus('Scanner stopped', '');
    },

    toggleUpload: function () {
        const videoContainer = document.getElementById('scanner_video_container');
        const uploadContainer = document.getElementById('scanner_upload_container');

        if (videoContainer.style.display === 'block') {
            videoContainer.style.display = 'none';
            uploadContainer.style.display = 'block';
            document.getElementById('btn_toggle_upload').innerHTML = '<i class="fas fa-camera"></i> Use Camera';
        } else {
            videoContainer.style.display = 'block';
            uploadContainer.style.display = 'none';
            document.getElementById('btn_toggle_upload').innerHTML = '<i class="fas fa-upload"></i> Upload Image';
        }
    },

    handleFileUpload: async function (file) {
        if (!file) return;

        try {
            if (typeof Html5Qrcode === 'undefined') {
                throw new Error("Scanner library not loaded.");
            }

            this.updateStatus('Processing image...', 'searching');

            const tempScanner = new Html5Qrcode("reader");
            const result = await tempScanner.scanFile(file, true);

            this.handleScanSuccess(result);

        } catch (err) {
            console.error('File scan error:', err);
            this.updateStatus('No barcode found in image', 'error');
        }
    },

    handleScanSuccess: function (decodedText, decodedResult = null) {
        console.log("Scan success: " + decodedText);
        this.lastScannedCode = decodedText;

        // Show last scanned
        document.getElementById('last_scanned').style.display = 'block';
        document.getElementById('last_scanned_value').textContent = decodedText;

        // Play beep if enabled
        if (this.getPreference('playBeep', true)) {
            this.playBeep();
        }

        this.updateStatus(`Scanned: ${decodedText}... Searching product`, 'success');

        // Check if we need to redirect to update stock
        // We do this by querying the search endpoint first to get the ID
        fetch(`../products/search_by_barcode.php?barcode=${encodeURIComponent(decodedText)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const product = data.data.product;
                    const variant = data.data.variant;

                    let redirectUrl = `../products/update_stock.php?id=${product.id}`;

                    if (data.data.type === 'variant' && variant) {
                        redirectUrl += `&variant_id=${variant.id}`;
                    }

                    this.updateStatus('Product found! Redirecting...', 'success');

                    // Small delay to let the user see the success message
                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 500);

                } else {
                    // Fallback to populating search fields if not found or other error
                    this.updateStatus(`Product not found via API. Populating search...`, 'warning');
                    this.populateSearchFields(decodedText);
                }
            })
            .catch(err => {
                console.error('Search error:', err);
                // Fallback on error
                this.populateSearchFields(decodedText);
            });

        // Save scan history
        this.saveToHistory(decodedText, decodedResult?.result?.format?.formatName);

        // Auto-close if enabled
        if (this.getPreference('autoClose', true)) {
            setTimeout(() => {
                this.autoCloseModal();
            }, 1000);
        }
    },

    populateSearchFields: function (decodedText) {
        // Try different selectors
        const selectors = [
            'input[name="search_barcode"]',
            'input[name="barcode"]',
            '#barcode_search',
            '#barcode',
            '.barcode-input',
            'input[type="text"][placeholder*="barcode" i]',
            'input[type="text"][placeholder*="scan" i]',
            'input[type="text"][id*="barcode" i]',
            'input[type="text"][name*="barcode" i]'
        ];

        let populated = false;

        for (const selector of selectors) {
            const inputs = document.querySelectorAll(selector);
            inputs.forEach(input => {
                if (input && !input.disabled && input.type === 'text') {
                    input.value = decodedText;
                    input.focus();

                    // Trigger events
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));

                    // Trigger form submission if it's a search form
                    const form = input.closest('form');
                    if (form && (form.id.includes('search') ||
                        form.className.includes('search') ||
                        form.action.includes('search'))) {
                        setTimeout(() => {
                            const submitBtn = form.querySelector('[type="submit"]');
                            if (submitBtn) {
                                submitBtn.click();
                            }
                        }, 500);
                    }

                    populated = true;
                }
            });
        }

        // Product-specific handling
        const productId = document.querySelector('[data-product-id]')?.getAttribute('data-product-id');
        if (productId) {
            this.updateProductBarcode(productId, decodedText);
        }

        // Global callback
        if (typeof window.onBarcodeScanned === 'function') {
            window.onBarcodeScanned(decodedText);
        }

        // Dispatch custom event
        const event = new CustomEvent('barcode-scanned', {
            detail: {
                code: decodedText,
                timestamp: new Date().toISOString()
            }
        });
        document.dispatchEvent(event);
    },

    updateProductBarcode: function (productId, barcode) {
        // AJAX implementation to update product barcode
        if (typeof fetch === 'function') {
            fetch('../products/update_barcode.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${productId}&barcode=${encodeURIComponent(barcode)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.updateStatus('Barcode saved to product!', 'success');
                    }
                })
                .catch(error => {
                    console.error('Update error:', error);
                });
        }
    },

    autoCloseModal: function () {
        const modalEl = document.getElementById('barcodeScannerModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                this.stopScanner();
                modal.hide();
            }
        }
    },

    updateStatus: function (msg, type) {
        const el = document.getElementById('scanner_status_text');
        if (el) {
            el.innerHTML = msg;
            el.className = 'scanner-status status-' + type;

            // Update status card border
            const statusCard = document.getElementById('scanner_status');
            statusCard.className = 'card border-' + (type || 'secondary');
        }
    },

    saveToHistory: function (code, format = 'Unknown') {
        try {
            const history = JSON.parse(localStorage.getItem('barcodeScanHistory') || '[]');
            history.unshift({
                code: code,
                format: format,
                timestamp: new Date().toISOString(),
                url: window.location.href
            });

            // Keep only last 50 scans
            if (history.length > 50) {
                history.pop();
            }

            localStorage.setItem('barcodeScanHistory', JSON.stringify(history));
        } catch (e) {
            console.error('Failed to save scan history:', e);
        }
    },

    playBeep: function () {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
        } catch (e) {
            console.warn('Audio context not supported');
            // Fallback: Create audio element
            const audio = new Audio('data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEAQB8AAEAfAAABAAgAZGF0YQ');
            audio.volume = 0.1;
            audio.play().catch(() => { });
        }
    }
};

// Toggle function for upload/camera
function toggleUpload() {
    BarcodeScanner.toggleUpload();
}

// Auto-init
document.addEventListener('DOMContentLoaded', () => {
    BarcodeScanner.init();
});