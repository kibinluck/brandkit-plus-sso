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
 * Logo, favicon and wallpaper file handling: where GLPI keeps its native
 * assets, backing them up, restoring them, and image processing.
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

require_once __DIR__ . '/paths.php';

/**
 * Logo/favicon file names Brandkit manages, mapped to where they are used.
 * The names are GLPI's own native file names in pics/logos (and pics/).
 *
 * @return array<string, string> file name => 'header' | 'login' | 'favicon'
 */
function brandkit_get_logo_target_kinds(): array
{
    return [
        'logo-GLPI-100-white.png' => 'header',
        'logo-G-100-white.png' => 'header',
        'logo-GLPI-100-black.png' => 'header',
        'logo-G-100-black.png' => 'header',
        'logo-GLPI-250-black.png' => 'login',
        'logo-GLPI-250-white.png' => 'login',
        'favicon.ico' => 'favicon',
    ];
}

/**
 * @return string[]
 */
function brandkit_get_logo_targets(): array
{
    return array_keys(brandkit_get_logo_target_kinds());
}

function brandkit_is_logo_target(string $targetFile): bool
{
    return isset(brandkit_get_logo_target_kinds()[$targetFile]);
}

/**
 * Pristine copy of a GLPI asset shipped with the plugin (pics/), used as a
 * last-resort source when restoring.
 */
function brandkit_get_plugin_original_logo_path(string $targetFile): ?string
{
    if (!brandkit_is_logo_target($targetFile)) {
        return null;
    }
    $path = dirname(__DIR__) . '/pics/' . $targetFile;
    return is_file($path) ? $path : null;
}

/**
 * Path of GLPI's own file for a target (the file Brandkit overwrites so the
 * logo also shows on pages where plugin CSS cannot reach, like the login
 * page on GLPI 11 and the browser favicon).
 */
function brandkit_get_native_asset_path(string $targetFile): ?string
{
    $kind = brandkit_get_logo_target_kinds()[$targetFile] ?? null;
    if ($kind === null) {
        return null;
    }

    if ($kind === 'favicon') {
        $picsDir = brandkit_get_pics_dir();
        return $picsDir !== null ? $picsDir . DIRECTORY_SEPARATOR . 'favicon.ico' : null;
    }

    $logoDir = brandkit_get_pics_logos_dir();
    return $logoDir !== null ? $logoDir . DIRECTORY_SEPARATOR . $targetFile : null;
}

/**
 * Kept for backward compatibility with forks that call it.
 */
function brandkit_get_native_login_logo_path(string $targetFile): ?string
{
    return (brandkit_get_logo_target_kinds()[$targetFile] ?? null) === 'login'
        ? brandkit_get_native_asset_path($targetFile)
        : null;
}

function brandkit_get_native_header_logo_path(string $targetFile): ?string
{
    return (brandkit_get_logo_target_kinds()[$targetFile] ?? null) === 'header'
        ? brandkit_get_native_asset_path($targetFile)
        : null;
}

function brandkit_get_native_favicon_path(): ?string
{
    return brandkit_get_native_asset_path('favicon.ico');
}

/**
 * The uploaded custom file for a target, as stored by the plugin.
 */
function brandkit_get_stored_logo_path(string $targetFile, bool $mustExist = true): ?string
{
    if (!brandkit_is_logo_target($targetFile)) {
        return null;
    }
    $dir = brandkit_get_storage_subdir('logos', !$mustExist);
    if ($dir === null) {
        return null;
    }
    $path = $dir . DIRECTORY_SEPARATOR . $targetFile;

    return !$mustExist || is_file($path) ? $path : null;
}

function brandkit_files_are_identical(string $a, string $b): bool
{
    return is_file($a) && is_file($b)
        && filesize($a) === filesize($b)
        && md5_file($a) === md5_file($b);
}

/**
 * Save GLPI's own version of an asset once, before Brandkit overwrites it,
 * so restoring gives back exactly what this GLPI installation shipped.
 */
