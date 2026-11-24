/*
 * Storefront behaviour (vanilla JS, no build step). Every feature here is
 * an enhancement: forms still submit normally when JavaScript is off.
 *
 *  - header shadow on scroll, search focus
 *  - live shop filtering with fetch()
 *  - add to cart with fetch(), toast and cart badge update
 *  - product quantity selector (also updates the WhatsApp enquiry link)
 *  - sticky buy bar visibility
 *  - delivery check on cart and checkout (charges, date, payment options)
 *  - copy UPI ID
 */
(function () {
    'use strict';

    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content;

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
            body: body
        });
    }

    /* ---------- Header ---------- */
    var header = document.querySelector('[data-header]');
    if (header) {
        var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }
    var searchPanel = document.getElementById('headerSearch');
    if (searchPanel) {
        searchPanel.addEventListener('shown.bs.collapse', function () {
            var input = searchPanel.querySelector('input');
            if (input) input.focus();
        });
    }

    /* ---------- Toast + cart badge ---------- */
    var toastEl = document.getElementById('cartToast');
    function toast(message, isError) {
        if (!toastEl || !window.bootstrap) { return; }
        toastEl.querySelector('[data-toast-message]').textContent = message;
        toastEl.classList.toggle('is-error', !!isError);
        window.bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 3500 }).show();
    }
    function setCartCount(count) {
        document.querySelectorAll('[data-cart-count]').forEach(function (b) {
            b.textContent = count;
            b.hidden = !count;
            b.classList.remove('is-bumped');
            void b.offsetWidth; // restart animation
            b.classList.add('is-bumped');
        });
    }

    /* ---------- Add to cart ---------- */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches('[data-add-to-cart]') || !window.fetch) return;
        // "Buy now" goes through the normal POST so the server redirects to checkout.
        if (e.submitter && e.submitter.name === 'buy_now') return;

        e.preventDefault();
        var buttons = form.querySelectorAll('button[type="submit"]');
        var external = form.id ? document.querySelectorAll('button[form="' + form.id + '"]') : [];
        var all = Array.prototype.slice.call(buttons).concat(Array.prototype.slice.call(external));
        all.forEach(function (b) { b.disabled = true; });

        postJson(form.action, new FormData(form))
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (r) {
                toast(r.data.message || (r.ok ? 'Added to cart.' : 'Could not add this item.'), !r.ok);
                if (typeof r.data.count === 'number') setCartCount(r.data.count);
                if (r.ok) {
                    var icon = form.querySelector('.product-card__go');
                    if (icon) {
                        icon.classList.add('is-added');
                        icon.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i>';
                        setTimeout(function () {
                            icon.classList.remove('is-added');
                            icon.innerHTML = '<i class="bi bi-bag-plus" aria-hidden="true"></i>';
                        }, 1600);
                    }
                }
            })
            .catch(function () { form.submit(); })
            .finally(function () { all.forEach(function (b) { b.disabled = false; }); });
    });

    /* ---------- Shop filters ---------- */
    var filterForm = document.querySelector('[data-filter-form]');
    var results = document.querySelector('[data-shop-results]');
    if (filterForm && results && window.fetch && window.history) {
        var countEl = document.querySelector('[data-result-count]');
        var titleEl = document.querySelector('[data-shop-title]');
        var controller = null;
        var debounceTimer = null;
        var desktop = window.matchMedia('(min-width: 992px)');

        var buildUrl = function () {
            var params = new URLSearchParams(new FormData(filterForm));
            Array.from(params.keys()).forEach(function (key) { if (!params.get(key)) params.delete(key); });
            var qs = params.toString();
            return filterForm.action + (qs ? '?' + qs : '');
        };

        var load = function (url, push) {
            if (controller) controller.abort();
            controller = new AbortController();
            results.classList.add('is-loading');
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
                .then(function (res) { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                .then(function (data) {
                    results.innerHTML = data.html;
                    if (countEl) countEl.textContent = data.count;
                    if (titleEl && data.title) titleEl.textContent = data.title;
                    if (push) window.history.pushState({ shop: true }, '', url);
                })
                .catch(function (err) { if (err.name !== 'AbortError') window.location.href = url; })
                .finally(function () { results.classList.remove('is-loading'); });
        };

        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            load(buildUrl(), true);
            var off = document.getElementById('shopFilters');
            if (off && window.bootstrap && !desktop.matches) {
                var inst = window.bootstrap.Offcanvas.getInstance(off);
                if (inst) inst.hide();
            }
        });
        document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
            el.addEventListener('change', function () { filterForm.requestSubmit ? filterForm.requestSubmit() : filterForm.dispatchEvent(new Event('submit')); });
        });
        filterForm.querySelectorAll('[data-debounce]').forEach(function (el) {
            el.addEventListener('input', function () {
                if (!desktop.matches) return;
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () { load(buildUrl(), true); }, 450);
            });
        });
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
        var clamp = function (v) { v = parseInt(v, 10); if (isNaN(v) || v < 1) v = 1; return Math.min(v, max); };
        var sync = function () {
            if (!input) return;
            var qty = clamp(input.value);
            input.value = qty;
            var href = 'https://wa.me/' + number + '?text=' + encodeURIComponent(template.replace('{qty}', qty));
            document.querySelectorAll('[data-wa-order]').forEach(function (a) { a.href = href; });
        };
        buy.querySelectorAll('[data-qty-step]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                input.value = clamp((parseInt(input.value, 10) || 1) + parseInt(btn.getAttribute('data-qty-step'), 10));
                sync();
            });
        });
        if (input) {
            input.addEventListener('change', sync);
            input.addEventListener('input', function () { if (input.value !== '') sync(); });
        }
        sync();

        var bar = document.querySelector('[data-buy-bar]');
        if (bar && 'IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                bar.classList.toggle('is-hidden', entries[0].isIntersecting);
            }, { threshold: 0.2 }).observe(buy);
        }
    }

    /* ---------- Totals updater shared by cart and checkout ---------- */
    function applySummary(summary) {
        Object.keys(summary || {}).forEach(function (key) {
            document.querySelectorAll('[data-sum="' + key + '"]').forEach(function (el) {
                el.textContent = summary[key] || '';
            });
        });
    }

    /* ---------- Cart: delivery check ---------- */
    var checkForm = document.querySelector('[data-delivery-check]');
    if (checkForm && window.fetch) {
        var resultEl = checkForm.querySelector('[data-delivery-result]');
        var runCheck = function () {
            var pin = checkForm.querySelector('[name="pincode"]').value.trim();
            if (!/^\d{6}$/.test(pin)) {
                resultEl.innerHTML = '<span class="text-danger">Enter a 6-digit PIN code.</span>';
                return;
            }
            resultEl.textContent = 'Checking…';
            var data = new FormData(checkForm);
            postJson(checkForm.getAttribute('data-url'), data)
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    var q = data.quote;
                    resultEl.innerHTML = '';
                    var span = document.createElement('span');
                    span.className = q.ok ? 'text-success' : 'text-danger';
                    span.textContent = q.ok ? '✓ ' + (q.estimate || 'Delivery available') : q.error;
                    resultEl.appendChild(span);
                    applySummary(data.summary);
                })
                .catch(function () { resultEl.textContent = 'Could not check right now. Try again at checkout.'; });
        };
        checkForm.addEventListener('submit', function (e) { e.preventDefault(); runCheck(); });
        checkForm.querySelectorAll('[name="method"]').forEach(function (r) {
            r.addEventListener('change', function () { if (/^\d{6}$/.test(checkForm.pincode.value.trim())) runCheck(); });
        });
    }

    /* ---------- Checkout: live quote ---------- */
    var checkout = document.querySelector('[data-checkout]');
    if (checkout && window.fetch) {
        var pinInput = checkout.querySelector('[data-pincode]');
        var box = checkout.querySelector('[data-quote-box]');
        var cityInput = checkout.querySelector('[name="city"]');
        var stateInput = checkout.querySelector('[name="state"]');
        var defaultCity = cityInput ? cityInput.dataset.default || '' : '';
        var defaultState = stateInput ? stateInput.dataset.default || '' : '';
        var quoteTimer = null;

        var currentMethod = function () {
            var checked = checkout.querySelector('[data-delivery-method]:checked');
            return checked ? checked.value : 'local';
        };

        var updatePayments = function (allowed) {
            var any = false;
            checkout.querySelectorAll('[data-payment]').forEach(function (card) {
                var key = card.getAttribute('data-payment');
                var ok = Object.prototype.hasOwnProperty.call(allowed, key);
                var radio = card.querySelector('input');
                card.hidden = !ok;
                radio.disabled = !ok;
                if (!ok && radio.checked) radio.checked = false;
                if (ok) any = true;
            });
            if (!checkout.querySelector('[name="payment_method"]:checked')) {
                var first = checkout.querySelector('[name="payment_method"]:not(:disabled)');
                if (first) first.checked = true;
            }
            var none = checkout.querySelector('[data-no-payment]');
            if (none) none.hidden = any;
        };

        var runQuote = function () {
            var pin = (pinInput.value || '').replace(/\D/g, '');
            if (pin.length !== 6) return;
            var data = new FormData();
            data.append('method', currentMethod());
            data.append('pincode', pin);
            box.classList.add('opacity-50');
            postJson(checkout.getAttribute('data-quote-url'), data)
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    box.innerHTML = data.html;
                    if (data.local_pin) {
                        var hint = document.createElement('p');
                        hint.className = 'delivery-box__note';
                        hint.textContent = 'This PIN is in our Kolkata delivery area. Choose Kolkata local delivery for a faster, cheaper delivery.';
                        box.appendChild(hint);
                    }
                    applySummary(data.summary);
                    updatePayments(data.payment_methods || {});
                })
                .catch(function () {})
                .finally(function () { box.classList.remove('opacity-50'); });
        };

        pinInput.addEventListener('input', function () {
            clearTimeout(quoteTimer);
            quoteTimer = setTimeout(runQuote, 350);
        });
        checkout.querySelectorAll('[data-delivery-method]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                // Prefill city/state for local delivery; clear them for courier so they aren't wrong.
                if (cityInput && stateInput) {
                    if (radio.value === 'local') {
                        if (!cityInput.value) cityInput.value = defaultCity;
                        if (!stateInput.value) stateInput.value = defaultState || '';
                    } else if (cityInput.value === defaultCity && stateInput.value === defaultState) {
                        cityInput.value = '';
                        stateInput.value = '';
                    }
                }
                runQuote();
            });
        });

        checkout.addEventListener('submit', function () {
            var btn = checkout.querySelector('[data-place-order]');
            if (btn) { btn.disabled = true; btn.textContent = 'Placing order…'; }
        });
    }

    /* ---------- Copy UPI ID ---------- */
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var source = btn.parentElement.querySelector('[data-copy-source]');
            if (!source || !navigator.clipboard) return;
            navigator.clipboard.writeText(source.textContent.trim()).then(function () {
                btn.textContent = 'Copied';
                setTimeout(function () { btn.textContent = 'Copy'; }, 1500);
            });
        });
    });
})();
