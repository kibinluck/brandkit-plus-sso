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

include_once '../../../inc/includes.php';
require_once __DIR__ . '/../inc/login.css.php';

header('Content-Type: text/css; charset=UTF-8');

/**
 * Logged-in pages: header logos and their size, as CSS variables.
 */
$logoVariables = [
    'logo-GLPI-100-white.png' => '--glpi-logo-light',
    'logo-G-100-white.png' => '--glpi-logo-light-reduced',
    'logo-GLPI-100-black.png' => '--glpi-logo-dark',
    'logo-G-100-black.png' => '--glpi-logo-dark-reduced',
];

$scaleConfig = Config::getConfigurationValues('plugin:brandkit', [
    'logo_header_scale',
    'logo_reduced_scale',
    // Names used before 0.1.6.
    'logo_header_light_scale',
    'logo_reduced_light_scale',
    'logo_header_dark_scale',
    'logo_reduced_dark_scale',
]);
$scaleConfig = array_filter($scaleConfig, static fn($value) => $value !== null && $value !== '');
$logoHeaderScale = brandkit_clamp_int(
    $scaleConfig['logo_header_scale']
        ?? $scaleConfig['logo_header_light_scale']
        ?? $scaleConfig['logo_header_dark_scale']
        ?? null,
    40,
    200,
    100
);
$logoReducedScale = brandkit_clamp_int(
    $scaleConfig['logo_reduced_scale']
        ?? $scaleConfig['logo_reduced_light_scale']
        ?? $scaleConfig['logo_reduced_dark_scale']
        ?? null,
    40,
    200,
    100
);

$publicLogosDir = brandkit_get_pics_logos_dir();
$picsBase = brandkit_get_pics_web_base();
$pluginImageBase = brandkit_get_front_web_base() . '/pluginimage.send.php?plugin=brandkit&folder=logos&name=';

$css = ":root {\n";
foreach ($logoVariables as $file => $variable) {
    // GLPI's own file (Brandkit copies uploads over it) is served statically
    // and cached; the version parameter makes browsers notice a new upload.
    $nativePath = $publicLogosDir !== null ? $publicLogosDir . DIRECTORY_SEPARATOR . $file : null;
    if ($nativePath !== null && is_file($nativePath)) {
        $css .= "  {$variable}: url(\"{$picsBase}/logos/{$file}?v=" . filemtime($nativePath) . "\") !important;\n";
        continue;
    }

    $storedPath = brandkit_get_stored_logo_path($file);
    if ($storedPath !== null) {
        $css .= "  {$variable}: url(\"{$pluginImageBase}{$file}&v=" . filemtime($storedPath) . "\") !important;\n";
    }
}
$css .= <<<CSS
  --brandkit-logo-scale-light: {$logoHeaderScale};
  --brandkit-logo-scale-light-reduced: {$logoReducedScale};
  --brandkit-logo-scale-dark: {$logoHeaderScale};
  --brandkit-logo-scale-dark-reduced: {$logoReducedScale};
  --brandkit-logo-scale: var(--brandkit-logo-scale-light);
  --brandkit-logo-scale-reduced: var(--brandkit-logo-scale-light-reduced);
  --brandkit-logo-image: var(--glpi-logo-light, var(--logo));
  --brandkit-logo-image-reduced: var(--glpi-logo-light-reduced, var(--logo));
}
:root[data-glpi-theme-dark="1"],
:root[glpi-theme-dark="1"] {
  --brandkit-logo-scale: var(--brandkit-logo-scale-dark);
  --brandkit-logo-scale-reduced: var(--brandkit-logo-scale-dark-reduced);
  --brandkit-logo-image: var(--glpi-logo-dark, var(--logo));
  --brandkit-logo-image-reduced: var(--glpi-logo-dark-reduced, var(--logo));
}
body.navbar-collapsed .navbar-brand {
  display: flex;
  justify-content: center;
  align-items: center;
}
body.navbar-collapsed .navbar-brand .glpi-logo {
  background-image: var(--brandkit-logo-image-reduced);
  background-position: center;
  width: calc(180px * var(--brandkit-logo-scale-reduced) / 100);
  height: calc(48px * var(--brandkit-logo-scale-reduced) / 100);
}
@media (max-width: 991.98px) {
  body.navbar-collapsed .navbar-brand .glpi-logo {
    background-image: var(--brandkit-logo-image);
    width: calc(180px * var(--brandkit-logo-scale) / 100);
    height: calc(48px * var(--brandkit-logo-scale) / 100);
  }
}
.navbar-brand {
  display: flex;
  align-items: center;
  justify-content: center;
}
.navbar-brand .glpi-logo {
  background-image: var(--brandkit-logo-image);
  background-size: contain;
  background-repeat: no-repeat;
  background-position: center;
  width: calc(180px * var(--brandkit-logo-scale) / 100);
  height: calc(48px * var(--brandkit-logo-scale) / 100);
  max-width: 100%;
  max-height: 100%;
}

CSS;

echo $css;