function brandkit_backup_native_asset(string $targetFile): void
{
    $native = brandkit_get_native_asset_path($targetFile);
    if ($native === null || !is_file($native)) {
        return;
    }

    $backupDir = brandkit_get_storage_subdir('originals');
    if ($backupDir === null) {
        return;
    }
    $backup = $backupDir . DIRECTORY_SEPARATOR . $targetFile;
    if (is_file($backup)) {
        return;
    }

    // An older release may already have replaced the native file without a
    // backup; never record a custom logo as the "original".
    $stored = brandkit_get_stored_logo_path($targetFile);
    if ($stored !== null && brandkit_files_are_identical($stored, $native)) {
        return;
    }

    @copy($native, $backup);
}

/**
 * Copy the stored custom file over GLPI's native one.
 *
 * Callers back the native file up first (brandkit_backup_native_asset())
 * while the stored copy still is the previous one: that is the only moment
 * a previous custom logo can be told apart from GLPI's own file.
 */
function brandkit_install_native_asset(string $targetFile): bool
{
    $stored = brandkit_get_stored_logo_path($targetFile);
    $native = brandkit_get_native_asset_path($targetFile);
    if ($stored === null || $native === null) {
        return false;
    }
    if (brandkit_files_are_identical($stored, $native)) {
        return true;
    }

    return @copy($stored, $native);
}

/**
 * Re-apply custom files that a GLPI upgrade (or anything else) replaced.
 * Cheap enough to run on each login page view: a few small file hashes.
 */
function brandkit_sync_native_assets(): void
{
    foreach (brandkit_get_logo_targets() as $targetFile) {
        if (brandkit_get_stored_logo_path($targetFile) !== null) {
            // The native file differs from our upload, so it is GLPI's own
            // (typically freshly restored by a GLPI upgrade): keep a copy.
            brandkit_backup_native_asset($targetFile);
            brandkit_install_native_asset($targetFile);
        }
    }
}

/**
 * Put GLPI's own asset back in place.
 *
 * @return string[] error codes ('favicon_restore_failed', 'login_restore_failed',
 *                  'login_original_missing', 'header_restore_failed', 'header_original_missing')
 */
function brandkit_restore_original_logo_assets(string $targetFile, ?string $logosDir = null): array
{
    $kind = brandkit_get_logo_target_kinds()[$targetFile] ?? null;
    if ($kind === null) {
        return [];
    }

    $logosDir = $logosDir ?? brandkit_get_storage_subdir('logos', false);
    if ($logosDir !== null) {
        $storedLogo = $logosDir . DIRECTORY_SEPARATOR . $targetFile;
        if (is_file($storedLogo)) {
            @unlink($storedLogo);
        }
    }

    $native = brandkit_get_native_asset_path($targetFile);
    if ($native === null) {
        return [];
    }

    $originalsDir = brandkit_get_storage_subdir('originals', false);
    $candidates = [
        $originalsDir !== null ? $originalsDir . DIRECTORY_SEPARATOR . $targetFile : null,
        // Releases up to 0.1.7 only backed up the favicon, as logos/favicon.ico.bak.
        ($kind === 'favicon' && $logosDir !== null) ? $logosDir . DIRECTORY_SEPARATOR . 'favicon.ico.bak' : null,
        brandkit_get_plugin_original_logo_path($targetFile),
    ];
    $source = null;
    foreach ($candidates as $candidate) {
        if ($candidate !== null && is_file($candidate)) {
            $source = $candidate;
            break;
        }
    }

    if ($source === null) {
        return $kind === 'favicon' ? ['favicon_restore_failed'] : [$kind . '_original_missing'];
    }
    if (!@copy($source, $native)) {
        return [$kind . '_restore_failed'];
    }

    return [];
}

function brandkit_restore_original_glpi_assets(): void
{
    foreach (brandkit_get_logo_targets() as $targetFile) {
        brandkit_restore_original_logo_assets($targetFile);
    }
}

