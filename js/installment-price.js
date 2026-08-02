/**
 * Programmers Lab — Installment Price Loader
 * Standalone script — course-price.js se alag
 *
 * Usage:
 *   <body data-course="PHP & MySQL">
 *   Course page mein yeh script include karo:
 *   <script src="js/installment-price.js"></script>
 *
 * Yeh script:
 *   1. API se installment_price fetch karta hai
 *   2. #installment-card show karta hai agar price > 0 ho
 *   3. Card mein price update karta hai
 */
(function () {
    // Get API base URL from this script's src
    function getBase() {
        var scripts = document.querySelectorAll('script[src]');
        for (var i = 0; i < scripts.length; i++) {
            var src = scripts[i].getAttribute('src') || '';
            if (src.indexOf('installment-price.js') !== -1) {
                return src.replace(/js\/installment-price\.js.*$/, '');
            }
        }
        return '/';
    }

    function fmt(n) {
        return 'Rs. ' + parseInt(n).toLocaleString('en-PK');
    }

    window.addEventListener('DOMContentLoaded', function () {
        var courseName = document.body.getAttribute('data-course');
        if (!courseName) return;

        var apiUrl = getBase() + 'api/courses.php';

        fetch(apiUrl)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success || !data.details) return;

                var course = data.details[courseName];
                if (!course) return;

                var installPrice = parseInt(course.installment_price) || 0;
                if (installPrice <= 0) return;

                // Find ALL installment elements (info card + li both)
                var installEls = document.querySelectorAll('[data-installment]');
                installEls.forEach(function(el) {
                    el.textContent = fmt(installPrice);
                    el.style.display = '';
                });

                // Show info card if exists
                var card = document.getElementById('installment-card');
                if (card) card.style.display = '';

                // Also update pricing section installment card if exists
                var installCard2 = document.getElementById('pl-install-card');
                if (installCard2) {
                    var priceEl2 = installCard2.querySelector('[data-installment]');
                    if (priceEl2) priceEl2.textContent = fmt(installPrice);
                    installCard2.style.display = '';
                }

                // Show pricing section
                var section = document.getElementById('pl-pricing-section');
                if (section) section.style.display = 'block';
            })
            .catch(function () {}); // silent fail
    });
})();
