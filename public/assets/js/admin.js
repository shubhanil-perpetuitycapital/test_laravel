/*
 * Admin panel behaviour (vanilla JS).
 *  - confirmation modal for forms marked data-confirm
 *  - show/hide password toggle
 *  - single and multiple image upload previews
 *  - "will be removed" state for product images
 *  - slug suggestion from name
 *  - live preview, tab memory and unsaved-change warning on settings
 *  - auto-submit filter selects
 */
(function () {
    'use strict';

    /* ---------- Confirmation modal ---------- */
    var modalEl = document.getElementById('confirmModal');
    var pendingForm = null;
    if (modalEl && window.bootstrap) {
        var modal = new window.bootstrap.Modal(modalEl);
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (form.dataset.confirmed === '1') return;
            var message = form.getAttribute('data-confirm');
            // Order status form: only confirm when cancelling.
            if (form.matches('[data-confirm-cancel]') && form.status && form.status.value === 'cancelled') {
                message = 'Cancel this order? Its items go back into stock and the order cannot be reopened.';
            }
            if (!message) return;
            e.preventDefault();
            pendingForm = form;
            modalEl.querySelector('[data-confirm-message]').textContent = message;
            modal.show();
        });
        modalEl.querySelector('[data-confirm-ok]').addEventListener('click', function () {
            if (!pendingForm) return;
            pendingForm.dataset.confirmed = '1';
            modal.hide();
            pendingForm.submit();
        });
    }

    /* ---------- Password visibility ---------- */
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.querySelector(btn.getAttribute('data-toggle-password'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.querySelector('.bi').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
        });
    });

    /* ---------- Single image preview ---------- */
    document.querySelectorAll('[data-image-field]').forEach(function (wrap) {
        var input = wrap.querySelector('[data-image-input]');
        var img = wrap.querySelector('[data-image-preview]');
        var empty = wrap.querySelector('[data-image-empty]');
        if (!input || !img) return;
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file || !file.type.match(/^image\//)) return;
            var reader = new FileReader();
            reader.onload = function (ev) {
                img.src = ev.target.result;
                img.hidden = false;
                if (empty) empty.hidden = true;
                if (input.name === 'logo_light' || input.name === 'logo') {
                    var slot = document.querySelector('[data-preview-logo]');
                    if (slot) slot.innerHTML = '<img src="' + ev.target.result + '" alt="">';
                }
            };
            reader.readAsDataURL(file);
        });
    });

    /* ---------- Multiple upload preview (product photos) ---------- */
    document.querySelectorAll('[data-multi-upload]').forEach(function (input) {
        var target = document.querySelector(input.getAttribute('data-multi-upload'));
        input.addEventListener('change', function () {
            if (!target) return;
            target.innerHTML = '';
            Array.prototype.slice.call(input.files || [], 0, 10).forEach(function (file) {
                if (!file.type.match(/^image\//)) return;
                var reader = new FileReader();
                reader.onload = function (ev) {
                    var img = document.createElement('img');
                    img.src = ev.target.result;
                    img.alt = '';
                    target.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    });

    document.querySelectorAll('[data-remove-image]').forEach(function (box) {
        box.addEventListener('change', function () {
            var tile = box.closest('.image-tile');
            if (tile) tile.classList.toggle('is-removing', box.checked);
        });
    });

    /* ---------- Slug from name ---------- */
    document.querySelectorAll('[data-slug-source]').forEach(function (source) {
        var target = document.querySelector(source.getAttribute('data-slug-source'));
        if (!target) return;
        var touched = target.value !== '';
        target.addEventListener('input', function () { touched = target.value !== ''; });
        source.addEventListener('input', function () {
            if (touched) return;
            target.placeholder = source.value.toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '')
                .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        });
    });

    /* ---------- Auto-submit filters ---------- */
    document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
        el.addEventListener('change', function () { if (el.form) el.form.submit(); });
    });

    /* ---------- Settings form: live preview, tab memory, unsaved warning ---------- */
    var form = document.querySelector('[data-unsaved-guard]');
    if (form) {
        form.querySelectorAll('[data-preview-source]').forEach(function (field) {
            field.addEventListener('input', function () {
                document.querySelectorAll('[data-preview="' + field.getAttribute('data-preview-source') + '"]').forEach(function (el) {
                    el.textContent = field.value || '—';
                });
            });
        });

        var tabInput = form.querySelector('[data-active-tab]');
        form.querySelectorAll('[data-tab-key]').forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function () {
                if (tabInput) tabInput.value = tab.getAttribute('data-tab-key');
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tab.getAttribute('data-tab-key'));
                window.history.replaceState(null, '', url);
            });
        });

        var dirty = false;
        var hint = document.querySelector('[data-unsaved-hint]');
        var markDirty = function () { dirty = true; if (hint) hint.hidden = false; };
        form.addEventListener('input', markDirty);
        form.addEventListener('change', markDirty);
        form.addEventListener('submit', function () { dirty = false; });
        window.addEventListener('beforeunload', function (e) {
            if (!dirty) return;
            e.preventDefault();
            e.returnValue = '';
        });
    }
})();
