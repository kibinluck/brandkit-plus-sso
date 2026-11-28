/*
 * Brandkit Plus SSO - AGPL-3.0-or-later
 * Fork of Brandkit by Marcati (https://github.com/juniormarcati/glpi-brandkit).
 *
 * SSO mode with samlSSO 1.4+: the "Sign in with a local account" link opens
 * a dialog that asks for samlSSO's emergency bypass key. The dialog markup
 * comes in a <template> printed by the display_login hook (inside GLPI's
 * login form, where a nested form is not allowed), so it is moved to <body>.
 *
 * The dialog form is a plain GET form; samlSSO checks the key. This script
 * never reads, stores or sends the key itself.
 */
(function () {
    'use strict';

    function stripKeyFromAddress() {
        try {
            var url = new URL(window.location.href);
            url.searchParams.delete('token');
            url.searchParams.delete('bypass_token');
            window.history.replaceState(window.history.state, '', url.toString());
        } catch (e) {
            // Old browser: the address keeps the rejected key, nothing else to do.
        }
    }

    function init() {
        var template = document.getElementById('brandkit-bypass-template');
        if (!template || !('content' in template)) {
            return;
        }

        var rejected = template.getAttribute('data-rejected') === '1';
        if (rejected) {
            stripKeyFromAddress();
        }

        var dialog = template.content.firstElementChild;
        if (!dialog || typeof dialog.showModal !== 'function') {
            return;
        }
        dialog = document.importNode(dialog, true);
        document.body.appendChild(dialog);

        var input = dialog.querySelector('input[name="token"]');
        var open = function () {
            if (!dialog.open) {
                dialog.showModal();
            }
            if (input) {
                input.focus();
            }
        };

        var cancel = dialog.querySelector('[data-brandkit-bypass-cancel]');
        if (cancel) {
            cancel.addEventListener('click', function () {
                dialog.close();
            });
        }

        // Clicking the dark area around the dialog closes it.
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) {
                dialog.close();
            }
        });

        dialog.addEventListener('close', function () {
            if (input) {
                input.value = '';
            }
        });

        var links = document.querySelectorAll('[data-brandkit-bypass]');
        for (var i = 0; i < links.length; i++) {
            links[i].addEventListener('click', function (event) {
                event.preventDefault();
                open();
            });
        }

        if (rejected) {
            open();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
