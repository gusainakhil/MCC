document.addEventListener('DOMContentLoaded', function () {
    var revealItems = document.querySelectorAll('.reveal');
    var countItems = document.querySelectorAll('[data-count]');
    var body = document.body;
    var sidebar = document.getElementById('dashboardSidebar');
    var sidebarToggleButtons = document.querySelectorAll('[data-sidebar-toggle]');
    var sidebarCloseButtons = document.querySelectorAll('[data-sidebar-close]');

    var isMobileViewport = function () {
        return window.matchMedia('(max-width: 1199.98px)').matches;
    };

    var syncToggleState = function () {
        var expanded = isMobileViewport() ? body.classList.contains('sidebar-open') : !body.classList.contains('sidebar-collapsed');
        sidebarToggleButtons.forEach(function (button) {
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    };

    var closeSidebar = function () {
        body.classList.remove('sidebar-open');
        syncToggleState();
    };

    var openSidebar = function () {
        body.classList.add('sidebar-open');
        syncToggleState();
    };

    if (sidebar && sidebarToggleButtons.length > 0) {
        sidebarToggleButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (isMobileViewport()) {
                    if (body.classList.contains('sidebar-open')) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }
                } else {
                    body.classList.toggle('sidebar-collapsed');
                    syncToggleState();
                }
            });
        });

        sidebarCloseButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                closeSidebar();
            });
        });

        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isMobileViewport()) {
                    closeSidebar();
                }
            });
        });

        window.addEventListener('resize', function () {
            if (!isMobileViewport()) {
                body.classList.remove('sidebar-open');
            } else {
                body.classList.remove('sidebar-collapsed');
            }
            syncToggleState();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && body.classList.contains('sidebar-open')) {
                closeSidebar();
            }
        });

        document.addEventListener('click', function (event) {
            if (!isMobileViewport() || !body.classList.contains('sidebar-open')) {
                return;
            }

            if (event.target.closest('[data-sidebar-close]')) {
                closeSidebar();
                return;
            }

            if (!event.target.closest('#dashboardSidebar') && !event.target.closest('[data-sidebar-toggle]')) {
                closeSidebar();
            }
        });

        if (isMobileViewport()) {
            body.classList.remove('sidebar-collapsed');
        }

        syncToggleState();
    }

    if ('IntersectionObserver' in window) {
        var revealObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.16,
            rootMargin: '0px 0px -8% 0px'
        });

        revealItems.forEach(function (item) {
            revealObserver.observe(item);
        });
    } else {
        revealItems.forEach(function (item) {
            item.classList.add('is-visible');
        });
    }

    countItems.forEach(function (item) {
        var target = parseInt(item.getAttribute('data-count'), 10) || 0;
        item.textContent = '0';

        var animate = function () {
            var start = 0;
            var duration = 900;
            var startTime = null;

            var step = function (timestamp) {
                if (!startTime) {
                    startTime = timestamp;
                }

                var progress = Math.min((timestamp - startTime) / duration, 1);
                var value = Math.floor(progress * (target - start) + start);
                item.textContent = String(value);

                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    item.textContent = String(target);
                }
            };

            window.requestAnimationFrame(step);
        };

        if ('IntersectionObserver' in window) {
            var countObserver = new IntersectionObserver(function (entries, observer) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        animate();
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.45
            });

            countObserver.observe(item);
        } else {
            animate();
        }
    });
});
