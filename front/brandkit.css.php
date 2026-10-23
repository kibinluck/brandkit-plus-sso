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

// Public stylesheet: it only exposes the logos already shown on the login
// page. GLPI 11 serves it stateless and without checks, as registered in
// setup.php (brandkit_register_public_stylesheet()); GLPI 10 reads this
// variable (its default for plugins is the same).
$SECURITY_STRATEGY = 'no_check';

// GLPI 10 runs this script directly; GLPI 11 boots its kernel first (and the
// plugin may live outside GLPI's tree, e.g. /var/glpi/marketplace in Docker).
if (!defined('GLPI_ROOT')) {
    include_once '../../../inc/includes.php';
}
require_once __DIR__ . '/../inc/login.css.php';

// Nothing here needs the session. GLPI 11 does not open it at all; GLPI 10
// does: drop it WITHOUT writing it back. session_write_close() would save
// the copy read when this request started, and a page loads this file in
// parallel with its own requests: with a session handler that does not lock
// (Redis by default), that stale copy could overwrite the session the page
// has just saved, e.g. the CSRF token of the password form on screen.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_abort();
}

header('Content-Type: text/css; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

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
    $nativePath = $publicLogosDir !== null ? $publicLogosDir . DIRECTORY_SEPARATOR . $file : null;
    $storedPath = brandkit_get_stored_logo_path($file);
    $nativeExists = $nativePath !== null && is_file($nativePath);

    // Serve the upload from the plugin storage when GLPI's own file could not
    // be replaced (pics/ not writable, GLPI upgraded, new Docker container...).
    if ($storedPath !== null && (!$nativeExists || !brandkit_files_are_identical($storedPath, $nativePath))) {
        $css .= "  {$variable}: url(\"{$pluginImageBase}{$file}&v=" . filemtime($storedPath) . "\") !important;\n";
        continue;
    }

    // Otherwise GLPI's file (with the upload copied over it) is served
    // statically and cached; the version makes browsers notice a new upload.
    if ($nativeExists) {
        $css .= "  {$variable}: url(\"{$picsBase}/logos/{$file}?v=" . filemtime($nativePath) . "\") !important;\n";
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

// The URL only changes with the plugin version, not when a logo is uploaded:
// browsers keep a copy but must revalidate it, and get a 304 when nothing
// changed. (Without a session, nothing else sets cache headers on GLPI 11.)
$etag = '"' . md5($css) . '"';
header_remove('Pragma');
header_remove('Expires');
header('Cache-Control: no-cache');
header('ETag: ' . $etag);
$ifNoneMatch = (string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '');
if ($ifNoneMatch !== '' && in_array($etag, array_map('trim', explode(',', $ifNoneMatch)), true)) {
    http_response_code(304);
    return;
}

echo $css;
