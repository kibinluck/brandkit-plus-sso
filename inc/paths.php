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
 * Environment and path helpers shared by every Brandkit entry point.
 * This is the single place that knows where GLPI keeps its files, so the
 * rest of the plugin never has to guess paths on its own.
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

if (!defined('PLUGIN_BRANDKIT_KEY')) {
    define('PLUGIN_BRANDKIT_KEY', 'brandkit');
}

/**
 * Whether the running GLPI is 11.x or later.
 */
function brandkit_is_glpi11(): bool
{
    if (defined('GLPI_VERSION')) {
        return version_compare(GLPI_VERSION, '11.0.0', '>=');
    }

    $root = rtrim(GLPI_ROOT, '/\\');
    $publicPics = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'pics';
    $legacyPics = $root . DIRECTORY_SEPARATOR . 'pics';

    return is_dir($publicPics) && !is_dir($legacyPics);
}

/**
 * GLPI base URL path (e.g. "" or "/glpi"), always without trailing slash.
 */
function brandkit_get_glpi_root_doc(): string
{
    $root = '';
    $cfg = $GLOBALS['CFG_GLPI'] ?? null;
    if (is_array($cfg) && isset($cfg['root_doc']) && is_string($cfg['root_doc'])) {
        $root = rtrim($cfg['root_doc'], '/');
    }
    if ($root !== '' && $root[0] !== '/') {
        $root = '/' . $root;
    }

    return $root;
}

/**
 * Web path of the plugin directory.
 *
 * GLPI 11 serves every plugin from /plugins/<key> and deprecates
 * Plugin::getWebDir(); GLPI 10 may also serve it from /marketplace/<key>.
 *
 * @param bool $full Prefix with GLPI root_doc (what links and redirects need).
 */
function brandkit_get_plugin_web_dir(bool $full = true): string
{
    $relative = '/plugins/' . PLUGIN_BRANDKIT_KEY;
    if (!brandkit_is_glpi11() && class_exists('Plugin') && method_exists('Plugin', 'getWebDir')) {
        $dir = Plugin::getWebDir(PLUGIN_BRANDKIT_KEY, false);
        if (is_string($dir) && $dir !== '') {
            $relative = '/' . ltrim(str_replace('\\', '/', $dir), '/');
        }
    }

    return $full ? brandkit_get_glpi_root_doc() . $relative : $relative;
}

function brandkit_get_front_web_base(): string
{
    return brandkit_get_glpi_root_doc() . '/front';
}

function brandkit_get_pics_web_base(): string
{
    return brandkit_get_glpi_root_doc() . '/pics';
}

/**
 * Filesystem directory holding GLPI's public pictures (pics/ on GLPI 10,
 * public/pics/ on GLPI 11).
 */
function brandkit_get_pics_dir(): ?string
{
    if (defined('GLPI_PICS_DIR')) {
        return rtrim(GLPI_PICS_DIR, '/\\');
    }

    $root = rtrim(GLPI_ROOT, '/\\');
    $publicPics = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'pics';
    $legacyPics = $root . DIRECTORY_SEPARATOR . 'pics';
    $candidates = brandkit_is_glpi11() ? [$publicPics, $legacyPics] : [$legacyPics, $publicPics];

    foreach ($candidates as $candidate) {
        if (is_dir($candidate)) {
            return $candidate;
        }
    }

    return null;
}

/**
 * Directory of GLPI's native logo files (pics/logos), or null when missing.
 */
function brandkit_get_pics_logos_dir(): ?string
{
    $picsDir = brandkit_get_pics_dir();
    if ($picsDir === null) {
        return null;
    }

    $logosDir = $picsDir . DIRECTORY_SEPARATOR . 'logos';
    if (is_dir($logosDir)) {
        return $logosDir;
    }

    // Some packagers flatten GLPI_PICS_DIR; keep the original fallback.
    return defined('GLPI_PICS_DIR') ? $picsDir : null;
}

/**
 * Base directory GLPI gives plugins for their files (files/_plugins by default).
 *
 * GLPI_PLUGIN_DOC_DIR is authoritative: it is what pluginimage.send.php and
 * css.php trust. GLPI_VAR_DIR/_plugins is only a fallback for odd setups.
 */
function brandkit_get_plugin_doc_base(): ?string
{
    if (defined('GLPI_PLUGIN_DOC_DIR')) {
        return rtrim(GLPI_PLUGIN_DOC_DIR, '/\\');
    }
    if (defined('GLPI_VAR_DIR')) {
        return rtrim(GLPI_VAR_DIR, '/\\') . DIRECTORY_SEPARATOR . '_plugins';
    }

    return null;
}

