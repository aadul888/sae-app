
(function($) {
    'use strict'

    $(function() {
        $('[data-toggle="sweet-alert"]').on('click', function(){
            var type = $(this).data('sweet-alert');

            switch (type) {
                case 'basic':
                    swal({
                        title: "Here's a message!",
                        text: 'A few words about this sweet alert ...',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-primary'
                    })
                break;

                case 'info':
                    swal({
                        title: 'Info',
                        text: 'A few words about this sweet alert ...',
                        type: 'info',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-info'
                    })
                break;

                case 'info':
                    swal({
                        title: 'Info',
                        text: 'A few words about this sweet alert ...',
                        type: 'info',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-info'
                    })
                break;

                case 'success':
                    swal({
                        title: 'Success',
                        text: 'A few words about this sweet alert ...',
                        type: 'success',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-success'
                    })
                break;

                case 'warning':
                    swal({
                        title: 'Warning',
                        text: 'A few words about this sweet alert ...',
                        type: 'warning',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-warning'
                    })
                break;

                case 'question':
                    swal({
                        title: 'Are you sure?',
                        text: 'A few words about this sweet alert ...',
                        type: 'question',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-default'
                    })
                break;

                case 'confirm':
                    swal({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        type: 'warning',
                        showCancelButton: true,
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-danger',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonClass: 'btn btn-secondary'
                    }).then((result) => {
                        if (result.value) {
                            // Show confirmation
                            swal({
                                title: 'Deleted!',
                                text: 'Your file has been deleted.',
                                type: 'success',
                                buttonsStyling: false,
                                confirmButtonClass: 'btn btn-primary'
                            });
                        }
                    })
                break;

                case 'image':
                    swal({
                        title: 'Sweet',
                        text: "Modal with a custom image ...",
                        imageUrl: '../../assets/img/ill/ill-1.svg',
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-primary',
                        confirmButtonText: 'Super!'
                });
                break;

                case 'timer':
                    swal({
                        title: 'Auto close alert!',
                        text: 'I will close in 2 seconds.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                break;
            }
        });

    });
}(jQuery));

$(document).on('click', '.btn-error', function(){ 
    swal({title: 'Failed!', text: 'Sorry Anda tidak memiliki hak akses.!', icon: 'error', timer: 1500,});
});


var path = window.location.href; 
// because the 'href' property of the DOM element is the absolute path
$('ul a').each(function() {
if (this.href === path) {
    $(this).addClass('active');
    //collapse show
}
});

$('.mobile-footer-link').each(function () {
    if (this.href === path) {
        $(this).addClass('active');
    }
});

(function ($) {
    'use strict';

    var dashboardRefreshTimer = null;
    var statSliderTimer = null;
    var SIDENAV_BACKDROP_SELECTOR = '.sidenav-backdrop';

    function initMobileSidebarToggle() {
        if (!$('#sidenav-main').length) {
            return;
        }

        function updateToggleIcon(isOpen) {
            var $toggle = $('.admin-topbar-toggle');
            if (!$toggle.length) {
                return;
            }

            $toggle.toggleClass('is-open', !!isOpen);
            $toggle.attr('aria-expanded', isOpen ? 'true' : 'false');
        }

        function unpinSidenav() {
            $('body')
                .removeClass('g-sidenav-pinned')
                .removeClass('g-sidenav-show')
                .addClass('g-sidenav-hidden');

            $(SIDENAV_BACKDROP_SELECTOR).remove();
            updateToggleIcon(false);
        }

        function pinSidenav() {
            var $body = $('body');
            var $sidenav = $('#sidenav-main');

            $body
                .removeClass('g-sidenav-hidden')
                .addClass('g-sidenav-show g-sidenav-pinned');

            if ($(SIDENAV_BACKDROP_SELECTOR).length === 0) {
                var backdrop = $('<div>', {
                    class: 'backdrop sidenav-backdrop d-xl-none',
                    'data-action': 'sidenav-unpin',
                    'data-target': $sidenav.data('target') || '#sidenav-main'
                });
                $body.append(backdrop);
                backdrop.on('click', unpinSidenav);
            }

            updateToggleIcon(true);
        }

        function toggleSidebar() {
            var $body = $('body');
            if ($body.hasClass('g-sidenav-pinned') || $body.hasClass('g-sidenav-show')) {
                unpinSidenav();
            } else {
                pinSidenav();
            }
        }

        function ensureMobileToggle() {
            var $toggleBtn = $('.admin-topbar-toggle');
            if (!$toggleBtn.length) {
                return;
            }

            if ($(window).width() < 1200) {
                unpinSidenav();
            }

            $toggleBtn.off('click.mobileSidebar').on('click.mobileSidebar', function (e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });

            updateToggleIcon(
                $('body').hasClass('g-sidenav-show') || $('body').hasClass('g-sidenav-pinned')
            );
        }

        ensureMobileToggle();

        // Close sidebar when clicking anywhere outside the sidenav on mobile
        $(document).off('click.sidenavOutside').on('click.sidenavOutside', function (e) {
            if ($(window).width() >= 1200) return;
            var $body = $('body');
            if (!$body.hasClass('g-sidenav-show') && !$body.hasClass('g-sidenav-pinned')) return;
            if ($(e.target).closest('#sidenav-main').length) return;
            if ($(e.target).closest('.admin-topbar-toggle').length) return;
            unpinSidenav();
        });

        $(window).off('resize.mobileSidebar').on('resize.mobileSidebar', function () {
            updateToggleIcon(
                $('body').hasClass('g-sidenav-show') || $('body').hasClass('g-sidenav-pinned')
            );
        });

        $('body').off('click.mobileSidebarSync').on(
            'click.mobileSidebarSync',
            '[data-action="sidenav-pin"], [data-action="sidenav-unpin"]',
            function () {
                setTimeout(function () {
                    updateToggleIcon(
                        $('body').hasClass('g-sidenav-show') || $('body').hasClass('g-sidenav-pinned')
                    );
                }, 100);
            }
        );

        $(document).off('show.bs.modal.mobileSidebar hidden.bs.modal.mobileSidebar').on(
            'show.bs.modal.mobileSidebar hidden.bs.modal.mobileSidebar',
            '.modal',
            function () {
                $(SIDENAV_BACKDROP_SELECTOR).remove();
                if ($(window).width() < 1200) {
                    $('body')
                        .removeClass('g-sidenav-pinned g-sidenav-show')
                        .addClass('g-sidenav-hidden');
                    updateToggleIcon(false);
                }
            }
        );
    }

    function initDashboardStatSlider() {
        var $slider = $('.dashboard-stats-slider');
        if (!$slider.length) {
            return;
        }

        function destroyTimer() {
            if (statSliderTimer) {
                clearInterval(statSliderTimer);
                statSliderTimer = null;
            }
        }

        function activateSlider() {
            var $cards = $slider.find('.dashboard-stat-col');
            if ($cards.length < 2) {
                destroyTimer();
                $slider.scrollLeft(0);
                return;
            }

            // Desktop (≥1200 px): show all 4 cards, no auto-slide
            if ($(window).width() >= 1200) {
                destroyTimer();
                $slider.scrollLeft(0);
                return;
            }

            var currentIndex = 0;
            destroyTimer();

            statSliderTimer = setInterval(function () {
                currentIndex = (currentIndex + 1) % $cards.length;
                var targetNode = $cards.get(currentIndex);
                if (!targetNode) {
                    return;
                }

                $slider.stop().animate({
                    scrollLeft: targetNode.offsetLeft - $slider.get(0).offsetLeft
                }, 380);
            }, 3200);
        }

        $slider.off('touchstart.dashboardSlider mouseenter.dashboardSlider').on('touchstart.dashboardSlider mouseenter.dashboardSlider', function () {
            destroyTimer();
        });

        $slider.off('touchend.dashboardSlider mouseleave.dashboardSlider').on('touchend.dashboardSlider mouseleave.dashboardSlider', function () {
            activateSlider();
        });

        activateSlider();
        $(window).off('resize.dashboardSlider').on('resize.dashboardSlider', activateSlider);
    }

    function initDashboardHome() {
        var $loadTable = $('.load-table');
        var hasDashboardShell = $('.dashboard-stats-slider').length || $loadTable.length;
        if (!hasDashboardShell) {
            return;
        }

        initDashboardStatSlider();

        if (!$loadTable.length) {
            return;
        }

        function setLoadingState(isLoading) {
            $loadTable.toggleClass('is-loading', !!isLoading);
        }

        function syncDashboardLayout() {
            try {
                var statsCard = $loadTable.closest('.card');
                if (statsCard.length) {
                    statsCard.removeClass('hide-statistics-card').css('display', '');
                }

                $('.info-card, .load-table .card').each(function () {
                    $(this).removeClass('hide-latest-stats').css('display', '');
                });
            } catch (e) {
                if (window.console && console.debug) {
                    console.debug('syncDashboardLayout error', e);
                }
            }
        }

        function enhanceTable() {
            $('.table tr').off('mouseenter.dashboardHome mouseleave.dashboardHome')
                .on('mouseenter.dashboardHome', function () {
                    $(this).addClass('bg-light');
                })
                .on('mouseleave.dashboardHome', function () {
                    $(this).removeClass('bg-light');
                });

            syncDashboardLayout();
        }

        function loadTable() {
            setLoadingState(true);
            $loadTable.load('./mod/home/proses.php?action=table', function (response, status) {
                setLoadingState(false);

                if (status === 'error') {
                    $loadTable.html('\
        <div class="alert alert-info">\
          <i class="fas fa-info-circle mr-2"></i>\
          Sedang memuat data...\
        </div>\
        <div class="row">\
          <div class="col-md-12">\
            <div class="card">\
              <div class="card-body text-center">\
                <h5>Dashboard Statistik</h5>\
                <p class="text-muted">Data akan dimuat secara berkala</p>\
              </div>\
            </div>\
          </div>\
        </div>');
                } else {
                    enhanceTable();
                }

                syncDashboardLayout();
            });
        }

        function addStatCardHandlers() {
            $('.card-stats').off('mouseenter.dashboardCard mouseleave.dashboardCard')
                .on('mouseenter.dashboardCard', function () {
                    $(this).css('transform', 'translateY(-2px)').addClass('shadow-lg');
                })
                .on('mouseleave.dashboardCard', function () {
                    $(this).css('transform', 'translateY(0)').removeClass('shadow-lg');
                });
        }

        setLoadingState(true);
        $loadTable.html('\
      <div class="text-center py-4">\
        <div class="spinner-border text-primary" role="status">\
          <span class="sr-only">Loading...</span>\
        </div>\
        <p class="mt-3 text-muted">Memuat data...</p>\
      </div>');

        addStatCardHandlers();
        loadTable();

        if (dashboardRefreshTimer) {
            clearTimeout(dashboardRefreshTimer);
        }

        (function scheduleRefresh() {
            dashboardRefreshTimer = setTimeout(function () {
                loadTable();
                scheduleRefresh();
            }, 5000);
        }());

        if (window.MutationObserver && $loadTable.get(0)) {
            var observer = new MutationObserver(function () {
                syncDashboardLayout();
            });
            observer.observe($loadTable.get(0), { childList: true, subtree: true });
        }
    }

    $(function () {
        initMobileSidebarToggle();
        initDashboardHome();
    });
}(jQuery));