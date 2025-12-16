// Single‑source sidebar controller (NO SB‑ADMIN JS REQUIRED)
// Controls: desktop collapse, mobile overlay, resize stability

(function ($) {
    "use strict";

    const BREAKPOINT_MOBILE = 768;
    const STORAGE_KEY = "ims_sidebar"; // collapsed | expanded

    const $body = $("body");
    const $sidebar = $(".sidebar");
    const $toggle = $("#sidebarToggle, #sidebarToggleTop");
    const $window = $(window);

    function isMobile() {
        return $window.width() < BREAKPOINT_MOBILE;
    }

    function expand() {
        $body.removeClass("sidebar-toggled");
        $sidebar.removeClass("toggled");
        localStorage.setItem(STORAGE_KEY, "expanded");
    }

    function collapse() {
        $body.addClass("sidebar-toggled");
        $sidebar.addClass("toggled");
        $('.sidebar .collapse').collapse('hide');
        localStorage.setItem(STORAGE_KEY, "collapsed");
    }

    function hideMobile() {
        $body.removeClass("sidebar-toggled");
        $sidebar.removeClass("toggled");
        $('.sidebar .collapse').collapse('hide');
    }

    function toggle(e) {
        if (e) e.preventDefault();

        if (isMobile()) {
            $body.toggleClass("sidebar-toggled");
            $sidebar.toggleClass("toggled");
            return;
        }

        if ($body.hasClass("sidebar-toggled")) {
            expand();
        } else {
            collapse();
        }
    }

    function applyInitialState() {
        if (isMobile()) {
            hideMobile();
            return;
        }

        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved === "collapsed") {
            collapse();
        } else {
            expand();
        }
    }

    function handleResize() {
        if (isMobile()) {
            hideMobile();
        } else {
            applyInitialState();
        }
    }

    function closeOnOverlayClick(e) {
        if (!isMobile()) return;

        const $target = $(e.target);
        const insideSidebar = $target.closest('.sidebar').length;
        const toggleBtn = $target.closest('#sidebarToggle, #sidebarToggleTop').length;

        if (!insideSidebar && !toggleBtn && $body.hasClass("sidebar-toggled")) {
            hideMobile();
        }
    }

    $(document).ready(function () {
        applyInitialState();
        $toggle.on('click', toggle);
        $window.on('resize', handleResize);
        $(document).on('click', closeOnOverlayClick);
    });

})(jQuery);