/**
 * Public directory for login wallpapers (pics/brandkit-wallpapers).
 *
 * It has to live under GLPI's public pics: on GLPI 11 nothing outside
 * public/ can be fetched anonymously, and the login page is anonymous.
 */
function brandkit_get_wallpaper_public_dir(bool $create = true): ?string
{
    $picsDir = brandkit_get_pics_dir();
    if ($picsDir === null) {
        return null;
    }

    $dir = $picsDir . DIRECTORY_SEPARATOR . 'brandkit-wallpapers';
    if (is_dir($dir)) {
        return $dir;
    }
    if ($create && (@mkdir($dir, 0755, true) || is_dir($dir))) {
        return $dir;
    }

    return null;
}

function brandkit_get_wallpaper_image_url(string $name, ?int $version = null): string
{
    $url = brandkit_get_pics_web_base() . '/brandkit-wallpapers/' . rawurlencode($name);
    if ($version !== null) {
        $url .= '?v=' . $version;
    }

    return $url;
}

function brandkit_get_wallpaper_storage_dir(): ?string
{
    $dir = brandkit_get_wallpaper_public_dir();
    if ($dir !== null) {
        return $dir;
    }

    return brandkit_get_storage_subdir('wallpaper', false);
}

function brandkit_is_safe_filename(string $filename): bool
{
    return $filename !== ''
        && basename($filename) === $filename
        && preg_match('/^[A-Za-z0-9._-]+$/', $filename) === 1
        && $filename[0] !== '.';
}

/**
 * Absolute path of an uploaded wallpaper, (re)publishing it from the master
 * copy in the plugin storage when the public copy is missing.
 */
function brandkit_get_wallpaper_path(string $filename): ?string
{
    if (!brandkit_is_safe_filename($filename)) {
        return null;
    }

    $dir = brandkit_get_wallpaper_storage_dir();
    if ($dir !== null) {
        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            return $path;
        }
    }

    $legacyDir = brandkit_get_storage_subdir('wallpaper', false);
    if ($legacyDir === null) {
        return null;
    }
    $legacyPath = $legacyDir . DIRECTORY_SEPARATOR . $filename;
    if (!is_file($legacyPath)) {
        return null;
    }

    $publicDir = brandkit_get_wallpaper_public_dir();
    if ($publicDir !== null) {
        $publicPath = $publicDir . DIRECTORY_SEPARATOR . $filename;
        if (is_file($publicPath) || @copy($legacyPath, $publicPath)) {
            return $publicPath;
        }
    }

    return $legacyPath;
}

/**
 * Find the stored wallpaper even if its extension changed since it was saved.
 */
function brandkit_resolve_wallpaper_filename(?string $filename): string
{
    if (!$filename) {
        return '';
    }

    $candidates = [$filename];
    $base = preg_replace('/\.[^.]+$/', '', $filename);
    foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
        $candidates[] = $base . '.' . $ext;
    }

    foreach (array_unique($candidates) as $candidate) {
        if (brandkit_get_wallpaper_path($candidate) !== null) {
            return $candidate;
        }
    }

    return '';
}

/**
 * Remove every stored variant of the uploaded login wallpaper.
 */
function brandkit_delete_uploaded_wallpaper_variants(string $baseName = 'wallpaperLogin'): void
{
    $dirs = array_filter([
        brandkit_get_wallpaper_public_dir(false),
        brandkit_get_storage_subdir('wallpaper', false),
    ]);
    foreach ($dirs as $dir) {
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $path = $dir . DIRECTORY_SEPARATOR . $baseName . '.' . $ext;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}

function brandkit_get_wallpaper_presets_dir(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'pics' . DIRECTORY_SEPARATOR . 'wallpapers';
}

/**
 * @return string[] preset file names, naturally sorted
 */
function brandkit_get_wallpaper_presets(): array
{
    static $presets = null;
    if ($presets !== null) {
        return $presets;
    }

    $presets = [];
    $baseDir = brandkit_get_wallpaper_presets_dir();
    if (!is_dir($baseDir)) {
        return $presets;
    }

    foreach (scandir($baseDir) ?: [] as $entry) {
        if (!brandkit_is_safe_filename($entry) || !is_file($baseDir . DIRECTORY_SEPARATOR . $entry)) {
            continue;
        }
        $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $presets[] = $entry;
        }
    }

    sort($presets, SORT_NATURAL | SORT_FLAG_CASE);
    return $presets;
}

