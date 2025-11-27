            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; 2025 <a href="#">Inventory Management System</a>.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0 | Powered by AdminLTE 4
        </div>
    </footer>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="/stock_management/assets/js/adminlte.min.js"></script>

<!-- Page specific scripts -->
<script>
    // Initialize tooltips
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });

    // Sidebar active state persistence
    $(function() {
        var url = window.location.pathname;
        var filename = url.substring(url.lastIndexOf('/')+1);
        
        $('.nav-sidebar a').each(function() {
            if ($(this).attr('href') && $(this).attr('href').indexOf(filename) !== -1) {
                $(this).addClass('active');
                $(this).parents('.nav-item').addClass('menu-open');
                $(this).parents('.nav-item').children('a').addClass('active');
            }
        });
    });
</script>
</body>
</html>