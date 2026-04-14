document.addEventListener('DOMContentLoaded', function () {
    var revealItems = document.querySelectorAll('.reveal');
    var countItems = document.querySelectorAll('[data-count]');

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
