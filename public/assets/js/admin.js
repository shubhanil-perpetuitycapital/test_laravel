/*
 * Admin panel behaviour (vanilla JS).
 *  - confirmation modal for forms marked data-confirm
 *  - show/hide password toggle
 *  - image upload previews
 *  - live preview + unsaved-change warning on the company setup form
 *  - remembers the active settings tab across saves
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
            if (!form.matches('[data-confirm]') || form.dataset.confirmed === '1') return;
            e.preventDefault();
            pendingForm = form;
            modalEl.querySelector('[data-confirm-message]').textContent = form.getAttribute('data-confirm');
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

    /* ---------- Image previews ---------- */
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
                // Mirror a new logo in the live preview.
                if (input.name === 'logo_light' || (input.name === 'logo' && !document.querySelector('input[name="remove_logo_light"]'))) {
                    var slot = document.querySelector('[data-preview-logo]');
                    if (slot) slot.innerHTML = '<img src="' + ev.target.result + '" alt="">';
                }
            };
            reader.readAsDataURL(file);
        });
    });

    /* ---------- Company form: live preview, tab memory, unsaved warning ---------- */
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
        form.addEventListener('input', function () { dirty = true; if (hint) hint.hidden = false; });
        form.addEventListener('change', function () { dirty = true; if (hint) hint.hidden = false; });
        form.addEventListener('submit', function () { dirty = false; });
        window.addEventListener('beforeunload', function (e) {
            if (!dirty) return;
            e.preventDefault();
            e.returnValue = '';
        });
    }
})();
