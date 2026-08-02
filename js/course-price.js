/**
 * Programmers Lab — Dynamic Course Price Loader
 *
 * Usage:
 *   <body data-course="Full Stack Web Development">
 *   <span data-price>Rs. 22,000</span>        ← current price
 *   <span data-orig-price>Rs. 28,000</span>   ← original (strikethrough)
 *   <span data-duration>3 Months</span>        ← duration
 *   <script src="js/course-price.js"></script>
 */
(function () {
    function fmt(n) {
        if (!n || parseInt(n) === 0) return 'Contact Us';
        return 'Rs. ' + parseInt(n).toLocaleString('en-PK');
    }

    // Auto-detect API base URL from script tag location
    function getApiUrl() {
        var scripts = document.querySelectorAll('script[src]');
        for (var i = 0; i < scripts.length; i++) {
            var src = scripts[i].getAttribute('src');
            if (src && src.indexOf('course-price.js') !== -1) {
                // src is like "js/course-price.js" or "/path/js/course-price.js"
                // Remove "js/course-price.js" and add "api/courses.php"
                var base = src.replace(/js\/course-price\.js.*$/, '');
                return base + 'api/courses.php';
            }
        }
        // fallback
        return '/api/courses.php';
    }

    var apiUrl = getApiUrl();

    fetch(apiUrl)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success || !data.details) return;
            var details = data.details;

            // Method 1: body[data-course] → update all [data-price], [data-orig-price], [data-duration], [data-installment]
            var bodyCourseName = document.body.getAttribute('data-course');
            if (bodyCourseName && details[bodyCourseName]) {
                var d = details[bodyCourseName];

                document.querySelectorAll('[data-price]').forEach(function (el) {
                    el.textContent = fmt(d.price);
                });
                document.querySelectorAll('[data-orig-price]').forEach(function (el) {
                    if (d.original_price > 0) {
                        el.textContent = fmt(d.original_price);
                        el.style.textDecoration = 'line-through';
                        el.style.color = '#9ca3af';
                        el.style.fontSize = '13px';
                    } else {
                        el.style.display = 'none';
                    }
                });
                document.querySelectorAll('[data-installment]').forEach(function (el) {
                    if (d.installment_price > 0) {
                        el.textContent = fmt(d.installment_price);
                        el.style.display = '';
                        // Show installment card if hidden
                        var card = document.getElementById('pl-install-card');
                        if (card) card.style.display = '';
                        // Show pricing section
                        var section = document.getElementById('pl-pricing-section');
                        if (section) section.style.display = '';
                    } else {
                        el.style.display = 'none';
                    }
                });
                document.querySelectorAll('[data-duration]').forEach(function (el) {
                    if (d.duration) el.textContent = d.duration;
                });
            }

            // Method 2: elements with [data-course-price="CourseName"] — for listing pages
            document.querySelectorAll('[data-course-price]').forEach(function (el) {
                var name = el.getAttribute('data-course-price');
                if (details[name]) el.textContent = fmt(details[name].price);
            });
            document.querySelectorAll('[data-course-orig-price]').forEach(function (el) {
                var name = el.getAttribute('data-course-orig-price');
                if (details[name] && details[name].original_price > 0) {
                    el.textContent = fmt(details[name].original_price);
                    el.style.textDecoration = 'line-through';
                }
            });
            document.querySelectorAll('[data-course-duration]').forEach(function (el) {
                var name = el.getAttribute('data-course-duration');
                if (details[name] && details[name].duration) el.textContent = details[name].duration;
            });
        })
        .catch(function () { /* silent fail — hardcoded fallback stays visible */ });
})();
