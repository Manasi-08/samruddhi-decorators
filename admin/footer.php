    </div> <!-- End .content-wrapper -->

    <!-- Admin Footer -->
    <footer class="admin-footer">
        <div>
            &copy; <?php echo date('Y'); ?> <strong>Samruddhi Decorators</strong>. All rights reserved.
        </div>
        <div>
            Designed with <i class="fa fa-heart text-danger"></i> for Event Excellence
        </div>
    </footer>
</div> <!-- End #main-container -->
</div> <!-- End #admin-wrapper -->

<!-- Bootstrap 5 Bundle JS (Includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="assets/js/jquery.dataTables.js"></script>

<!-- Custom Admin Scripts -->
<script>
$(document).ready(function() {
    // Sidebar Toggle
    $('#sidebarToggle').on('click', function() {
        $('body').toggleClass('sidebar-collapsed');
        if ($(window).width() < 992) {
            $('body').toggleClass('sidebar-mobile-open');
        }
    });

    // Submenu Toggle
    $('.sidebar-dropdown-toggle').on('click', function(e) {
        e.preventDefault();
        var $parent = $(this).parent('.nav-item');
        $parent.toggleClass('open');
    });

    // Mobile Overlay Click Close Sidebar
    $(document).on('click', function(e) {
        if ($(window).width() < 992) {
            if (!$(e.target).closest('#sidebar, #sidebarToggle').length) {
                $('body').removeClass('sidebar-mobile-open');
            }
        }
    });
});
</script>
</body>
</html>
