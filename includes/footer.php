                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Inventory Management System 2025</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    
    <!-- Page level plugins -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Barcode Scanner Logic -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8" type="text/javascript"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/barcode_scanner.js?v=<?php echo time(); ?>"></script>

    <!-- Barcode Scanner Modal -->
    <div class="modal fade" id="barcodeScannerModal" tabindex="-1" aria-labelledby="barcodeScannerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="barcodeScannerModalLabel">
                        <i class="fas fa-barcode me-2"></i>Scan Barcode
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Video Container -->
                            <div id="scanner_video_container" style="display: block;">
                                <div id="reader" class="border rounded bg-dark" style="width: 100%; min-height: 300px; position: relative;">
                                    <div class="scanner-overlay">
                                        <div class="scanner-line"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Upload Container -->
                            <div id="scanner_upload_container" style="display: none;">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-image fa-4x text-primary mb-3"></i>
                                        <h5>Upload Barcode Image</h5>
                                        <p class="text-muted">Upload a clear image containing a barcode</p>
                                        <input type="file" id="scanner_image_file" accept="image/*" class="form-control">
                                        <div class="mt-3">
                                            <img id="preview_image" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px; display: none;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="d-grid gap-2 mb-3">
                                <button id="btn_start_camera" class="btn btn-primary">
                                    <i class="fas fa-camera"></i> Start Camera
                                </button>
                                <button id="btn_toggle_upload" class="btn btn-outline-secondary" onclick="toggleUpload()">
                                    <i class="fas fa-upload"></i> Upload Image
                                </button>
                                <button id="btn_stop_camera" class="btn btn-warning" style="display: none;" onclick="BarcodeScanner.stopScanner()">
                                    <i class="fas fa-stop"></i> Stop Camera
                                </button>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6><i class="fas fa-info-circle me-2"></i>Supported Formats:</h6>
                                    <div class="row small">
                                        <div class="col-6">
                                            <span class="badge bg-primary mb-1">UPC-A</span><br>
                                            <span class="badge bg-primary mb-1">EAN-13</span><br>
                                            <span class="badge bg-primary mb-1">CODE 128</span>
                                        </div>
                                        <div class="col-6">
                                            <span class="badge bg-primary mb-1">QR CODE</span><br>
                                            <span class="badge bg-primary mb-1">CODE 39</span><br>
                                            <span class="badge bg-primary mb-1">UPC-E</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="scanner_status" class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Status</h6>
                                    <div id="scanner_status_text" class="scanner-status">Ready</div>
                                    <div id="last_scanned" class="mt-2" style="display: none;">
                                        <small class="text-muted">Last scanned:</small>
                                        <div id="last_scanned_value" class="font-monospace"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="scanner_auto_close" checked>
                                    <label class="form-check-label" for="scanner_auto_close">
                                        Auto close after scan
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="scanner_beep" checked>
                                    <label class="form-check-label" for="scanner_beep">
                                        Play sound on scan
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarToggleTop = document.getElementById('sidebarToggleTop');
            const sidebar = document.querySelector('.sidebar');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('toggled');
                });
            }
            
            if (sidebarToggleTop) {
                sidebarToggleTop.addEventListener('click', function() {
                    sidebar.classList.toggle('toggled');
                });
            }
            
            // Scroll to top button
            const scrollToTopButton = document.querySelector('.scroll-to-top');
            if (scrollToTopButton) {
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 100) {
                        scrollToTopButton.style.display = 'block';
                    } else {
                        scrollToTopButton.style.display = 'none';
                    }
                });
                
                scrollToTopButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        });

        // Auto-dismiss alerts
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert:not(#bulkActionsToolbar)');
                alerts.forEach(alert => {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    } else {
                        alert.style.display = 'none';
                    }
                });
            }, 5000);
        });

        // Confirm delete
        function confirmDelete(productName) {
            return confirm('Are you sure you want to delete "' + productName + '"? This action cannot be undone.');
        }

        // Confirm stock out
        function confirmStockOut(productName, quantity) {
            return confirm('Are you sure you want to remove ' + quantity + ' units of "' + productName + '" from stock?');
        }
    </script>
</body>
</html>