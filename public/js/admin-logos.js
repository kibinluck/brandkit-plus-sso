/*
 * Brandkit Plugin (Community Edition) - AGPL-3.0-or-later
 * Logos step of the configuration page: auto-submit uploads, live size preview.
 */

document.addEventListener('DOMContentLoaded', function () {
    var inputs = document.querySelectorAll('.brandkit-logo-input');
    inputs.forEach(function (input) {
        input.addEventListener('change', function () {
            if (input.files && input.files.length) {
                input.form.submit();
            }
        });
    });

    var headerScale = document.getElementById('brandkit_logo_header_scale');
    var reducedScale = document.getElementById('brandkit_logo_reduced_scale');
    var saveButton = document.querySelector('.brandkit-save-logo');
    var root = document.documentElement;
    var previewValues = document.querySelectorAll('[data-brandkit-logo-value]');
    var previewImages = document.querySelectorAll('[data-brandkit-logo-scale]');
    var previewTimers = new Map();
    function capScale(value) {
        return Math.min(1.15, Math.max(0.6, value / 100));
    }
    function updatePreviewScales() {
        var headerValue = headerScale ? parseInt(headerScale.value || '100', 10) : 100;
        var reducedValue = reducedScale ? parseInt(reducedScale.value || '100', 10) : 100;
        root.style.setProperty('--brandkit-logo-preview-header-scale', capScale(headerValue));
        root.style.setProperty('--brandkit-logo-preview-reduced-scale', capScale(reducedValue));
        previewValues.forEach(function (node) {
            var type = node.getAttribute('data-brandkit-logo-value');
            if (type === 'header') {
                node.textContent = headerValue + '%';
            } else if (type === 'reduced') {
                node.textContent = reducedValue + '%';
            }
            var sample = node.closest('.logo-sample');
            if (sample) {
                sample.classList.add('brandkit-preview-active');
                var existing = previewTimers.get(sample);
                if (existing) {
                    clearTimeout(existing);
                }
                previewTimers.set(sample, setTimeout(function () {
                    sample.classList.remove('brandkit-preview-active');
                }, 1200));
            }
        });
        previewImages.forEach(function (img) {
            img.classList.remove('brandkit-scale-pulse');
            void img.offsetWidth;
            img.classList.add('brandkit-scale-pulse');
        });
        if (saveButton) {
            saveButton.classList.remove('brandkit-save-pulse');
            void saveButton.offsetWidth;
            saveButton.classList.add('brandkit-save-pulse');
        }
    }
    updatePreviewScales();
    if (headerScale) {
        headerScale.addEventListener('input', updatePreviewScales);
    }
    if (reducedScale) {
        reducedScale.addEventListener('input', updatePreviewScales);
    }
});