function brandkit_get_wallpaper_preset_default(array $presets): string
{
    $preferred = 'top-view-of-office.png';
    if (in_array($preferred, $presets, true)) {
        return $preferred;
    }

    return $presets[0] ?? '';
}

/**
 * Publish an optimized copy of a preset in pics/brandkit-wallpapers.
 * Photos become progressive JPEG (~10x lighter than the bundled PNG);
 * images with transparency stay PNG.
 *
 * @return string|null Published file name.
 */
function brandkit_publish_wallpaper_preset(string $presetFile, string $publicDir): ?string
{
    $presetPath = brandkit_get_wallpaper_presets_dir() . DIRECTORY_SEPARATOR . $presetFile;
    $presetMtime = (int) filemtime($presetPath);
    $jpegName = pathinfo($presetFile, PATHINFO_FILENAME) . '.jpg';

    foreach ([$jpegName, $presetFile] as $candidate) {
        $candidatePath = $publicDir . DIRECTORY_SEPARATOR . $candidate;
        if (is_file($candidatePath) && (int) filemtime($candidatePath) >= $presetMtime) {
            return $candidate;
        }
    }

    // Decoding a full-HD image is costly: never attempt it when the result
    // cannot be saved anyway (this runs on every login page view).
    if (!is_writable($publicDir)) {
        return null;
    }

    $image = brandkit_load_image($presetPath);
    if ($image) {
        $output = brandkit_fit_image($image, 1920, 1080);
        imagedestroy($image);
        if ($output) {
            $transparent = brandkit_image_has_transparency($output);
            $name = $transparent ? $presetFile : $jpegName;
            $saved = brandkit_save_image(
                $output,
                $publicDir . DIRECTORY_SEPARATOR . $name,
                $transparent ? brandkit_guess_mime_from_filename($presetFile) : 'image/jpeg',
                80
            );
            imagedestroy($output);
            if ($saved) {
                return $name;
            }
        }
    }

    return @copy($presetPath, $publicDir . DIRECTORY_SEPARATOR . $presetFile) ? $presetFile : null;
}

/**
 * Public URL for a preset wallpaper.
 *
 * @return array{url: string, webp_url: string, mime: string}
 */
function brandkit_get_wallpaper_preset_assets(string $presetFile): array
{
    if (!in_array($presetFile, brandkit_get_wallpaper_presets(), true)) {
        return ['url' => '', 'webp_url' => '', 'mime' => 'image/jpeg'];
    }

    $publicDir = brandkit_get_wallpaper_public_dir();
    $published = $publicDir !== null ? brandkit_publish_wallpaper_preset($presetFile, $publicDir) : null;
    if ($published !== null) {
        return [
            'url' => brandkit_get_wallpaper_image_url($published, (int) filemtime($publicDir . DIRECTORY_SEPARATOR . $published)),
            'webp_url' => '',
            'mime' => brandkit_guess_mime_from_filename($published),
        ];
    }

    // pics/ is not writable: serve the bundled file from the plugin itself.
    $presetPath = brandkit_get_wallpaper_presets_dir() . DIRECTORY_SEPARATOR . $presetFile;
    return [
        'url' => brandkit_get_public_asset_url('pics/wallpapers/' . rawurlencode($presetFile)) . '?v=' . (int) filemtime($presetPath),
        'webp_url' => '',
        'mime' => brandkit_guess_mime_from_filename($presetFile),
    ];
}

