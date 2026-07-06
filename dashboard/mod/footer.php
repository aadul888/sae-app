<?php
if (empty($connection)) {
    header('location:../');
    exit();
} else {
    $mod = htmlspecialchars($_GET['mod'] ?? 'home');
    $appSiteName = trim((string)($site_name ?? ''));
    if ($appSiteName === '') {
        $appSiteName = defined('SAE_APP_NAME') ? SAE_APP_NAME : 'Smart Apps Education';
    }
    $appVersion = defined('SAE_VERSION') ? SAE_VERSION : 'v5.0';

    // JavaScript Libraries - Load in proper order
    echo '
    <!-- Core Bootstrap & jQuery Extensions -->
    <script src="../admin/assets/vendor/js-cookie/js.cookie.js"></script>
    <script src="../admin/assets/vendor/jquery.scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../admin/assets/vendor/jquery-scroll-lock/dist/jquery-scrollLock.min.js"></script>
    
    <!-- Form & Data Components -->
    <script src="../admin/assets/vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="../admin/assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="../admin/assets/vendor/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/js/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- UI & Theme -->
    <script src="assets/js/sweetalert.min.js"></script>
    <script src="assets/js/demo.min.js"></script>
    <script src="assets/js/argon.js"></script>';

    // Check if module-specific script exists and load it
    $mod_script_file = __DIR__ . '/' . $mod . '/scripts.js';
    if (file_exists($mod_script_file)) {
        echo '
    <!-- Module-specific Script for: ' . $mod . ' -->
    <script src="mod/' . $mod . '/scripts.js?v=' . time() . '" type="text/javascript"></script>';
    } else {
        // Load home scripts as fallback
        echo '
    <!-- Fallback to Home Scripts -->
    <script src="mod/home/scripts.js?v=' . time() . '" type="text/javascript"></script>';
    }

    // Enhanced footer navigation
    echo '
    <!-- Ultra Modern Footer Navigation -->
    <div class="footer-nav" title="' . htmlspecialchars($appSiteName, ENT_QUOTES) . ' ' . htmlspecialchars($appVersion, ENT_QUOTES) . '">
        <!-- Home -->
        <div class="footer-item">
            <a href="home" class="footer-icon' . ($mod == 'home' ? ' footer-active' : '') . '" title="Beranda">
                <i class="fas fa-home"></i>
            </a>
        </div>
        <!-- Information -->
        <div class="footer-item">
            <a href="informasi" class="footer-icon' . ($mod == 'informasi' ? ' footer-active' : '') . '" title="Informasi">
                <i class="fas fa-info-circle"></i>
            </a>
        </div>
        <!-- File Upload -->
        <div class="footer-item">
            <a href="berkas" class="footer-icon' . ($mod == 'berkas' ? ' footer-active' : '') . '" title="Upload Berkas">
                <i class="fas fa-cloud-upload-alt"></i>
            </a>
        </div>
        <!-- Reports -->
        <div class="footer-item">
            <a href="laporan" class="footer-icon' . ($mod == 'laporan' ? ' footer-active' : '') . '" title="Laporan">
                <i class="fas fa-chart-bar"></i>
            </a>
        </div>
        <!-- FAQ -->
        <div class="footer-item">
            <a href="faq" class="footer-icon' . ($mod == 'faq' ? ' footer-active' : '') . '" title="FAQ">
                <i class="fas fa-question-circle"></i>
            </a>
        </div>
    </div>
    
    <!-- Back to Top Button -->
    <div id="backToTop" class="dashboard-backtotop">
        <button class="btn btn-primary btn-sm rounded-circle" onclick="window.scrollTo({top: 0, behavior: \'smooth\'});">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>
    
    <!-- Global JavaScript Handlers -->
    <script type="text/javascript">
    $(document).ready(function() {
        // Hide page loader when all content is ready
        setTimeout(function() {
            $("#pageLoader").addClass("hide");
            $(".main-content").addClass("loaded");
        }, 300);
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $(".alert").fadeOut("slow");
        }, 5000);
        
        // Enhanced form validation feedback
        $("form").on("submit", function(e) {
            var form = $(this);
            var submitBtn = form.find(".btn[type=\"submit\"]");
            
            // Skip form validation for berkas upload form
            if(form.hasClass("form-upload")) {
                return true;
            }
            
            // Prevent multiple submissions for other forms
            if(submitBtn.prop("disabled")) {
                e.preventDefault();
                return false;
            }
            
            submitBtn.prop("disabled", true)
                     .html("<i class=\"fas fa-spinner fa-spin\"></i> Loading...");
        });
        
        // Back to top button functionality
        $(window).scroll(function() {
            if ($(this).scrollTop() > 100) {
                $("#backToTop").fadeIn();
            } else {
                $("#backToTop").fadeOut();
            }
        });
        
        // Enhanced dropdown interactions
        $(".dropdown-toggle").on("show.bs.dropdown", function() {
            $(this).find(".avatar img").css("transform", "scale(1.1)");
        });
        
        $(".dropdown-toggle").on("hide.bs.dropdown", function() {
            $(this).find(".avatar img").css("transform", "scale(1)");
        });
        
        // Global error handler for scripts
        window.addEventListener("error", function(e) {
            if (e.target && e.target.tagName === "SCRIPT") {
                console.error("Script loading error:", e.target.src);
            }
        });
    });
    </script>
    
</body>
</html>';
}
