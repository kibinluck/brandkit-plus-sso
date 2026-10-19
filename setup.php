<?php

/**
 * ------------------------------------------------------------------------
 * Brandkit Plugin (Community Edition)
 * Copyright (C) 2026 Marcati
 * https://github.com/juniormarcati
 * ------------------------------------------------------------------------
 * This file is part of Brandkit Plugin.
 *
 * Brandkit Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Brandkit Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Brandkit Plugin. If not, see <https://www.gnu.org/licenses/>.
 * ------------------------------------------------------------------------
 *
 * Provides branding customization for GLPI (login page and system logos).
 *
 * @package   Brandkit Plugin
 * @author    Marcati
 * @copyright 2026 Marcati
 * @license   AGPL-3.0-or-later
 * @link      https://github.com/juniormarcati/brandkit
 * @since     2026
 * ------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

define('PLUGIN_BRANDKIT_VERSION', '0.4.0');
define('PLUGIN_BRANDKIT_MIN_GLPI', '10.0.0');
// GLPI 12 is not supported yet: GLPI refuses to load the plugin there, which
// is the safe behaviour until it has been tested.
define('PLUGIN_BRANDKIT_MAX_GLPI', '11.0.99');

define('PLUGIN_BRANDKIT_NAME', 'Brandkit Plus SSO');
define('PLUGIN_BRANDKIT_FORK_MAINTAINER', 'Bruno Braz');
define('PLUGIN_BRANDKIT_ORIGINAL_URL', 'https://github.com/juniormarcati/glpi-brandkit');
define('PLUGIN_BRANDKIT_FORK_URL', 'https://github.com/kibinluck/brandkit-plus-sso');

require_once __DIR__ . '/inc/paths.php';
require_once __DIR__ . '/hook.php';

/**
 * Init the hooks of the plugin - Needed
 */
function plugin_init_brandkit()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant'][PLUGIN_BRANDKIT_KEY] = true;

    $isGlpi11 = brandkit_is_glpi11();

    // Login page (anonymous): generated stylesheet + self-healing of the
    // native logo files, see plugin_brandkit_display_login().
    $PLUGIN_HOOKS['display_login'][PLUGIN_BRANDKIT_KEY] = 'plugin_brandkit_display_login';
    $loginCssUrl = brandkit_get_login_css_url();
    $storageDir = $loginCssUrl === null ? brandkit_get_plugin_storage_dir() : null;
    if ($loginCssUrl === null && $storageDir !== null && is_writable($storageDir)) {
        // First run (or storage wiped): generate it now so the very first
        // login page is styled. Never let this break GLPI's bootstrap.
        try {
            if (brandkit_write_login_css()) {
                $loginCssUrl = brandkit_get_login_css_url();
            }
        } catch (Throwable $e) {
            trigger_error('Brandkit: unable to generate the login stylesheet: ' . $e->getMessage(), E_USER_WARNING);
        }
    }
    $anonymousHeaderTags = [];
    if ($loginCssUrl !== null) {
        $anonymousHeaderTags[] = [
            'tag' => 'link',
            'properties' => [
                'rel' => 'stylesheet',
                'href' => $loginCssUrl,
            ],
        ];
    }
    // SSO mode hides the local login form, unless ?bypass=1 asks for it.
    // Decided here, server side, so the local form can always be reached.
    // With ?bypass=1 it is the other way round: the page is for local
    // accounts only, so the SSO buttons are hidden instead.
    if (!Session::getLoginUserID()) {
        try {
            if (brandkit_get_sso_settings()['enabled']) {
                $stylesheet = brandkit_is_sso_bypass_requested() ? 'css/brandkit-sso-local.css' : 'css/brandkit-sso.css';
                $anonymousHeaderTags[] = [
                    'tag' => 'link',
                    'properties' => [
                        'rel' => 'stylesheet',
                        'href' => brandkit_get_public_asset_url($stylesheet) . '?v=' . PLUGIN_BRANDKIT_VERSION,
                    ],
                ];
            }
        } catch (Throwable $e) {
            trigger_error('Brandkit: unable to read the SSO settings: ' . $e->getMessage(), E_USER_WARNING);
        }
    }
    if ($anonymousHeaderTags !== []) {
        $PLUGIN_HOOKS['add_header_tag_anonymous_page'][PLUGIN_BRANDKIT_KEY] = $anonymousHeaderTags;
    }
    if (!$isGlpi11) {
        $PLUGIN_HOOKS['add_javascript_anonymous_page'][PLUGIN_BRANDKIT_KEY] = brandkit_get_public_hook_path('js/brandkit-login.js');
    }

    // Error pages reuse the login look (see public/js/brandkit-error.js).
    // Static files live in public/: GLPI 11 serves nothing else.
    $PLUGIN_HOOKS['add_javascript'][PLUGIN_BRANDKIT_KEY] = brandkit_get_public_hook_path('js/brandkit-error.js');

    if (!Session::getLoginUserID()) {
        return;
    }

    $PLUGIN_HOOKS['add_css'][PLUGIN_BRANDKIT_KEY][] = 'front/brandkit.css.php';

    // GLPI caches the menu in session; drop it once after an upgrade so the
    // Brandkit entry shows up without logging out.
    if (($_SESSION['brandkit_menu_version'] ?? null) !== PLUGIN_BRANDKIT_VERSION) {
        unset($_SESSION['glpimenu']);
        $_SESSION['brandkit_menu_version'] = PLUGIN_BRANDKIT_VERSION;
    }

    Plugin::registerClass('PluginBrandkitConfig');

    if (Session::haveRight('config', UPDATE)) {
        $PLUGIN_HOOKS['menu_toadd'][PLUGIN_BRANDKIT_KEY] = ['plugins' => 'PluginBrandkitConfig'];
        $PLUGIN_HOOKS['config_page'][PLUGIN_BRANDKIT_KEY] = 'front/index.php';
        if (!$isGlpi11) {
            $PLUGIN_HOOKS['menu_entry'][PLUGIN_BRANDKIT_KEY] = 'front/index.php';
            $PLUGIN_HOOKS['submenu_entry'][PLUGIN_BRANDKIT_KEY]['search'] = 'front/index.php';
        }
    }
}

/**
 * Get the name and the version of the plugin - Needed
 */
function plugin_version_brandkit()
{
    return [
        'name' => PLUGIN_BRANDKIT_NAME,
        'version' => PLUGIN_BRANDKIT_VERSION,
        // Fork of Brandkit by Marcati: original author first.
        'author' => '<a href="https://github.com/juniormarcati">Marcati</a> (Brandkit), '
            . PLUGIN_BRANDKIT_FORK_MAINTAINER . ' (Plus SSO)',
        'license' => 'AGPLv3+',
        'homepage' => PLUGIN_BRANDKIT_FORK_URL,
        'readme' => PLUGIN_BRANDKIT_FORK_URL . '/blob/main/Readme.md',
        'issues' => PLUGIN_BRANDKIT_FORK_URL . '/issues',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_BRANDKIT_MIN_GLPI,
                'max' => PLUGIN_BRANDKIT_MAX_GLPI,
            ],
            'php' => [
                'min' => '7.4',
            ],
        ],
    ];
}
