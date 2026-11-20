/*
 * Storefront behaviour (vanilla JS, no build step).
 *  - header shadow on scroll
 *  - live shop filtering with fetch(), falling back to normal form submits
 *  - quantity selector that updates the WhatsApp order link
 *  - hide the sticky buy bar while the main buy button is visible
 */
(function () {
    'use strict';

    /* ---------- Header state ---------- */
    var header = document.querySelector('[data-header]');
    if (header) {
        var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* Focus the search box when the header search opens. */
    var searchPanel = document.getElementById('headerSearch');
    if (searchPanel) {
        searchPanel.addEventListener('shown.bs.collapse', function () {
            var input = searchPanel.querySelector('input');
            if (input) input.focus();
        });
    }

    /* ---------- Shop filters ---------- */
    var form = document.querySelector('[data-filter-form]');
    var results = document.querySelector('[data-shop-results]');

    if (form && results && window.fetch && window.history) {
        var countEl = document.querySelector('[data-result-count]');
        var titleEl = document.querySelector('[data-shop-title]');
        var controller = null;
        var debounceTimer = null;
        var desktop = window.matchMedia('(min-width: 992px)');

        var buildUrl = function (base) {
            var params = new URLSearchParams(new FormData(form));
            // Drop empty values to keep URLs clean and shareable.
            Array.from(params.keys()).forEach(function (key) {
                if (!params.get(key)) params.delete(key);
            });
            var qs = params.toString();
            return (base || form.action) + (qs ? '?' + qs : '');
        };

        var load = function (url, push) {
            if (controller) controller.abort();
            controller = new AbortController();
            results.classList.add('is-loading');

            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function (data) {
                    results.innerHTML = data.html;
                    if (countEl) countEl.textContent = data.count;
                    if (titleEl && data.title) titleEl.textContent = data.title;
                    if (push) window.history.pushState({ shop: true }, '', url);
                })
                .catch(function (err) {
                    if (err.name !== 'AbortError') window.location.href = url; // fall back to a full page load
                })
                .finally(function () { results.classList.remove('is-loading'); });
        };

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            load(buildUrl(), true);
            var off = document.getElementById('shopFilters');
            if (off && window.bootstrap && !desktop.matches) {
                var inst = window.bootstrap.Offcanvas.getInstance(off);
                if (inst) inst.hide();
            }
        });

        // Sort select and switches apply immediately.
        document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
            el.addEventListener('change', function () { form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit')); });
        });

        // Text and price fields apply after typing pauses (desktop only; phones use "Show results").
        form.querySelectorAll('[data-debounce]').forEach(function (el) {
            el.addEventListener('input', function () {
                if (!desktop.matches) return;
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () { load(buildUrl(), true); }, 450);
            });
        });

        // Pagination links inside the results load in place.
        results.addEventListener('click', function (e) {
            var link = e.target.closest('.pagination a');
            if (!link) return;
            e.preventDefault();
            load(link.href, true);
            results.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        window.addEventListener('popstate', function () { load(window.location.href, false); });
    }

    /* ---------- Product quantity + WhatsApp link ---------- */
    var buy = document.querySelector('[data-product-buy]');
    if (buy) {
        var input = buy.querySelector('[data-qty-input]');
        var max = parseInt(buy.getAttribute('data-max'), 10) || 1;
        var number = buy.getAttribute('data-wa-number');
        var template = buy.getAttribute('data-wa-template') || '';

        var clamp = function (v) {
            v = parseInt(v, 10);
            if (isNaN(v) || v < 1) v = 1;
            if (v > max) v = max;
            return v;
        };

        var sync = function () {
            if (!input) return;
            var qty = clamp(input.value);
            input.value = qty;
            var href = 'https://wa.me/' + number + '?text=' + encodeURIComponent(template.replace('{qty}', qty));
            document.querySelectorAll('[data-wa-order]').forEach(function (a) { a.href = href; });
        };

        buy.querySelectorAll('[data-qty-step]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!input) return;
                input.value = clamp((parseInt(input.value, 10) || 1) + parseInt(btn.getAttribute('data-qty-step'), 10));
                sync();
            });
        });
        if (input) {
            input.addEventListener('change', sync);
            input.addEventListener('input', function () { if (input.value !== '') sync(); });
        }
        sync();

        // Hide the sticky bar while the main buy area is on screen.
        var bar = document.querySelector('[data-buy-bar]');
        if (bar && 'IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                bar.classList.toggle('is-hidden', entries[0].isIntersecting);
            }, { threshold: 0.2 }).observe(buy);
        }
    }
})();