/* ------------------------------------------------------------------------
 * Image processing (GD)
 * --------------------------------------------------------------------- */

/** Largest upload accepted for a logo, in bytes. */
const BRANDKIT_MAX_LOGO_BYTES = 5 * 1024 * 1024;
/** Largest upload accepted for the favicon, in bytes. */
const BRANDKIT_MAX_FAVICON_BYTES = 1024 * 1024;
/** Largest upload accepted for a wallpaper, in bytes. */
const BRANDKIT_MAX_WALLPAPER_BYTES = 15 * 1024 * 1024;
/** Refuse to decode images above this many pixels (decompression bombs). */
const BRANDKIT_MAX_IMAGE_PIXELS = 40000000;

function brandkit_can_process_images(): bool
{
    return extension_loaded('gd')
        && function_exists('imagecreatetruecolor')
        && function_exists('imagecopyresampled');
}

function brandkit_guess_mime_from_filename(string $filename): string
{
    switch (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
        case 'png':
            return 'image/png';
        case 'webp':
            return 'image/webp';
        case 'ico':
            return 'image/x-icon';
        default:
            return 'image/jpeg';
    }
}

function brandkit_get_memory_limit_bytes(): int
{
    $limit = trim((string) ini_get('memory_limit'));
    if ($limit === '' || $limit === '-1') {
        return PHP_INT_MAX;
    }

    $value = (int) $limit;
    switch (strtolower(substr($limit, -1))) {
        case 'g':
            $value *= 1024;
            // no break
        case 'm':
            $value *= 1024;
            // no break
        case 'k':
            $value *= 1024;
    }

    return $value;
}

/**
 * Whether GD can decode an image of this size without exhausting memory
 * (a decoded true-color image takes about 5 bytes per pixel).
 */
function brandkit_image_fits_in_memory(int $width, int $height): bool
{
    $pixels = $width * $height;
    if ($pixels <= 0 || $pixels > BRANDKIT_MAX_IMAGE_PIXELS) {
        return false;
    }

    $needed = (int) ($pixels * 5 * 1.8) + 8 * 1024 * 1024;
    return memory_get_usage() + $needed < brandkit_get_memory_limit_bytes();
}

/**
 * Inspect an image file without decoding it.
 *
 * @return array{width: int, height: int, mime: string}|null
 */
function brandkit_get_image_info(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }
    $info = @getimagesize($path);
    if (!$info || empty($info['mime']) || (int) $info[0] <= 0 || (int) $info[1] <= 0) {
        return null;
    }
    if (!in_array($info['mime'], ['image/png', 'image/jpeg', 'image/webp'], true)) {
        return null;
    }

    return ['width' => (int) $info[0], 'height' => (int) $info[1], 'mime' => $info['mime']];
}

/**
 * Decode a PNG/JPEG/WEBP file with GD after checking its declared size.
 *
 * @return resource|\GdImage|false
 */
function brandkit_load_image(string $path, ?array &$info = null)
{
    $info = brandkit_get_image_info($path);
    if ($info === null || !brandkit_can_process_images()) {
        return false;
    }
    if (!brandkit_image_fits_in_memory($info['width'], $info['height'])) {
        return false;
    }

    $loaders = [
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png' => 'imagecreatefrompng',
        'image/webp' => 'imagecreatefromwebp',
    ];
    $loader = $loaders[$info['mime']];
    if (!function_exists($loader)) {
        return false;
    }

    return @$loader($path);
}

/**
 * Scale an image down to fit in a box (never up) into a new true-color
 * canvas that keeps transparency.
 *
 * @param resource|\GdImage $image
 * @return resource|\GdImage|false
 */