/**
 * Where releases up to 0.1.7 stored files when GLPI_PLUGIN_DOC_DIR was
 * customized (they always used GLPI_VAR_DIR/_plugins). Null when it is the
 * same place as the current storage directory or does not exist.
 */
function brandkit_get_legacy_storage_dir(): ?string
{
    if (!defined('GLPI_VAR_DIR')) {
        return null;
    }
    $legacy = rtrim(GLPI_VAR_DIR, '/\\') . DIRECTORY_SEPARATOR . '_plugins' . DIRECTORY_SEPARATOR . PLUGIN_BRANDKIT_KEY;
    $base = brandkit_get_plugin_doc_base();
    $current = $base !== null ? $base . DIRECTORY_SEPARATOR . PLUGIN_BRANDKIT_KEY : null;
    if (!is_dir($legacy)) {
        return null;
    }
    if ($current !== null && is_dir($current) && realpath($current) === realpath($legacy)) {
        return null;
    }

    return $legacy;
}

/**
 * Plugin storage directory (files/_plugins/brandkit).
 *
 * @param bool $create Create it when missing.
 */
function brandkit_get_plugin_storage_dir(bool $create = true): ?string
{
    $base = brandkit_get_plugin_doc_base();
    if ($base === null) {
        return null;
    }

    $dir = $base . DIRECTORY_SEPARATOR . PLUGIN_BRANDKIT_KEY;
    if (is_dir($dir)) {
        return $dir;
    }
    if ($create && (@mkdir($dir, 0755, true) || is_dir($dir))) {
        return $dir;
    }

    return null;
}

function brandkit_get_plugin_storage_dir_existing(): ?string
{
    return brandkit_get_plugin_storage_dir(false);
}

/**
 * Sub-directory of the plugin storage (e.g. "logos", "originals").
 */
function brandkit_get_storage_subdir(string $name, bool $create = true): ?string
{
    $base = brandkit_get_plugin_storage_dir($create);
    if ($base === null) {
        return null;
    }

    $dir = $base . DIRECTORY_SEPARATOR . $name;
    if (is_dir($dir)) {
        return $dir;
    }
    if ($create && (@mkdir($dir, 0755, true) || is_dir($dir))) {
        return $dir;
    }

    return null;
}

/**
 * Relative path between two directories ("../x/y").
 */
function brandkit_relative_path(string $from, string $to): string
{
    $from = rtrim(str_replace('\\', '/', $from), '/');
    $to = rtrim(str_replace('\\', '/', $to), '/');

    $fromParts = $from === '' ? [] : explode('/', $from);
    $toParts = $to === '' ? [] : explode('/', $to);

    while (!empty($fromParts) && !empty($toParts) && $fromParts[0] === $toParts[0]) {
        array_shift($fromParts);
        array_shift($toParts);
    }

    $rel = str_repeat('../', count($fromParts)) . implode('/', $toParts);
    return $rel === '' ? '.' : $rel;
}

/**
 * Write a file atomically, and only when its content actually changes.
 *
 * Keeping the mtime stable matters: it is used as a cache-busting version
 * in URLs, so rewriting identical content would defeat browser caching.
 */
function brandkit_write_file_if_changed(string $path, string $content): bool
{
    if (is_file($path) && @file_get_contents($path) === $content) {
        return true;
    }

    $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }

    return true;
}

/**
 * Recursively delete a directory the plugin created.
 */
function brandkit_remove_dir(string $dir): void
{
    if (!is_dir($dir) || is_link($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        if ($item->isDir() && !$item->isLink()) {
            @rmdir($item->getPathname());
        } else {
            @unlink($item->getPathname());
        }
    }
    @rmdir($dir);
}

/**
 * URL of a static file shipped in the plugin's public/ directory.
 *
 * GLPI 11 only serves plugin static files from public/ and expects URLs
 * without that segment; GLPI 10 lets the web server serve the plugin
 * directory as is.
 */
function brandkit_get_public_asset_url(string $relativePath, bool $full = true): string
{
    $relativePath = ltrim($relativePath, '/');
    $prefix = brandkit_is_glpi11() ? '/' : '/public/';

    return brandkit_get_plugin_web_dir($full) . $prefix . $relativePath;
}

/**
 * Path of a public/ file as the add_javascript / add_css hooks expect it
 * (relative to the plugin directory on GLPI 10, to public/ on GLPI 11).
 */
function brandkit_get_public_hook_path(string $relativePath): string
{
    $relativePath = ltrim($relativePath, '/');

    return brandkit_is_glpi11() ? $relativePath : 'public/' . $relativePath;
}
