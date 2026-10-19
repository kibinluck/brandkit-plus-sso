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

declare(strict_types=1);

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

require_once __DIR__ . '/paths.php';

/**
 * Menu entry and rights for the Brandkit configuration page.
 */
class PluginBrandkitConfig extends CommonGLPI
{
    public static $rightname = 'config';

    public static function getTypeName($nb = 0)
    {
        return __('Brandkit', 'brandkit');
    }

    public static function canView(): bool
    {
        return (bool) Session::haveRight('config', UPDATE);
    }

    public static function canCreate(): bool
    {
        return self::canView();
    }

    public static function getMenuName()
    {
        return __('Brandkit', 'brandkit');
    }

    public static function getSearchURL($full = true)
    {
        return brandkit_get_plugin_web_dir((bool) $full) . '/front/index.php';
    }

    public static function getMenuContent()
    {
        if (!self::canView()) {
            return false;
        }

        $menu = [
            'title' => self::getTypeName(1),
            'page' => brandkit_get_plugin_web_dir(false) . '/front/index.php',
            'icon' => 'ti ti-paint',
        ];

        return $menu;
    }
}