function brandkit_fit_image($image, int $maxWidth, int $maxHeight)
{
    $width = imagesx($image);
    $height = imagesy($image);
    $ratio = 1;
    if ($maxWidth > 0 && $maxHeight > 0) {
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
    }
    $targetWidth = max(1, (int) round($width * $ratio));
    $targetHeight = max(1, (int) round($height * $ratio));

    $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
    if (!$canvas) {
        return false;
    }
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

    if (!imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height)) {
        imagedestroy($canvas);
        return false;
    }

    return $canvas;
}

/**
 * @param resource|\GdImage $image
 */
function brandkit_save_image($image, string $destination, string $mime, int $quality = 82): bool
{
    $quality = max(40, min(95, $quality));
    switch ($mime) {
        case 'image/png':
            return @imagepng($image, $destination, 6);
        case 'image/webp':
            return function_exists('imagewebp') && @imagewebp($image, $destination, $quality);
        case 'image/jpeg':
            imageinterlace($image, 1);
            return @imagejpeg($image, $destination, $quality);
    }

    return false;
}

/**
 * Re-encode an image file: decoding and encoding again drops metadata and
 * anything appended to the file, so only real pixels get published.
 *
 * @param string|null $mime Output type; null keeps the source type.
 */
function brandkit_reencode_image(
    string $source,
    string $destination,
    ?string $mime = null,
    int $maxWidth = 0,
    int $maxHeight = 0,
    int $quality = 82
): bool {
    $image = brandkit_load_image($source, $info);
    if (!$image) {
        return false;
    }

    $output = brandkit_fit_image($image, $maxWidth, $maxHeight);
    imagedestroy($image);
    if (!$output) {
        return false;
    }

    $tmp = $destination . '.' . bin2hex(random_bytes(4)) . '.tmp';
    $saved = brandkit_save_image($output, $tmp, $mime ?? $info['mime'], $quality);
    imagedestroy($output);

    if (!$saved || !@rename($tmp, $destination)) {
        @unlink($tmp);
        return false;
    }

    return true;
}

/**
 * Convert any supported image to PNG.
 */
function brandkit_convert_image_to_png(string $source, string $destination): bool
{
    return brandkit_reencode_image($source, $destination, 'image/png');
}

/**
 * Downscale an image to fit in a box, keeping its format.
 */
function brandkit_resize_image(string $source, string $destination, int $maxWidth = 1920, int $maxHeight = 1080, int $quality = 80): bool
{
    return brandkit_reencode_image($source, $destination, null, $maxWidth, $maxHeight, $quality);
}

/**
 * Basic checks on a $_FILES entry.
 *
 * @return string|null Translated error message, or null when the upload is usable.
 */
function brandkit_check_upload(?array $file, array $allowedExtensions, int $maxBytes): ?string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    switch ($error) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return __('Upload failed. The file is larger than the allowed size.', 'brandkit');
        case UPLOAD_ERR_PARTIAL:
            return __('Upload failed because the file was only partially uploaded.', 'brandkit');
        case UPLOAD_ERR_NO_FILE:
            return __('Please choose an image file to upload.', 'brandkit');
        case UPLOAD_ERR_NO_TMP_DIR:
            return __('Upload failed because the temporary upload folder is missing on the server.', 'brandkit');
        case UPLOAD_ERR_CANT_WRITE:
            return __('Upload failed because the server could not write the temporary file.', 'brandkit');
        case UPLOAD_ERR_EXTENSION:
            return __('Upload was blocked by a PHP extension on the server.', 'brandkit');
        default:
            return sprintf(__('Upload failed due to an unexpected error (code %d).', 'brandkit'), $error);
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return __('The upload could not be validated.', 'brandkit');
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return in_array('ico', $allowedExtensions, true)
            ? __('Favicon must be an ICO file.', 'brandkit')
            : __('Allowed formats: PNG, JPG, JPEG, WEBP.', 'brandkit');
    }

    $size = (int) @filesize($tmpName);
    if ($size <= 0 || $size > $maxBytes) {
        return sprintf(
            __('Upload failed. The file is larger than the allowed size (%s).', 'brandkit'),
            sprintf('%d MB', (int) ceil($maxBytes / 1048576))
        );
    }

    return null;
}

