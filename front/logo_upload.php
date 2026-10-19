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

/**
 * Upload or remove a logo / favicon (POST, CSRF checked by GLPI core).
 */
Session::checkRight('config', UPDATE);

$redirectUrl = brandkit_get_plugin_web_dir() . '/front/index.php?step=1';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Html::redirect($redirectUrl);
    exit;
}

// PHP drops the whole body when it exceeds post_max_size.
if (empty($_POST) && empty($_FILES)) {
    Session::addMessageAfterRedirect(__('Upload failed. The file may be too large for the server limits.', 'brandkit'), false, ERROR);
    Html::redirect($redirectUrl);
    exit;
}

$action = (string) ($_POST['brandkit_action'] ?? 'upload_logo');
$targetFile = (string) ($_POST['logo_target'] ?? '');
if (!in_array($action, ['upload_logo', 'clear_logo'], true) || !brandkit_is_logo_target($targetFile)) {
    Session::addMessageAfterRedirect(__('Invalid logo target selected.', 'brandkit'), false, ERROR);
    Html::redirect($redirectUrl);
    exit;
}

if ($action === 'clear_logo') {
    $messages = [
        'favicon_restore_failed' => __('Unable to restore the original favicon. Check file permissions.', 'brandkit'),
        'login_restore_failed' => __('Unable to restore the original login logo. Check file permissions.', 'brandkit'),
        'login_original_missing' => __('Original login logo not found in plugin pics.', 'brandkit'),
        'header_restore_failed' => __('Unable to restore the original header logo. Check file permissions.', 'brandkit'),
        'header_original_missing' => __('Original header logo not found in plugin pics.', 'brandkit'),
    ];
    foreach (brandkit_restore_original_logo_assets($targetFile) as $error) {
        Session::addMessageAfterRedirect($messages[$error] ?? $error, false, WARNING);
    }
    Session::addMessageAfterRedirect(__('Logo cleared. GLPI will use the default asset.', 'brandkit'), false, INFO);
    Html::redirect($redirectUrl);
    exit;
}

$isFavicon = $targetFile === 'favicon.ico';
$upload = $_FILES['brandkit_logo'] ?? null;
$uploadError = brandkit_check_upload(
    is_array($upload) ? $upload : null,
    $isFavicon ? ['ico'] : ['png', 'jpg', 'jpeg', 'webp'],
    $isFavicon ? BRANDKIT_MAX_FAVICON_BYTES : BRANDKIT_MAX_LOGO_BYTES
);
if ($uploadError !== null) {
    Session::addMessageAfterRedirect($uploadError, false, ERROR);
    Html::redirect($redirectUrl);
    exit;
}

$destination = brandkit_get_stored_logo_path($targetFile, false);
if ($destination === null) {
    Session::addMessageAfterRedirect(__('Unable to create plugin logo directory.', 'brandkit'), false, ERROR);
    Html::redirect($redirectUrl);
    exit;
}

// Save GLPI's own file before the new upload replaces the stored copy:
// compared with the previous upload, the native file is recognised as ours.
brandkit_backup_native_asset($targetFile);

$tmpName = $upload['tmp_name'];
if ($isFavicon) {
    // GD cannot decode .ico: validate the header and store the file as is.
    $saved = brandkit_is_ico_file($tmpName) && @move_uploaded_file($tmpName, $destination);
    if (!$saved) {
        Session::addMessageAfterRedirect(__('Favicon must be an ICO file.', 'brandkit'), false, ERROR);
        Html::redirect($redirectUrl);
        exit;
    }
} else {
    // Always decode and re-encode as PNG (GLPI's file names are .png):
    // drops metadata and anything smuggled after the image data, and caps
    // the size to something sensible for a logo.
    if (brandkit_get_image_info($tmpName) === null) {
        Session::addMessageAfterRedirect(__('The uploaded file is not a valid image.', 'brandkit'), false, ERROR);
        Html::redirect($redirectUrl);
        exit;
    }
    if (!brandkit_reencode_image($tmpName, $destination, 'image/png', 1200, 1200)) {
        Session::addMessageAfterRedirect(__('Unable to process the uploaded logo. Ensure the file is a valid image.', 'brandkit'), false, ERROR);
        Html::redirect($redirectUrl);
        exit;
    }
}

if (!brandkit_install_native_asset($targetFile)) {
    $kind = brandkit_get_logo_target_kinds()[$targetFile];
    $warnings = [
        'favicon' => __('Uploaded, but failed to replace the GLPI favicon. Check file permissions.', 'brandkit'),
        'login' => __('Uploaded, but failed to replace the native login logo. Check file permissions.', 'brandkit'),
        'header' => __('Uploaded, but failed to replace the native header logo. Check file permissions.', 'brandkit'),
    ];
    Session::addMessageAfterRedirect($warnings[$kind], false, WARNING);
}

Session::addMessageAfterRedirect(__('Logo uploaded successfully.', 'brandkit'), false, INFO);
Html::redirect($redirectUrl);
