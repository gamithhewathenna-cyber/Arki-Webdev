/* ARKI CMS – small enhancements (no inline scripts, CSP friendly) */
(function () {
    'use strict';

    // Confirm destructive actions
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
        });
    });

    // Live preview of a newly selected image
    document.querySelectorAll('input[data-image-input]').forEach(function (input) {
        input.addEventListener('change', function () {
            var img = document.querySelector('img[data-preview="' + input.id + '"]');
            var file = input.files && input.files[0];
            if (!img || !file) return;
            if (file.size > 10 * 1024 * 1024) {
                alert('This image is larger than 10 MB. Please choose a smaller file.');
                input.value = '';
                return;
            }
            img.src = URL.createObjectURL(file);
            img.hidden = false;
            img.parentElement.classList.remove('is-empty');
        });
    });

    // Warn before leaving a form with unsaved changes
    document.querySelectorAll('form.adm-form').forEach(function (form) {
        var dirty = false;
        form.addEventListener('input', function () { dirty = true; });
        form.addEventListener('change', function () { dirty = true; });
        form.addEventListener('submit', function () { dirty = false; });
        window.addEventListener('beforeunload', function (e) {
            if (dirty) { e.preventDefault(); e.returnValue = ''; }
        });
    });
})();
