<?php
// includes/footer.php
?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
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