/**
 * Whether a file is a real Windows icon (checks the ICONDIR header).
 */
function brandkit_is_ico_file(string $path): bool
{
    $handle = @fopen($path, 'rb');
    if (!$handle) {
        return false;
    }
    $header = fread($handle, 6);
    fclose($handle);
    if ($header === false || strlen($header) !== 6) {
        return false;
    }

    $fields = unpack('vreserved/vtype/vcount', $header);
    return $fields !== false
        && $fields['reserved'] === 0
        && $fields['type'] === 1
        && $fields['count'] > 0
        && $fields['count'] <= 64;
}

/**
 * Whether an image has any visible transparency (sampled, so it stays fast
 * on full-HD wallpapers).
 *
 * @param resource|\GdImage $image
 */
function brandkit_image_has_transparency($image): bool
{
    if (!imageistruecolor($image)) {
        return imagecolortransparent($image) >= 0;
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $step = max(1, (int) floor(min($width, $height) / 200));
    for ($y = 0; $y < $height; $y += $step) {
        for ($x = 0; $x < $width; $x += $step) {
            if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) > 0) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Validate, optimize and publish an uploaded login wallpaper.
 *
 * Photos are stored as progressive JPEG (a full-HD PNG photo weighs several
 * MB on every login page view); images with transparency stay PNG.
 *
 * @return array{0: string|null, 1: string|null} [stored file name, error message]
 */
function brandkit_store_uploaded_wallpaper(?array $file, string $baseName = 'wallpaperLogin'): array
{
    $error = brandkit_check_upload($file, ['png', 'jpg', 'jpeg', 'webp'], BRANDKIT_MAX_WALLPAPER_BYTES);
    if ($error !== null) {
        return [null, $error];
    }

    $publicDir = brandkit_get_wallpaper_public_dir();
    if ($publicDir === null) {
        return [null, __('Unable to create wallpaper directory.', 'brandkit')];
    }

    $image = brandkit_load_image($file['tmp_name'], $info);
    if (!$image) {
        return [null, $info === null
            ? __('The uploaded file is not a valid image.', 'brandkit')
            : __('Unable to process the uploaded wallpaper. The image is too large to be processed.', 'brandkit')];
    }

    $output = brandkit_fit_image($image, 1920, 1080);
    imagedestroy($image);
    if (!$output) {
        return [null, __('Unable to process the uploaded wallpaper. Ensure the file is a valid image.', 'brandkit')];
    }

    $mime = brandkit_image_has_transparency($output) ? 'image/png' : 'image/jpeg';
    $filename = $baseName . ($mime === 'image/png' ? '.png' : '.jpg');
    $tmp = $publicDir . DIRECTORY_SEPARATOR . $filename . '.' . bin2hex(random_bytes(4)) . '.tmp';
    $saved = brandkit_save_image($output, $tmp, $mime, 82);
    imagedestroy($output);
    if (!$saved) {
        @unlink($tmp);
        return [null, __('Unable to save the uploaded wallpaper. Check file permissions.', 'brandkit')];
    }

    brandkit_delete_uploaded_wallpaper_variants($baseName);
    $publicPath = $publicDir . DIRECTORY_SEPARATOR . $filename;
    if (!@rename($tmp, $publicPath)) {
        @unlink($tmp);
        return [null, __('Unable to save the uploaded wallpaper. Check file permissions.', 'brandkit')];
    }

    // Master copy in the plugin storage: pics/ is part of GLPI's code and is
    // replaced by GLPI upgrades (and by a new container in Docker setups);
    // brandkit_get_wallpaper_path() republishes it from here when missing.
    $masterDir = brandkit_get_storage_subdir('wallpaper');
    if ($masterDir !== null) {
        @copy($publicPath, $masterDir . DIRECTORY_SEPARATOR . $filename);
    }

    return [$filename, null];
}
