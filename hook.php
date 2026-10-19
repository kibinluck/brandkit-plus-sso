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

require_once __DIR__ . '/inc/paths.php';
require_once __DIR__ . '/inc/login.css.php';

/**
 * Install (also called by GLPI when the plugin version changes).
 */
function plugin_brandkit_install()
{
    brandkit_migrate_legacy_storage();
    brandkit_get_storage_subdir('logos');
    brandkit_write_login_css();

    return true;
}

/**
 * Uninstall: put GLPI's own logos and favicon back, then remove every file
 * and setting the plugin created.
 */
function plugin_brandkit_uninstall()
{
    brandkit_restore_original_glpi_assets();

    $config = Config::getConfigurationValues('plugin:brandkit');
    if (!empty($config)) {
        Config::deleteConfigurationValues('plugin:brandkit', array_keys($config));
    }

    $wallpaperDir = brandkit_get_wallpaper_public_dir(false);
    if ($wallpaperDir !== null) {
        brandkit_remove_dir($wallpaperDir);
    }
    $storageDir = brandkit_get_plugin_storage_dir(false);
    if ($storageDir !== null) {
        brandkit_remove_dir($storageDir);
    }

    return true;
}

/**
 * Releases up to 0.1.7 ignored a customized GLPI_PLUGIN_DOC_DIR and always
 * wrote to GLPI_VAR_DIR/_plugins/brandkit. Copy those files over once.
 */
function brandkit_migrate_legacy_storage(): void
{
    $legacy = brandkit_get_legacy_storage_dir();
    $current = brandkit_get_plugin_storage_dir();
    if ($legacy === null || $current === null) {
        return;
    }

    foreach (['logos', 'wallpaper', 'originals'] as $folder) {
        $from = $legacy . DIRECTORY_SEPARATOR . $folder;
        if (!is_dir($from)) {
            continue;
        }
        $to = brandkit_get_storage_subdir($folder);
        if ($to === null) {
            continue;
        }
        foreach (scandir($from) ?: [] as $entry) {
            $source = $from . DIRECTORY_SEPARATOR . $entry;
            $target = $to . DIRECTORY_SEPARATOR . $entry;
            if (is_file($source) && !is_file($target)) {
                @copy($source, $target);
            }
        }
    }
}

/**
 * Login page hook: keep the generated stylesheet and GLPI's native logo
 * files in line with the saved branding (a GLPI upgrade replaces the latter).
 * Both checks are no-ops unless something actually changed.
 */
function plugin_brandkit_display_login()
{
    brandkit_sync_native_assets();
    brandkit_write_login_css();

    // SSO mode: title above the SSO buttons and the local access link.
    echo brandkit_render_sso_login_extras();
}
