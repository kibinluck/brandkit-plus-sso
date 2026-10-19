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

// GLPI 10 runs this script directly; GLPI 11 boots its kernel first (and the
// plugin may live outside GLPI's tree, e.g. /var/glpi/marketplace in Docker).
if (!defined('GLPI_ROOT')) {
    include_once '../../../inc/includes.php';
}
require_once __DIR__ . '/../inc/admin_views.php';

Session::checkRight('config', UPDATE);

$isGlpi11 = brandkit_is_glpi11();

function brandkit_is_light_color(string $hex): bool
{
    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return false;
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $luma = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
    return $luma > 0.75;
}

$currentStep = isset($_GET['step']) ? (int) $_GET['step'] : 0;
if ($currentStep < 0 || $currentStep > 2) {
    $currentStep = 0;
}

$baseUrl = brandkit_get_plugin_web_dir() . '/front/index.php';
$logoFiles = [
    'logo-GLPI-100-white.png' => __('Expanded menu logo', 'brandkit'),
    'logo-G-100-white.png' => __('Collapsed menu logo', 'brandkit'),
    'logo-GLPI-100-black.png' => __('Expanded menu logo (dark mode)', 'brandkit'),
    'logo-G-100-black.png' => __('Collapsed menu logo (dark mode)', 'brandkit'),
    'logo-GLPI-250-black.png' => __('Login logo', 'brandkit'),
    'favicon.ico' => __('Favicon (browser tab)', 'brandkit'),
];
$wallpaperPresets = brandkit_get_wallpaper_presets();
$wallpaperPresetDefault = brandkit_get_wallpaper_preset_default($wallpaperPresets);
$wallpaperPresetLabels = [];
foreach ($wallpaperPresets as $presetFile) {
    $labelBase = str_replace(['-', '_'], ' ', pathinfo($presetFile, PATHINFO_FILENAME));
    $wallpaperPresetLabels[$presetFile] = ucwords(trim($labelBase));
}

$loginDefaults = brandkit_get_login_defaults();
$loginConfig = brandkit_get_login_settings();
$logoScaleDefaults = [
    'logo_header_scale' => '100',
    'logo_reduced_scale' => '100',
];
$logoScaleLegacyDefaults = [
    'logo_header_light_scale' => '100',
    'logo_reduced_light_scale' => '100',
    'logo_header_dark_scale' => '100',
    'logo_reduced_dark_scale' => '100',
];
$logoScaleConfig = Config::getConfigurationValues(
    'plugin:brandkit',
    array_merge(array_keys($logoScaleDefaults), array_keys($logoScaleLegacyDefaults))
);
$logoScaleConfig = array_merge(
    $logoScaleLegacyDefaults,
    $logoScaleDefaults,
    array_filter($logoScaleConfig, static fn($value) => $value !== null && $value !== '')
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['brandkit_action'] ?? '') === 'reset_login_defaults') {
    $defaults = brandkit_get_login_defaults();
    brandkit_delete_uploaded_wallpaper_variants();

    Config::setConfigurationValues('plugin:brandkit', $defaults);
    $cssWritten = brandkit_write_login_css($defaults);

    if ($cssWritten) {
        Session::addMessageAfterRedirect(__('Login page settings saved.', 'brandkit'), false, INFO);
    } else {
        Session::addMessageAfterRedirect(__('Settings saved, but the login CSS file could not be written.', 'brandkit'), false, WARNING);
    }

    Html::redirect($baseUrl . '?step=2');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['brandkit_action'] ?? '') === 'save_login_settings') {
    $layout = $_POST['login_layout'] ?? $loginDefaults['login_layout'];
    if (!in_array($layout, ['split-left', 'split-right', 'classic'], true)) {
        $layout = $loginDefaults['login_layout'];
    }

    $panelTransparent = ($_POST['login_panel_transparent'] ?? '0') === '1' ? '1' : '0';
    $panelHoverEffect = ($_POST['login_panel_hover_effect'] ?? '1') === '1' ? '1' : '0';
    $panelOpacity = brandkit_clamp_int($_POST['login_panel_opacity'] ?? $loginDefaults['login_panel_opacity'], 0, 100, 65);
    $panelBlur = brandkit_clamp_int($_POST['login_panel_blur'] ?? $loginDefaults['login_panel_blur'], 0, 24, 6);
    $panelWidth = brandkit_clamp_int($_POST['login_panel_width'] ?? $loginDefaults['login_panel_width'], 280, 720, 520);
    $panelHeight = brandkit_clamp_int($_POST['login_panel_height'] ?? $loginDefaults['login_panel_height'], 360, 780, 560);
    $panelColor = brandkit_normalize_hex_color($_POST['login_panel_color'] ?? $loginDefaults['login_panel_color'], $loginDefaults['login_panel_color']);
    $textColor = brandkit_normalize_hex_color($_POST['login_text_color'] ?? $loginDefaults['login_text_color'], $loginDefaults['login_text_color']);
    $logoPosition = $_POST['login_logo_position'] ?? $loginDefaults['login_logo_position'];
    if (!in_array($logoPosition, ['outside', 'inside'], true)) {
        $logoPosition = $loginDefaults['login_logo_position'];
    }
    $splitOpacity = brandkit_clamp_int($_POST['login_split_opacity'] ?? $loginDefaults['login_split_opacity'], 0, 100, 55);
    $splitBlur = brandkit_clamp_int($_POST['login_split_blur'] ?? $loginDefaults['login_split_blur'], 0, 24, 10);
    $splitColor = brandkit_normalize_hex_color($_POST['login_split_color'] ?? $loginDefaults['login_split_color'], $loginDefaults['login_split_color']);
    $splitWidth = brandkit_clamp_int($_POST['login_split_width'] ?? $loginDefaults['login_split_width'], 0, 100, 50);
    $buttonStyle = $_POST['login_button_style'] ?? $loginDefaults['login_button_style'];
    if (!in_array($buttonStyle, brandkit_get_login_button_styles(), true)) {
        $buttonStyle = $loginDefaults['login_button_style'];
    }
    $buttonShape = $_POST['login_button_shape'] ?? $loginDefaults['login_button_shape'];
    if (!in_array($buttonShape, brandkit_get_login_button_shapes(), true)) {
        $buttonShape = $loginDefaults['login_button_shape'];
    }
    $buttonColorMode = $_POST['login_button_color_mode'] ?? $loginDefaults['login_button_color_mode'];
    if (!in_array($buttonColorMode, ['preset', 'custom'], true)) {
        $buttonColorMode = $loginDefaults['login_button_color_mode'];
    }
    $buttonCustomStyle = $_POST['login_button_custom_style'] ?? $loginDefaults['login_button_custom_style'];
    if (!in_array($buttonCustomStyle, ['default', 'outline'], true)) {
        $buttonCustomStyle = $loginDefaults['login_button_custom_style'];
    }
    $buttonHoverEffect = $_POST['login_button_hover_effect'] ?? $loginDefaults['login_button_hover_effect'];
    if (!in_array($buttonHoverEffect, ['press', 'fill'], true)) {
        $buttonHoverEffect = $loginDefaults['login_button_hover_effect'];
    }
    $buttonHoverFillMode = $_POST['login_button_hover_fill_mode'] ?? $loginDefaults['login_button_hover_fill_mode'];
    if (!in_array($buttonHoverFillMode, ['button', 'custom'], true)) {
        $buttonHoverFillMode = $loginDefaults['login_button_hover_fill_mode'];
    }
    $buttonTextSizeDefault = $isGlpi11 ? 14 : 15;
    $buttonTextSize = brandkit_clamp_int($_POST['login_button_text_size'] ?? $loginDefaults['login_button_text_size'], 10, 28, $buttonTextSizeDefault);
    $buttonColorRaw = $loginDefaults['login_button_color'];
    if (!empty($_POST['login_button_color_hex'])) {
        $buttonColorRaw = $_POST['login_button_color_hex'];
    } elseif (!empty($_POST['login_button_color'])) {
        $buttonColorRaw = $_POST['login_button_color'];
    }
    $buttonColor = brandkit_normalize_hex_color($buttonColorRaw, $loginDefaults['login_button_color']);
    $buttonHoverFillColor = brandkit_normalize_hex_color(
        $_POST['login_button_hover_fill_color'] ?? $loginDefaults['login_button_hover_fill_color'],
        $loginDefaults['login_button_hover_fill_color']
    );
    $buttonHoverTextColor = brandkit_normalize_hex_color(
        $_POST['login_button_hover_text_color'] ?? $loginDefaults['login_button_hover_text_color'],
        $loginDefaults['login_button_hover_text_color']
    );

    $fieldStyle = $_POST['login_field_style'] ?? $loginDefaults['login_field_style'];
    $fieldStyleOptions = ['solid', 'glass', 'soft', 'outline', 'underline', 'neon'];
    if (!in_array($fieldStyle, $fieldStyleOptions, true)) {
        $fieldStyle = $loginDefaults['login_field_style'];
    }
    $fieldBg = brandkit_normalize_hex_color($_POST['login_field_bg'] ?? $loginDefaults['login_field_bg'], $loginDefaults['login_field_bg']);
    $fieldBorder = brandkit_normalize_hex_color($_POST['login_field_border'] ?? $loginDefaults['login_field_border'], $loginDefaults['login_field_border']);
    $fieldBorderWidthDefault = $isGlpi11 ? 1 : 0;
    $fieldBorderWidth = brandkit_clamp_int($_POST['login_field_border_width'] ?? $loginDefaults['login_field_border_width'], 0, 6, $fieldBorderWidthDefault);
    $fieldText = brandkit_normalize_hex_color($_POST['login_field_text'] ?? $loginDefaults['login_field_text'], $loginDefaults['login_field_text']);
    $fieldTextSizeDefault = $isGlpi11 ? 14 : 15;
    $fieldHeightDefault = $isGlpi11 ? 44 : 40;
    $fieldTextSize = brandkit_clamp_int($_POST['login_field_text_size'] ?? $loginDefaults['login_field_text_size'], 10, 36, $fieldTextSizeDefault);
    $fieldHeight = brandkit_clamp_int($_POST['login_field_height'] ?? $loginDefaults['login_field_height'], 32, 72, $fieldHeightDefault);
    $fieldWidthDefault = 280;
    $fieldWidth = brandkit_clamp_int($_POST['login_field_width'] ?? $loginDefaults['login_field_width'], 260, 720, $fieldWidthDefault);
    $fieldSpacing = null;
    $titleSize = null;
    if (!$isGlpi11) {
        $fieldSpacing = brandkit_clamp_int($_POST['login_field_spacing'] ?? $loginDefaults['login_field_spacing'], 8, 48, 20);
        $titleSize = brandkit_clamp_int($_POST['login_title_size'] ?? $loginDefaults['login_title_size'], 16, 48, 20);
    }
    $textSizeDefault = $isGlpi11 ? 15 : 14;
    $textSize = brandkit_clamp_int($_POST['login_text_size'] ?? $loginDefaults['login_text_size'], 10, 30, $textSizeDefault);
    $fieldFocus = brandkit_normalize_hex_color($_POST['login_field_focus'] ?? $loginDefaults['login_field_focus'], $loginDefaults['login_field_focus']);
    $accentColor = brandkit_normalize_hex_color($_POST['login_accent_color'] ?? $loginDefaults['login_accent_color'], $loginDefaults['login_accent_color']);
    $iconUser = $_POST['login_icon_user'] ?? $loginDefaults['login_icon_user'];
    $iconPassword = $_POST['login_icon_password'] ?? $loginDefaults['login_icon_password'];
    $iconColor = brandkit_normalize_hex_color($_POST['login_icon_color'] ?? $loginDefaults['login_icon_color'], $loginDefaults['login_icon_color']);
    $iconPosition = $_POST['login_icon_position'] ?? $loginDefaults['login_icon_position'];
    $iconUserOptions = ['none', 'user', 'id', 'mail'];
    if (!in_array($iconUser, $iconUserOptions, true)) {
        $iconUser = $loginDefaults['login_icon_user'];
    }
    $iconPasswordOptions = ['none', 'lock', 'key', 'shield'];
    if (!in_array($iconPassword, $iconPasswordOptions, true)) {
        $iconPassword = $loginDefaults['login_icon_password'];
    }
    if (!in_array($iconPosition, ['left', 'right'], true)) {
        $iconPosition = $loginDefaults['login_icon_position'];
    }
    $fieldRadius = brandkit_clamp_int($_POST['login_field_radius'] ?? $loginDefaults['login_field_radius'], 0, 32, 12);
    $wallpaperSize = $_POST['login_wallpaper_size'] ?? $loginDefaults['login_wallpaper_size'];
    $wallpaperSizeOptions = ['cover', 'contain', 'stretch', 'original'];
    if (!in_array($wallpaperSize, $wallpaperSizeOptions, true)) {
        $wallpaperSize = $loginDefaults['login_wallpaper_size'];
    }
    $wallpaperPosition = $_POST['login_wallpaper_position'] ?? $loginDefaults['login_wallpaper_position'];
    $wallpaperPositionOptions = ['center', 'top', 'bottom', 'left', 'right'];
    if (!in_array($wallpaperPosition, $wallpaperPositionOptions, true)) {
        $wallpaperPosition = $loginDefaults['login_wallpaper_position'];
    }
    $wallpaperAttachment = $_POST['login_wallpaper_attachment'] ?? $loginDefaults['login_wallpaper_attachment'];
    $wallpaperAttachmentOptions = ['fixed', 'scroll'];
    if (!in_array($wallpaperAttachment, $wallpaperAttachmentOptions, true)) {
        $wallpaperAttachment = $loginDefaults['login_wallpaper_attachment'];
    }
    $wallpaperOverlayOpacity = brandkit_clamp_int(
        $_POST['login_wallpaper_overlay_opacity'] ?? $loginDefaults['login_wallpaper_overlay_opacity'],
        0,
        100,
        35
    );
    $wallpaperOverlayColor = brandkit_normalize_hex_color(
        $_POST['login_wallpaper_overlay_color'] ?? $loginDefaults['login_wallpaper_overlay_color'],
        $loginDefaults['login_wallpaper_overlay_color']
    );
    $hideLoginText = ($_POST['login_hide_login_text'] ?? '0') === '1' ? '1' : '0';
    $ssoMode = ($_POST['login_sso_mode'] ?? '0') === '1' ? '1' : '0';
    $ssoLocalLink = ($_POST['login_sso_local_link'] ?? '1') === '1' ? '1' : '0';
    $ssoTitle = is_string($_POST['login_sso_title'] ?? null) ? trim($_POST['login_sso_title']) : '';
    $ssoTitle = function_exists('mb_substr') ? mb_substr($ssoTitle, 0, 120) : substr($ssoTitle, 0, 120);
    $ssoButtonColors = ($_POST['login_sso_button_colors'] ?? 'login') === 'custom' ? 'custom' : 'login';
    $ssoButtonBg = brandkit_normalize_hex_color($_POST['login_sso_button_bg'] ?? '', $loginDefaults['login_sso_button_bg']);
    $ssoButtonText = brandkit_normalize_hex_color($_POST['login_sso_button_text'] ?? '', $loginDefaults['login_sso_button_text']);
    $ssoButtonLabel = is_string($_POST['login_sso_button_label'] ?? null) ? trim($_POST['login_sso_button_label']) : '';
    $ssoButtonLabel = function_exists('mb_substr') ? mb_substr($ssoButtonLabel, 0, 60) : substr($ssoButtonLabel, 0, 60);
    $ssoButtonIcon = in_array($_POST['login_sso_button_icon'] ?? '', ['provider', 'microsoft', 'none'], true)
        ? $_POST['login_sso_button_icon']
        : 'provider';
    $loginLogoWidth = brandkit_clamp_int(
        $_POST['login_logo_width'] ?? ($loginConfig['login_logo_width'] ?? $loginDefaults['login_logo_width']),
        80,
        480,
        200
    );
    $loginLogoHeight = brandkit_clamp_int(
        $_POST['login_logo_height'] ?? ($loginConfig['login_logo_height'] ?? $loginDefaults['login_logo_height']),
        40,
        320,
        110
    );
    $loginLogoScale = brandkit_clamp_int(
        $_POST['login_logo_scale'] ?? ($loginConfig['login_logo_scale'] ?? $loginDefaults['login_logo_scale']),
        80,
        480,
        200
    );
    $loginLogoOffset = brandkit_clamp_int($_POST['login_logo_offset'] ?? $loginDefaults['login_logo_offset'], 0, 240, 0);

    $loginWallpaper = $loginConfig['login_wallpaper'] ?? '';
    $wallpaperSource = $_POST['login_wallpaper_source'] ?? ($loginConfig['login_wallpaper_source'] ?? '');
    if ($wallpaperSource === '') {
        $wallpaperSource = $loginWallpaper ? 'custom' : 'preset';
    }
    if (!in_array($wallpaperSource, ['preset', 'custom'], true)) {
        $wallpaperSource = $loginWallpaper ? 'custom' : 'preset';
    }
    $wallpaperPreset = $_POST['login_wallpaper_preset'] ?? ($loginConfig['login_wallpaper_preset'] ?? $wallpaperPresetDefault);
    if (!in_array($wallpaperPreset, $wallpaperPresets, true)) {
        $wallpaperPreset = $wallpaperPresetDefault;
    }
    $wallpaperError = null;
    if (!empty($_POST['login_wallpaper_remove'])) {
        brandkit_delete_uploaded_wallpaper_variants();
        $loginWallpaper = '';
        $wallpaperSource = 'custom';
    } elseif (($_FILES['login_wallpaper']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        [$storedWallpaper, $wallpaperError] = brandkit_store_uploaded_wallpaper($_FILES['login_wallpaper']);
        if ($storedWallpaper !== null) {
            $loginWallpaper = $storedWallpaper;
            $wallpaperSource = 'custom';
        }
    }

    $loginConfig = [
        'login_layout' => $layout,
        'login_panel_transparent' => $panelTransparent,
        'login_panel_hover_effect' => $panelHoverEffect,
        'login_panel_opacity' => (string) $panelOpacity,
        'login_panel_color' => $panelColor,
        'login_panel_blur' => (string) $panelBlur,
        'login_panel_width' => (string) $panelWidth,
        'login_panel_height' => (string) $panelHeight,
        'login_text_color' => $textColor,
        'login_logo_position' => $logoPosition,
        'login_split_opacity' => (string) $splitOpacity,
        'login_split_color' => $splitColor,
        'login_split_blur' => (string) $splitBlur,
        'login_split_width' => (string) $splitWidth,
        'login_button_style' => $buttonStyle,
        'login_button_shape' => $buttonShape,
        'login_button_color_mode' => $buttonColorMode,
        'login_button_color' => $buttonColor,
        'login_button_custom_style' => $buttonCustomStyle,
        'login_button_hover_effect' => $buttonHoverEffect,
        'login_button_hover_fill_mode' => $buttonHoverFillMode,
        'login_button_hover_fill_color' => $buttonHoverFillColor,
        'login_button_hover_text_color' => $buttonHoverTextColor,
        'login_button_text_size' => (string) $buttonTextSize,
        'login_field_style' => $fieldStyle,
        'login_field_bg' => $fieldBg,
        'login_field_border' => $fieldBorder,
        'login_field_border_width' => (string) $fieldBorderWidth,
        'login_field_text' => $fieldText,
        'login_field_text_size' => (string) $fieldTextSize,
        'login_field_height' => (string) $fieldHeight,
        'login_field_width' => (string) $fieldWidth,
        'login_text_size' => (string) $textSize,
        'login_field_focus' => $fieldFocus,
        'login_accent_color' => $accentColor,
        'login_icon_user' => $iconUser,
        'login_icon_password' => $iconPassword,
        'login_icon_color' => $iconColor,
        'login_icon_position' => $iconPosition,
        'login_field_radius' => (string) $fieldRadius,
        'login_wallpaper_size' => $wallpaperSize,
        'login_wallpaper_position' => $wallpaperPosition,
        'login_wallpaper_attachment' => $wallpaperAttachment,
        'login_wallpaper_overlay_color' => $wallpaperOverlayColor,
        'login_wallpaper_overlay_opacity' => (string) $wallpaperOverlayOpacity,
        'login_wallpaper_source' => $wallpaperSource,
        'login_wallpaper_preset' => $wallpaperPreset,
        'login_hide_login_text' => $hideLoginText,
        'login_wallpaper' => $loginWallpaper,
        'login_logo_width' => (string) $loginLogoWidth,
        'login_logo_height' => (string) $loginLogoHeight,
        'login_logo_scale' => (string) $loginLogoScale,
        'login_logo_offset' => (string) $loginLogoOffset,
        'login_sso_mode' => $ssoMode,
        'login_sso_local_link' => $ssoLocalLink,
        'login_sso_title' => $ssoTitle,
        'login_sso_button_colors' => $ssoButtonColors,
        'login_sso_button_bg' => $ssoButtonBg,
        'login_sso_button_text' => $ssoButtonText,
        'login_sso_button_label' => $ssoButtonLabel,
        'login_sso_button_icon' => $ssoButtonIcon,
    ];
    if (!$isGlpi11) {
        $loginConfig['login_field_spacing'] = (string) $fieldSpacing;
        $loginConfig['login_title_size'] = (string) $titleSize;
    }

    Config::setConfigurationValues('plugin:brandkit', $loginConfig);

    $cssWritten = brandkit_write_login_css($loginConfig);

    if ($cssWritten) {
        Session::addMessageAfterRedirect(__('Login page settings saved.', 'brandkit'), false, INFO);
    } else {
        Session::addMessageAfterRedirect(__('Settings saved, but the login CSS file could not be written.', 'brandkit'), false, WARNING);
    }
    if ($wallpaperError) {
        Session::addMessageAfterRedirect($wallpaperError, false, WARNING);
    }

    Html::redirect($baseUrl . '?step=2');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['brandkit_action'] ?? '') === 'save_logo_scales') {
    $logoHeaderScale = brandkit_clamp_int($_POST['logo_header_scale'] ?? null, 40, 200, 100);
    $logoReducedScale = brandkit_clamp_int($_POST['logo_reduced_scale'] ?? null, 40, 200, 100);

    $logoScaleConfig = [
        'logo_header_scale' => (string) $logoHeaderScale,
        'logo_reduced_scale' => (string) $logoReducedScale,
    ];

    Config::setConfigurationValues('plugin:brandkit', $logoScaleConfig);

    Session::addMessageAfterRedirect(__('Logo sizes updated.', 'brandkit'), false, INFO);
    Html::redirect($baseUrl . '?step=1');
    exit;
}

Html::header(PLUGIN_BRANDKIT_NAME, $_SERVER['PHP_SELF'], 'plugins', 'brandkit');

?>

<link rel="stylesheet" href="<?php echo htmlescape(brandkit_get_public_asset_url('css/admin.css') . '?v=' . PLUGIN_BRANDKIT_VERSION); ?>">

<div class="brandkit-wrapper">
    <div class="brandkit-hero">
        <div class="brandkit-hero-inner">
            <h2>
                <?php echo htmlescape(PLUGIN_BRANDKIT_NAME); ?>
                <span class="brandkit-hero-version">v<?php echo htmlescape(PLUGIN_BRANDKIT_VERSION); ?></span>
            </h2>
            <p><?php echo htmlescape(__('Turn GLPI into a showcase for your brand in just a few clicks.', 'brandkit')); ?></p>
            <div class="brandkit-hero-links">
                <a class="brandkit-hero-link" href="<?php echo htmlescape(PLUGIN_BRANDKIT_FORK_URL); ?>" target="_blank" rel="noopener noreferrer">
                    <i class="ti ti-code" aria-hidden="true"></i>
                    <span><?php echo htmlescape(__('Source code', 'brandkit')); ?></span>
                </a>
                <a class="brandkit-hero-link" href="<?php echo htmlescape(PLUGIN_BRANDKIT_ORIGINAL_URL); ?>" target="_blank" rel="noopener noreferrer">
                    <i class="ti ti-brand-github" aria-hidden="true"></i>
                    <span><?php echo htmlescape(__('Original project', 'brandkit')); ?></span>
                </a>
            </div>
        </div>
    </div>

    <div class="brandkit-timeline">
        <a class="brandkit-timeline-step <?php echo $currentStep === 0 ? 'active' : ''; ?>" href="<?php echo htmlescape($baseUrl); ?>">
            <?php echo htmlescape(__('About', 'brandkit')); ?>
        </a>
        <a class="brandkit-timeline-step <?php echo $currentStep === 1 ? 'active' : ''; ?>" href="<?php echo htmlescape($baseUrl); ?>?step=1">
            <?php echo htmlescape(__('GLPI logos', 'brandkit')); ?>
        </a>
        <a class="brandkit-timeline-step <?php echo $currentStep === 2 ? 'active' : ''; ?>" href="<?php echo htmlescape($baseUrl); ?>?step=2">
            <?php echo htmlescape(__('Login Page', 'brandkit')); ?>
        </a>
    </div>

    <div class="brandkit-content">
        <?php if ($currentStep === 0) : ?>
            <div class="brandkit-intro">
                <div class="brandkit-intro-copy">
                    <span class="brandkit-intro-eyebrow"><?php echo htmlescape(PLUGIN_BRANDKIT_NAME); ?></span>
                    <h3><?php echo htmlescape(__('The visual evolution of your GLPI starts here.', 'brandkit')); ?></h3>
                    <p><?php echo htmlescape(__('Brandkit delivers logos, login screens, and colors aligned with your identity. No CSS, no effort, just professional results.', 'brandkit')); ?></p>
                    <div class="brandkit-fork-credits">
                        <p>
                            <?php echo htmlescape(__('Brandkit Plus SSO is a fork of Brandkit, created by Marcati.', 'brandkit')); ?>
                            <a href="<?php echo htmlescape(PLUGIN_BRANDKIT_ORIGINAL_URL); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlescape(__('See the original project', 'brandkit')); ?></a>.
                        </p>
                        <p><?php echo htmlescape(__('Its goal is to work with the single sign-on (SSO) of the samlSSO plugin and to fix issues found in the original.', 'brandkit')); ?></p>
                        <p><?php echo htmlescape(sprintf(__('Developed with Claude (Anthropic), under the guidance and supervision of %s.', 'brandkit'), PLUGIN_BRANDKIT_FORK_MAINTAINER)); ?></p>
                    </div>
                    <div class="brandkit-intro-actions">
                        <a class="brandkit-cta primary" href="<?php echo htmlescape($baseUrl); ?>?step=1"><?php echo htmlescape(__('GLPI logos', 'brandkit')); ?></a>
                        <a class="brandkit-cta secondary" href="<?php echo htmlescape($baseUrl); ?>?step=2"><?php echo htmlescape(__('Login Page', 'brandkit')); ?></a>
                        <a class="brandkit-cta ghost" href="<?php echo htmlescape(PLUGIN_BRANDKIT_ORIGINAL_URL); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlescape(__('Original project', 'brandkit')); ?></a>
                    </div>
                </div>
                <div class="brandkit-intro-panel">
                    <div class="brandkit-intro-card">
                        <span>01</span>
                        <h4><?php echo htmlescape(__('Consistent logos', 'brandkit')); ?></h4>
                        <p><?php echo htmlescape(__('Ready uploads for header, favicon, and login.', 'brandkit')); ?></p>
                    </div>
                    <div class="brandkit-intro-card">
                        <span>02</span>
                        <h4><?php echo htmlescape(__('Login with presence', 'brandkit')); ?></h4>
                        <p><?php echo htmlescape(__('Pick layouts, colors, and effects for an authentication screen that sells your brand.', 'brandkit')); ?></p>
                    </div>
                    <div class="brandkit-intro-card">
                        <span>03</span>
                        <h4><?php echo htmlescape(__('Fast delivery', 'brandkit')); ?></h4>
                        <p><?php echo htmlescape(__('Settings saved directly in GLPI to activate branding without rework.', 'brandkit')); ?></p>
                    </div>
                </div>
            </div>
        <?php elseif ($currentStep === 1) : ?>
            <h3><?php echo htmlescape(__('GLPI logo configuration', 'brandkit')); ?></h3>
            <p><?php echo htmlescape(__('Upload logos that will replace the default GLPI assets using CSS variables.', 'brandkit')); ?></p>
            <?php
                $logoHeaderScale = brandkit_clamp_int(
                    $logoScaleConfig['logo_header_scale']
                        ?? $logoScaleConfig['logo_header_light_scale']
                        ?? $logoScaleConfig['logo_header_dark_scale']
                        ?? $logoScaleDefaults['logo_header_scale'],
                    40,
                    200,
                    100
                );
                $logoReducedScale = brandkit_clamp_int(
                    $logoScaleConfig['logo_reduced_scale']
                        ?? $logoScaleConfig['logo_reduced_light_scale']
                        ?? $logoScaleConfig['logo_reduced_dark_scale']
                        ?? $logoScaleDefaults['logo_reduced_scale'],
                    40,
                    200,
                    100
                );
            ?>
            <?php
                $lightThemeFiles = [
                    'logo-GLPI-100-white.png',
                    'logo-G-100-white.png',
                    'logo-GLPI-250-black.png',
                ];
                $darkThemeFiles = [
                    'logo-GLPI-100-black.png',
                    'logo-G-100-black.png',
                ];
                $logoInfoMap = [
                    'logo-GLPI-100-white.png' => __('Expanded menu logo: the logo displayed when the sidebar menu is open, with more space available.', 'brandkit'),
                    'logo-G-100-white.png' => __('Collapsed menu logo: the logo displayed when the sidebar menu is collapsed, so it needs to be a more compact version.', 'brandkit'),
                    'logo-GLPI-100-black.png' => __('Expanded menu logo: the logo displayed when the sidebar menu is open, with more space available.', 'brandkit'),
                    'logo-G-100-black.png' => __('Collapsed menu logo: the logo displayed when the sidebar menu is collapsed, so it needs to be a more compact version.', 'brandkit'),
                    'logo-GLPI-250-black.png' => __('Login page logo: the logo displayed on the login screen before the user enters the system.', 'brandkit'),
                ];
                $logoGroups = [
                    [
                        'title' => __('Light mode logos', 'brandkit'),
                        'info' => __('Light mode logos: used when the system is in light mode, ensuring good visibility on white or light backgrounds.', 'brandkit'),
                        'files' => $lightThemeFiles,
                    ],
                    [
                        'title' => __('Dark mode logos', 'brandkit'),
                        'info' => __('Dark mode logos: used when the system is in dark mode, ensuring contrast and readability on black or dark backgrounds.', 'brandkit'),
                        'files' => $darkThemeFiles,
                    ],
                ];
                $faviconGroup = [
                    'title' => __('Favicon', 'brandkit'),
                    'info' => __('The favicon is the small icon that appears in the browser tab (and also in bookmarks).', 'brandkit'),
                    'files' => [
                        'favicon.ico',
                    ],
                ];
            ?>
            <?php foreach ($logoGroups as $group) : ?>
                <?php brandkit_render_logo_section(
                    $group['title'],
                    $group['info'] ?? null,
                    array_intersect_key($logoFiles, array_flip($group['files'])),
                    $logoInfoMap
                ); ?>
            <?php endforeach; ?>
            <form method="post" action="<?php echo htmlescape($baseUrl); ?>?step=1">
                <div class="brandkit-section">
                    <h4><?php echo htmlescape(__('Logo size (%)', 'brandkit')); ?></h4>
                    <div class="brandkit-form-grid">
                        <div>
                            <label for="brandkit_logo_header_scale"><?php echo htmlescape(__('Expanded menu logo size (%)', 'brandkit')); ?></label>
                            <div class="brandkit-range">
                                <input type="range" id="brandkit_logo_header_scale" name="logo_header_scale" min="40" max="200" step="5" value="<?php echo htmlescape($logoHeaderScale); ?>" oninput="document.getElementById('brandkit_logo_header_scale_value').textContent = this.value + '%'">
                                <span class="brandkit-range-value" id="brandkit_logo_header_scale_value"><?php echo htmlescape($logoHeaderScale); ?>%</span>
                            </div>
                        </div>
                        <div>
                            <label for="brandkit_logo_reduced_scale"><?php echo htmlescape(__('Collapsed menu logo size (%)', 'brandkit')); ?></label>
                            <div class="brandkit-range">
                                <input type="range" id="brandkit_logo_reduced_scale" name="logo_reduced_scale" min="40" max="200" step="5" value="<?php echo htmlescape($logoReducedScale); ?>" oninput="document.getElementById('brandkit_logo_reduced_scale_value').textContent = this.value + '%'">
                                <span class="brandkit-range-value" id="brandkit_logo_reduced_scale_value"><?php echo htmlescape($logoReducedScale); ?>%</span>
                            </div>
                        </div>
                    </div>
                    <div class="brandkit-actions">
                        <button type="submit" class="brandkit-save-logo"><?php echo htmlescape(__('Save logo sizes', 'brandkit')); ?></button>
                    </div>
                </div>
                <input type="hidden" name="brandkit_action" value="save_logo_scales">
                <input type="hidden" name="_glpi_csrf_token" value="<?php echo htmlescape(brandkit_get_csrf_token()); ?>">
            </form>
            <?php brandkit_render_logo_section(
                $faviconGroup['title'],
                $faviconGroup['info'] ?? null,
                array_intersect_key($logoFiles, array_flip($faviconGroup['files']))
            ); ?>
            <script src="<?php echo htmlescape(brandkit_get_public_asset_url('js/admin-logos.js') . '?v=' . PLUGIN_BRANDKIT_VERSION); ?>"></script>
        <?php elseif ($currentStep === 2) : ?>
            <h3><?php echo htmlescape(__('Login page configuration', 'brandkit')); ?></h3>
            <p><?php echo htmlescape(__('Pick a layout and visual treatments for the GLPI login page.', 'brandkit')); ?></p>
            <?php
                $loginWallpaper = $loginConfig['login_wallpaper'] ?? '';
                if ($loginWallpaper) {
                    $resolvedWallpaper = brandkit_resolve_wallpaper_filename($loginWallpaper);
                    if ($resolvedWallpaper !== '') {
                        $loginWallpaper = $resolvedWallpaper;
                    }
                }
                $pluginStorageDir = brandkit_get_plugin_storage_dir();
                $wallpaperSource = $loginConfig['login_wallpaper_source'] ?? '';
                if ($wallpaperSource === '') {
                    $wallpaperSource = $loginWallpaper ? 'custom' : 'preset';
                }
                if (!in_array($wallpaperSource, ['preset', 'custom'], true)) {
                    $wallpaperSource = $loginWallpaper ? 'custom' : 'preset';
                }
                if ($wallpaperSource === 'custom' && $loginWallpaper && brandkit_get_wallpaper_path($loginWallpaper)) {
                    $wallpaperSource = 'custom';
                }
                $wallpaperPreset = $loginConfig['login_wallpaper_preset'] ?? $wallpaperPresetDefault;
                if (!in_array($wallpaperPreset, $wallpaperPresets, true)) {
                    $wallpaperPreset = $wallpaperPresetDefault;
                }
                $customWallpaperUrl = null;
                $customWallpaperWebpUrl = null;
                if ($loginWallpaper) {
                    $resolvedWallpaper = brandkit_resolve_wallpaper_filename($loginWallpaper);
                    if ($resolvedWallpaper !== '') {
                        $loginWallpaper = $resolvedWallpaper;
                    }
                    $customWallpaperPath = brandkit_get_wallpaper_path($loginWallpaper);
                    $customWallpaperVersion = ($customWallpaperPath && is_file($customWallpaperPath))
                        ? (int) filemtime($customWallpaperPath)
                        : time();
                    $customWallpaperUrl = brandkit_get_wallpaper_image_url($loginWallpaper, $customWallpaperVersion);
                    $customWebpPath = preg_replace('/\.[^.]+$/', '.webp', $customWallpaperPath);
                    if ($customWebpPath && is_file($customWebpPath)) {
                        $customWallpaperWebpUrl = brandkit_get_wallpaper_image_url(basename($customWebpPath), (int) filemtime($customWebpPath));
                    }
                }
                $presetWallpaperUrl = null;
                $presetWallpaperWebpUrl = null;
                if ($wallpaperPreset) {
                    $presetAssets = brandkit_get_wallpaper_preset_assets($wallpaperPreset);
                    $presetWallpaperUrl = $presetAssets['url'] ?? null;
                    $presetWallpaperWebpUrl = $presetAssets['webp_url'] ?? null;
                }
                $loginWallpaperUrl = $wallpaperSource === 'custom' ? $customWallpaperUrl : $presetWallpaperUrl;
                $loginWallpaperWebpUrl = $wallpaperSource === 'custom' ? $customWallpaperWebpUrl : $presetWallpaperWebpUrl;
                $loginLogoScale = brandkit_clamp_int($loginConfig['login_logo_scale'] ?? $loginDefaults['login_logo_scale'], 80, 480, 200);
                $loginLogoOffset = brandkit_clamp_int($loginConfig['login_logo_offset'] ?? $loginDefaults['login_logo_offset'], 0, 240, 0);
                $loginPanelWidth = brandkit_clamp_int(
                    $loginConfig['login_panel_width'] ?? $loginDefaults['login_panel_width'],
                    280,
                    720,
                    520
                );
                $loginPanelHeight = brandkit_clamp_int(
                    $loginConfig['login_panel_height'] ?? $loginDefaults['login_panel_height'],
                    360,
                    780,
                    560
                );
                $loginPanelHoverEffect = ($loginConfig['login_panel_hover_effect'] ?? $loginDefaults['login_panel_hover_effect']) === '1';
                $loginButtonStyle = $loginConfig['login_button_style'] ?? $loginDefaults['login_button_style'];
                if (!in_array($loginButtonStyle, brandkit_get_login_button_styles(), true)) {
                    $loginButtonStyle = $loginDefaults['login_button_style'];
                }
                $loginButtonShape = $loginConfig['login_button_shape'] ?? $loginDefaults['login_button_shape'];
                if (!in_array($loginButtonShape, brandkit_get_login_button_shapes(), true)) {
                    $loginButtonShape = $loginDefaults['login_button_shape'];
                }
                $loginButtonColorMode = $loginConfig['login_button_color_mode'] ?? $loginDefaults['login_button_color_mode'];
                if (!in_array($loginButtonColorMode, ['preset', 'custom'], true)) {
                    $loginButtonColorMode = $loginDefaults['login_button_color_mode'];
                }
                $loginButtonCustomStyle = $loginConfig['login_button_custom_style'] ?? $loginDefaults['login_button_custom_style'];
                if (!in_array($loginButtonCustomStyle, ['default', 'outline'], true)) {
                    $loginButtonCustomStyle = $loginDefaults['login_button_custom_style'];
                }
                $loginButtonHoverEffect = $loginConfig['login_button_hover_effect'] ?? $loginDefaults['login_button_hover_effect'];
                if (!in_array($loginButtonHoverEffect, ['press', 'fill'], true)) {
                    $loginButtonHoverEffect = $loginDefaults['login_button_hover_effect'];
                }
                $loginButtonHoverFillMode = $loginConfig['login_button_hover_fill_mode'] ?? $loginDefaults['login_button_hover_fill_mode'];
                if (!in_array($loginButtonHoverFillMode, ['button', 'custom'], true)) {
                    $loginButtonHoverFillMode = $loginDefaults['login_button_hover_fill_mode'];
                }
                $loginButtonTextSize = brandkit_clamp_int(
                    $loginConfig['login_button_text_size'] ?? $loginDefaults['login_button_text_size'],
                    10,
                    28,
                    14
                );
                $loginButtonColor = brandkit_normalize_hex_color(
                    $loginConfig['login_button_color'] ?? $loginDefaults['login_button_color'],
                    $loginDefaults['login_button_color']
                );
                $loginButtonHoverFillColor = brandkit_normalize_hex_color(
                    $loginConfig['login_button_hover_fill_color'] ?? $loginDefaults['login_button_hover_fill_color'],
                    $loginDefaults['login_button_hover_fill_color']
                );
                $loginButtonHoverTextColor = brandkit_normalize_hex_color(
                    $loginConfig['login_button_hover_text_color'] ?? $loginDefaults['login_button_hover_text_color'],
                    $loginDefaults['login_button_hover_text_color']
                );
                $loginLogoPosition = $loginConfig['login_logo_position'] ?? $loginDefaults['login_logo_position'];
                if (!in_array($loginLogoPosition, ['outside', 'inside'], true)) {
                    $loginLogoPosition = $loginDefaults['login_logo_position'];
                }
                $loginWallpaperSize = $loginConfig['login_wallpaper_size'] ?? $loginDefaults['login_wallpaper_size'];
                $loginWallpaperPosition = $loginConfig['login_wallpaper_position'] ?? $loginDefaults['login_wallpaper_position'];
                $loginWallpaperAttachment = $loginConfig['login_wallpaper_attachment'] ?? $loginDefaults['login_wallpaper_attachment'];
                $loginWallpaperOverlayColor = brandkit_normalize_hex_color(
                    $loginConfig['login_wallpaper_overlay_color'] ?? $loginDefaults['login_wallpaper_overlay_color'],
                    $loginDefaults['login_wallpaper_overlay_color']
                );
                $loginWallpaperOverlayOpacity = brandkit_clamp_int(
                    $loginConfig['login_wallpaper_overlay_opacity'] ?? $loginDefaults['login_wallpaper_overlay_opacity'],
                    0,
                    100,
                    35
                );
                $loginTextColor = brandkit_normalize_hex_color(
                    $loginConfig['login_text_color'] ?? $loginDefaults['login_text_color'],
                    $loginDefaults['login_text_color']
                );
                $loginFieldStyle = $loginConfig['login_field_style'] ?? $loginDefaults['login_field_style'];
                if (!in_array($loginFieldStyle, ['solid', 'glass', 'soft', 'outline', 'underline', 'neon'], true)) {
                    $loginFieldStyle = $loginDefaults['login_field_style'];
                }
                $loginFieldBg = brandkit_normalize_hex_color(
                    $loginConfig['login_field_bg'] ?? $loginDefaults['login_field_bg'],
                    $loginDefaults['login_field_bg']
                );
                $loginFieldBorder = brandkit_normalize_hex_color(
                    $loginConfig['login_field_border'] ?? $loginDefaults['login_field_border'],
                    $loginDefaults['login_field_border']
                );
                $loginFieldBorderWidth = brandkit_clamp_int(
                    $loginConfig['login_field_border_width'] ?? $loginDefaults['login_field_border_width'],
                    0,
                    6,
                    1
                );
                $loginFieldTextSize = brandkit_clamp_int(
                    $loginConfig['login_field_text_size'] ?? $loginDefaults['login_field_text_size'],
                    10,
                    36,
                    14
                );
                $loginFieldHeight = brandkit_clamp_int(
                    $loginConfig['login_field_height'] ?? $loginDefaults['login_field_height'],
                    32,
                    72,
                    44
                );
                $loginFieldWidth = brandkit_clamp_int(
                    $loginConfig['login_field_width'] ?? $loginDefaults['login_field_width'],
                    260,
                    720,
                    520
                );
                $loginFieldSpacing = null;
                $loginTitleSize = null;
                if (!$isGlpi11) {
                    $loginFieldSpacing = brandkit_clamp_int(
                        $loginConfig['login_field_spacing'] ?? $loginDefaults['login_field_spacing'],
                        8,
                        48,
                        16
                    );
                    $loginTitleSize = brandkit_clamp_int(
                        $loginConfig['login_title_size'] ?? $loginDefaults['login_title_size'],
                        16,
                        48,
                        24
                    );
                }
                $loginTextSize = brandkit_clamp_int(
                    $loginConfig['login_text_size'] ?? $loginDefaults['login_text_size'],
                    10,
                    30,
                    15
                );
                $loginFieldText = brandkit_normalize_hex_color(
                    $loginConfig['login_field_text'] ?? $loginDefaults['login_field_text'],
                    $loginDefaults['login_field_text']
                );
                $loginFieldFocus = brandkit_normalize_hex_color(
                    $loginConfig['login_field_focus'] ?? $loginDefaults['login_field_focus'],
                    $loginDefaults['login_field_focus']
                );
                $loginAccentColor = brandkit_normalize_hex_color(
                    $loginConfig['login_accent_color'] ?? $loginDefaults['login_accent_color'],
                    $loginDefaults['login_accent_color']
                );
                $loginIconUser = $loginConfig['login_icon_user'] ?? $loginDefaults['login_icon_user'];
                $loginIconUserOptions = ['none', 'user', 'id', 'mail'];
                if (!in_array($loginIconUser, $loginIconUserOptions, true)) {
                    $loginIconUser = $loginDefaults['login_icon_user'];
                }
                $loginIconPassword = $loginConfig['login_icon_password'] ?? $loginDefaults['login_icon_password'];
                $loginIconPasswordOptions = ['none', 'lock', 'key', 'shield'];
                if (!in_array($loginIconPassword, $loginIconPasswordOptions, true)) {
                    $loginIconPassword = $loginDefaults['login_icon_password'];
                }
                $loginIconColor = brandkit_normalize_hex_color(
                    $loginConfig['login_icon_color'] ?? $loginDefaults['login_icon_color'],
                    $loginDefaults['login_icon_color']
                );
                $loginIconPosition = $loginConfig['login_icon_position'] ?? $loginDefaults['login_icon_position'];
                if (!in_array($loginIconPosition, ['left', 'right'], true)) {
                    $loginIconPosition = $loginDefaults['login_icon_position'];
                }
                $loginFieldRadius = brandkit_clamp_int(
                    $loginConfig['login_field_radius'] ?? $loginDefaults['login_field_radius'],
                    0,
                    32,
                    12
                );
                $loginHideLoginText = ($loginConfig['login_hide_login_text'] ?? $loginDefaults['login_hide_login_text']) === '1';
                $loginSso = brandkit_get_sso_settings();
                $ssoButtonColors = ($loginConfig['login_sso_button_colors'] ?? 'login') === 'custom' ? 'custom' : 'login';
                $ssoButtonBg = brandkit_normalize_hex_color($loginConfig['login_sso_button_bg'] ?? '', $loginDefaults['login_sso_button_bg']);
                $ssoButtonText = brandkit_normalize_hex_color($loginConfig['login_sso_button_text'] ?? '', $loginDefaults['login_sso_button_text']);
                $ssoButtonLabel = (string) ($loginConfig['login_sso_button_label'] ?? '');
                $ssoButtonIcon = (string) ($loginConfig['login_sso_button_icon'] ?? 'provider');
                $buttonStyleLabels = [
                    'primary' => __('Primary (default)', 'brandkit'),
                    'secondary' => __('Secondary', 'brandkit'),
                    'success' => __('Success', 'brandkit'),
                    'danger' => __('Danger', 'brandkit'),
                    'warning' => __('Warning', 'brandkit'),
                    'info' => __('Info', 'brandkit'),
                    'dark' => __('Dark', 'brandkit'),
                    'light' => __('Light', 'brandkit'),
                    'outline-primary' => __('Outline primary', 'brandkit'),
                    'outline-secondary' => __('Outline secondary', 'brandkit'),
                    'outline-success' => __('Outline success', 'brandkit'),
                    'outline-danger' => __('Outline danger', 'brandkit'),
                    'outline-warning' => __('Outline warning', 'brandkit'),
                    'outline-info' => __('Outline info', 'brandkit'),
                    'outline-dark' => __('Outline dark', 'brandkit'),
                ];
                $buttonShapeLabels = [
                    'square' => __('Square', 'brandkit'),
                    'rounded' => __('Rounded', 'brandkit'),
                    'pill' => __('Pill', 'brandkit'),
                    'cut' => __('Cut corner', 'brandkit'),
                    'soft' => __('Soft depth', 'brandkit'),
                ];
                $buttonShapeDescriptions = [
                    'square' => __('Sharp edges with a confident shadow.', 'brandkit'),
                    'rounded' => __('Balanced corners for modern brands.', 'brandkit'),
                    'pill' => __('Friendly, fully rounded shape.', 'brandkit'),
                    'cut' => __('Dynamic cut for a bolder call-to-action.', 'brandkit'),
                    'soft' => __('Cushioned look with soft depth.', 'brandkit'),
                ];
                $fieldStyleLabels = [
                    'solid' => __('Solid', 'brandkit'),
                    'glass' => __('Glass blur', 'brandkit'),
                    'soft' => __('Soft shadow', 'brandkit'),
                    'outline' => __('Outline', 'brandkit'),
                    'underline' => __('Underline', 'brandkit'),
                    'neon' => __('Neon glow', 'brandkit'),
                ];
                $buttonColorModeLabels = [
                    'preset' => __('Preset palette', 'brandkit'),
                    'custom' => __('Custom hex color', 'brandkit'),
                ];
                $buttonCustomStyleLabels = [
                    'default' => __('Default fill', 'brandkit'),
                    'outline' => __('Outline only', 'brandkit'),
                ];
                $buttonHoverEffectLabels = [
                    'press' => __('Press on hover', 'brandkit'),
                    'fill' => __('Fill with color', 'brandkit'),
                ];
                $buttonHoverFillModeLabels = [
                    'button' => __('Use button color', 'brandkit'),
                    'custom' => __('Pick a custom color', 'brandkit'),
                ];
                $iconUserLabels = [
                    'none' => __('None', 'brandkit'),
                    'user' => __('User', 'brandkit'),
                    'id' => __('ID card', 'brandkit'),
                    'mail' => __('Email', 'brandkit'),
                ];
                $iconPasswordLabels = [
                    'none' => __('None', 'brandkit'),
                    'lock' => __('Lock', 'brandkit'),
                    'key' => __('Key', 'brandkit'),
                    'shield' => __('Shield', 'brandkit'),
                ];
                $iconPositionLabels = [
                    'right' => __('Right', 'brandkit'),
                    'left' => __('Left', 'brandkit'),
                ];
                $iconSvgMap = [
                    'user' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c2-4 6-6 8-6s6 2 8 6"/></svg>',
                    'id' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 15h4"/><circle cx="10" cy="10" r="2.5"/></svg>',
                    'mail' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
                    'lock' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>',
                    'key' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="14.5" r="3.5"/><path d="M11 14h10l-2 2 2 2-2 2"/></svg>',
                    'shield' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 5 3.5 8.5 8 9 4.5-.5 8-4 8-9V6z"/><path d="M9.5 12.5 11 14l3.5-3.5"/></svg>',
                ];
                $wallpaperSizeLabels = [
                    'cover' => __('Cover', 'brandkit'),
                    'contain' => __('Contain', 'brandkit'),
                    'stretch' => __('Stretch', 'brandkit'),
                    'original' => __('Original size', 'brandkit'),
                ];
                $wallpaperPositionLabels = [
                    'center' => __('Center', 'brandkit'),
                    'top' => __('Top', 'brandkit'),
                    'bottom' => __('Bottom', 'brandkit'),
                    'left' => __('Left', 'brandkit'),
                    'right' => __('Right', 'brandkit'),
                ];
                $wallpaperAttachmentLabels = [
                    'fixed' => __('Fixed', 'brandkit'),
                    'scroll' => __('Scroll', 'brandkit'),
                ];
            ?>
            <?php
                $layoutLabels = [
                    'split-left' => __('Split left', 'brandkit'),
                    'classic' => __('Classic centered', 'brandkit'),
                    'split-right' => __('Split right', 'brandkit'),
                ];
                $selectedLayout = $loginConfig['login_layout'] ?? $loginDefaults['login_layout'];
                if (!in_array($selectedLayout, ['split-left', 'split-right', 'classic'], true)) {
                    $selectedLayout = $loginDefaults['login_layout'];
                }
            ?>
            <form id="brandkit-login-settings-form" method="post" enctype="multipart/form-data" action="<?php echo htmlescape($baseUrl); ?>?step=2">
                <div class="brandkit-step2-grid">
                    <div class="brandkit-step2-row">
                        <div class="brandkit-section">
                            <h4><?php echo htmlescape(__('Layout & logo', 'brandkit')); ?></h4>
                            <div class="brandkit-form-grid">
                                <div>
                                    <label for="brandkit_login_layout"><?php echo htmlescape(__('Layout', 'brandkit')); ?></label>
                                    <select id="brandkit_login_layout" name="login_layout">
                                        <?php foreach ($layoutLabels as $layoutValue => $layoutLabel) : ?>
                                            <option value="<?php echo htmlescape($layoutValue); ?>" <?php echo $selectedLayout === $layoutValue ? 'selected' : ''; ?>>
                                                <?php echo htmlescape($layoutLabel); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="brandkit_login_transparent"><?php echo htmlescape(__('Transparent panel', 'brandkit')); ?></label>
                                    <select id="brandkit_login_transparent" name="login_panel_transparent">
                                        <option value="0" <?php echo $loginConfig['login_panel_transparent'] === '0' ? 'selected' : ''; ?>><?php echo htmlescape(__('No (show panel)', 'brandkit')); ?></option>
                                        <option value="1" <?php echo $loginConfig['login_panel_transparent'] === '1' ? 'selected' : ''; ?>><?php echo htmlescape(__('Yes (hide panel)', 'brandkit')); ?></option>
                                    </select>
                                </div>
                                <div>
                                    <label for="brandkit_login_panel_hover"><?php echo htmlescape(__('Panel hover effect', 'brandkit')); ?></label>
                                    <select id="brandkit_login_panel_hover" name="login_panel_hover_effect">
                                        <option value="1" <?php echo $loginPanelHoverEffect ? 'selected' : ''; ?>><?php echo htmlescape(__('Yes (enable)', 'brandkit')); ?></option>
                                        <option value="0" <?php echo !$loginPanelHoverEffect ? 'selected' : ''; ?>><?php echo htmlescape(__('No (disable)', 'brandkit')); ?></option>
                                    </select>
                                </div>
                                <div class="brandkit-panel-size">
                                    <label for="brandkit_login_panel_width"><?php echo htmlescape(__('Panel width (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_panel_width" name="login_panel_width" min="280" max="720" step="10" value="<?php echo htmlescape($loginPanelWidth); ?>" oninput="document.getElementById('brandkit_login_panel_width_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_panel_width_value"><?php echo htmlescape($loginPanelWidth); ?>px</span>
                                    </div>
                                </div>

                                <div class="brandkit-panel-size">
                                    <label for="brandkit_login_panel_height"><?php echo htmlescape(__('Panel height (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_panel_height" name="login_panel_height" min="360" max="780" step="10" value="<?php echo htmlescape($loginPanelHeight); ?>" oninput="document.getElementById('brandkit_login_panel_height_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_panel_height_value"><?php echo htmlescape($loginPanelHeight); ?>px</span>
                                    </div>
                                </div>
                                <div>
                                    <label for="brandkit_login_logo_scale"><?php echo htmlescape(__('Login logo size (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_logo_scale" name="login_logo_scale" min="80" max="480" step="10" value="<?php echo htmlescape($loginLogoScale); ?>" oninput="document.getElementById('brandkit_login_logo_scale_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_logo_scale_value"><?php echo htmlescape($loginLogoScale); ?>px</span>
                                    </div>
                                </div>
                                <div>
                                    <label for="brandkit_login_logo_position"><?php echo htmlescape(__('Logo position', 'brandkit')); ?></label>
                                    <select id="brandkit_login_logo_position" name="login_logo_position">
                                        <option value="outside" <?php echo $loginLogoPosition === 'outside' ? 'selected' : ''; ?>><?php echo htmlescape(__('Above panel', 'brandkit')); ?></option>
                                        <option value="inside" <?php echo $loginLogoPosition === 'inside' ? 'selected' : ''; ?>><?php echo htmlescape(__('Inside panel', 'brandkit')); ?></option>
                                    </select>
                                </div>
                                <div class="brandkit-logo-offset-field">
                                    <label for="brandkit_login_logo_offset"><?php echo htmlescape(__('Logo offset from top (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_logo_offset" name="login_logo_offset" min="0" max="240" step="5" value="<?php echo htmlescape($loginLogoOffset); ?>" oninput="document.getElementById('brandkit_login_logo_offset_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_logo_offset_value"><?php echo htmlescape($loginLogoOffset); ?>px</span>
                                    </div>
                                </div>
                                <div>
                                    <label for="brandkit_login_accent_color"><?php echo htmlescape(__('Accent color', 'brandkit')); ?></label>
                                    <input type="color" id="brandkit_login_accent_color" name="login_accent_color" value="<?php echo htmlescape($loginAccentColor); ?>">
                                </div>
                                <div>
                                    <label for="brandkit_login_text_color"><?php echo htmlescape(__('Text color', 'brandkit')); ?></label>
                                    <input type="color" id="brandkit_login_text_color" name="login_text_color" value="<?php echo htmlescape($loginTextColor); ?>">
                                </div>
                                <div>
                                    <label for="brandkit_hide_login_text"><?php echo htmlescape(__('Hide login text', 'brandkit')); ?></label>
                                    <select id="brandkit_hide_login_text" name="login_hide_login_text">
                                        <option value="0" <?php echo !$loginHideLoginText ? 'selected' : ''; ?>><?php echo htmlescape(__('No', 'brandkit')); ?></option>
                                        <option value="1" <?php echo $loginHideLoginText ? 'selected' : ''; ?>><?php echo htmlescape(__('Yes', 'brandkit')); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="brandkit-step2-preview">
                            <div class="brandkit-preview-shell" data-brandkit-preview="layout" data-layout="<?php echo htmlescape($selectedLayout); ?>">
                                <div class="brandkit-preview-split"></div>
                                <div class="brandkit-preview-panel">
                                    <div class="brandkit-preview-logo" data-brandkit-preview-logo>Logo</div>
                                    <div class="brandkit-preview-login">
                                        <div class="brandkit-preview-title" data-brandkit-preview-title><?php echo htmlescape(__('Sign in to your account', 'brandkit')); ?></div>
                                        <div>
                                            <div class="brandkit-preview-field-label" data-brandkit-preview-text><?php echo htmlescape(__('Login', 'brandkit')); ?></div>
                                            <div class="brandkit-preview-field"></div>
                                        </div>
                                        <div>
                                            <div class="brandkit-preview-field-label" data-brandkit-preview-text><?php echo htmlescape(__('Password', 'brandkit')); ?></div>
                                            <div class="brandkit-preview-field"></div>
                                        </div>
                                        <button type="button" class="brandkit-preview-button" data-brandkit-preview-button><?php echo htmlescape(__('Sign in', 'brandkit')); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="brandkit-step2-row" id="brandkit_panel_split_section">
                        <div class="brandkit-section">
                            <h4><?php echo htmlescape(__('Panel & split styling', 'brandkit')); ?></h4>
                            <div class="brandkit-form-grid">
                                <div class="brandkit-panel-style-controls">
                                    <label for="brandkit_login_opacity"><?php echo htmlescape(__('Panel opacity (%)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                    <input type="range" id="brandkit_login_opacity" name="login_panel_opacity" min="0" max="100" value="<?php echo htmlescape($loginConfig['login_panel_opacity']); ?>" oninput="document.getElementById('brandkit_login_opacity_value').textContent = this.value + '%'">
                                        <span class="brandkit-range-value" id="brandkit_login_opacity_value"><?php echo htmlescape($loginConfig['login_panel_opacity']); ?>%</span>
                                    </div>
                                </div>
                                <div class="brandkit-panel-style-controls">
                                    <label for="brandkit_login_color"><?php echo htmlescape(__('Panel color', 'brandkit')); ?></label>
                                    <input type="color" id="brandkit_login_color" name="login_panel_color" value="<?php echo htmlescape($loginConfig['login_panel_color']); ?>">
                                </div>
                                <div class="brandkit-panel-style-controls">
                                    <label for="brandkit_login_blur"><?php echo htmlescape(__('Panel blur (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_blur" name="login_panel_blur" min="0" max="24" value="<?php echo htmlescape($loginConfig['login_panel_blur']); ?>" oninput="document.getElementById('brandkit_login_blur_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_blur_value"><?php echo htmlescape($loginConfig['login_panel_blur']); ?>px</span>
                                    </div>
                                </div>
                            </div>
                            <div class="brandkit-form-grid brandkit-split-controls" id="brandkit_split_controls">
                                <div>
                                    <label for="brandkit_split_width"><?php echo htmlescape(__('Split width (%)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_split_width" name="login_split_width" min="0" max="100" value="<?php echo htmlescape($loginConfig['login_split_width']); ?>" oninput="document.getElementById('brandkit_split_width_value').textContent = this.value + '%'">
                                        <span class="brandkit-range-value" id="brandkit_split_width_value"><?php echo htmlescape($loginConfig['login_split_width']); ?>%</span>
                                    </div>
                                </div>
                                <div>
                                    <label for="brandkit_split_opacity"><?php echo htmlescape(__('Split opacity (%)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                    <input type="range" id="brandkit_split_opacity" name="login_split_opacity" min="0" max="100" value="<?php echo htmlescape($loginConfig['login_split_opacity']); ?>" oninput="document.getElementById('brandkit_split_opacity_value').textContent = this.value + '%'">
                                        <span class="brandkit-range-value" id="brandkit_split_opacity_value"><?php echo htmlescape($loginConfig['login_split_opacity']); ?>%</span>
                                    </div>
                                </div>
                                <div>
                                    <label for="brandkit_split_color"><?php echo htmlescape(__('Split color', 'brandkit')); ?></label>
                                    <input type="color" id="brandkit_split_color" name="login_split_color" value="<?php echo htmlescape($loginConfig['login_split_color']); ?>">
                                </div>
                                <div>
                                    <label for="brandkit_split_blur"><?php echo htmlescape(__('Split blur (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                    <input type="range" id="brandkit_split_blur" name="login_split_blur" min="0" max="24" value="<?php echo htmlescape($loginConfig['login_split_blur']); ?>" oninput="document.getElementById('brandkit_split_blur_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_split_blur_value"><?php echo htmlescape($loginConfig['login_split_blur']); ?>px</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="brandkit-step2-preview" id="brandkit_panel_split_preview">
                            <div class="brandkit-preview-shell" data-brandkit-preview="panel" data-layout="<?php echo htmlescape($selectedLayout); ?>">
                                <div class="brandkit-preview-split"></div>
                                <div class="brandkit-preview-panel">
                                    <div class="brandkit-preview-login">
                                        <div>
                                            <div class="brandkit-preview-field-label" data-brandkit-preview-text><?php echo htmlescape(__('Login', 'brandkit')); ?></div>
                                            <div class="brandkit-preview-field"></div>
                                        </div>
                                        <div>
                                            <div class="brandkit-preview-field-label" data-brandkit-preview-text><?php echo htmlescape(__('Password', 'brandkit')); ?></div>
                                            <div class="brandkit-preview-field"></div>
                                        </div>
                                        <button type="button" class="brandkit-preview-button" data-brandkit-preview-button><?php echo htmlescape(__('Sign in', 'brandkit')); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="brandkit-step2-row">
                        <div class="brandkit-section">
                            <h4><?php echo htmlescape(__('Button', 'brandkit')); ?></h4>
                            <div class="brandkit-form-grid brandkit-form-grid--button">
                                <div>
                                    <label for="brandkit_login_button_shape"><?php echo htmlescape(__('Button shape', 'brandkit')); ?></label>
                                    <select id="brandkit_login_button_shape" name="login_button_shape">
                                        <?php foreach (brandkit_get_login_button_shapes() as $shape) : ?>
                                            <option value="<?php echo htmlescape($shape); ?>" <?php echo $loginButtonShape === $shape ? 'selected' : ''; ?>>
                                                <?php echo htmlescape($buttonShapeLabels[$shape] ?? $shape); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <input type="hidden" id="brandkit_login_button_style" name="login_button_style" value="<?php echo htmlescape($loginButtonStyle); ?>">
                                <div>
                                    <label for="brandkit_login_button_custom_style"><?php echo htmlescape(__('Button fill style', 'brandkit')); ?></label>
                                    <select id="brandkit_login_button_custom_style" name="login_button_custom_style">
                                        <?php foreach ($buttonCustomStyleLabels as $styleValue => $styleLabel) : ?>
                                            <option value="<?php echo htmlescape($styleValue); ?>" <?php echo $loginButtonCustomStyle === $styleValue ? 'selected' : ''; ?>>
                                                <?php echo htmlescape($styleLabel); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="brandkit_login_button_text_size"><?php echo htmlescape(__('Button text size (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_button_text_size" name="login_button_text_size" min="10" max="28" step="1" value="<?php echo htmlescape($loginButtonTextSize); ?>" oninput="document.getElementById('brandkit_login_button_text_size_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_button_text_size_value"><?php echo htmlescape($loginButtonTextSize); ?>px</span>
                                    </div>
                                </div>
                                <div>
                                    <label for="brandkit_login_button_color_mode"><?php echo htmlescape(__('Color mode', 'brandkit')); ?></label>
                                    <select id="brandkit_login_button_color_mode" name="login_button_color_mode">
                                        <?php foreach ($buttonColorModeLabels as $colorModeValue => $colorModeLabel) : ?>
                                            <option value="<?php echo htmlescape($colorModeValue); ?>" <?php echo $loginButtonColorMode === $colorModeValue ? 'selected' : ''; ?>>
                                                <?php echo htmlescape($colorModeLabel); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="brandkit_button_color_custom" class="brandkit-color-custom">
                                    <div class="brandkit-color-inputs">
                                        <div class="brandkit-inline-field">
                                            <label for="brandkit_login_button_color_picker"><?php echo htmlescape(__('Custom color', 'brandkit')); ?></label>
                                            <input type="color" id="brandkit_login_button_color_picker" name="login_button_color" value="<?php echo htmlescape($loginButtonColor); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label for="brandkit_login_button_hover_effect"><?php echo htmlescape(__('Hover effect', 'brandkit')); ?></label>
                                    <select id="brandkit_login_button_hover_effect" name="login_button_hover_effect">
                                        <?php foreach ($buttonHoverEffectLabels as $effectValue => $effectLabel) : ?>
                                            <option value="<?php echo htmlescape($effectValue); ?>" <?php echo $loginButtonHoverEffect === $effectValue ? 'selected' : ''; ?>>
                                                <?php echo htmlescape($effectLabel); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="brandkit_button_hover_options" class="brandkit-color-custom">
                                    <input type="hidden" id="brandkit_login_button_hover_fill_mode" name="login_button_hover_fill_mode" value="custom">
                                    <div id="brandkit_button_hover_fill_custom" class="brandkit-color-inputs">
                                        <div class="brandkit-inline-field">
                                            <label for="brandkit_login_button_hover_fill_color"><?php echo htmlescape(__('Hover fill custom color', 'brandkit')); ?></label>
                                            <input type="color" id="brandkit_login_button_hover_fill_color" name="login_button_hover_fill_color" value="<?php echo htmlescape($loginButtonHoverFillColor); ?>">
                                        </div>
                                        <div class="brandkit-inline-field">
                                            <label for="brandkit_login_button_hover_text_color"><?php echo htmlescape(__('Hover text color', 'brandkit')); ?></label>
                                            <input type="color" id="brandkit_login_button_hover_text_color" name="login_button_hover_text_color" value="<?php echo htmlescape($loginButtonHoverTextColor); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="brandkit-step2-preview">
                            <div class="brandkit-button-stage" data-brandkit-preview="button">
                                <div class="brandkit-button-panel">
                                    <button type="button" class="brandkit-button-demo brandkit-button-demo--rounded" data-brandkit-preview-button>
                                        <?php echo htmlescape(__('Sign in', 'brandkit')); ?>
                                    </button>
                                    <div class="brandkit-button-panel-line"></div>
                                    <div class="brandkit-button-panel-line" style="width: 70%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="brandkit-step2-row">
                        <div class="brandkit-section">
                            <h4><?php echo htmlescape(__('Fields', 'brandkit')); ?></h4>
                            <div class="brandkit-form-grid">
                                <div>
                                    <label for="brandkit_login_field_style"><?php echo htmlescape(__('Field style', 'brandkit')); ?></label>
                                    <select id="brandkit_login_field_style" name="login_field_style">
                                        <?php foreach ($fieldStyleLabels as $styleValue => $styleLabel) : ?>
                                            <option value="<?php echo htmlescape($styleValue); ?>" <?php echo $loginFieldStyle === $styleValue ? 'selected' : ''; ?>>
                                                <?php echo htmlescape($styleLabel); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="brandkit_login_field_bg"><?php echo htmlescape(__('Field background', 'brandkit')); ?></label>
                                    <input type="color" id="brandkit_login_field_bg" name="login_field_bg" value="<?php echo htmlescape($loginFieldBg); ?>">
                                </div>
                                <div>
                                    <label for="brandkit_login_field_border"><?php echo htmlescape(__('Field border', 'brandkit')); ?></label>
                                    <input type="color" id="brandkit_login_field_border" name="login_field_border" value="<?php echo htmlescape($loginFieldBorder); ?>">
                                </div>
                                <div>
                                    <label for="brandkit_login_field_border_width"><?php echo htmlescape(__('Field border width (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_field_border_width" name="login_field_border_width" min="0" max="6" step="1" value="<?php echo htmlescape($loginFieldBorderWidth); ?>" oninput="document.getElementById('brandkit_login_field_border_width_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_field_border_width_value"><?php echo htmlescape($loginFieldBorderWidth); ?>px</span>
                                    </div>
                                </div>
                                <div>
                                    <label for="brandkit_login_field_text"><?php echo htmlescape(__('Field text', 'brandkit')); ?></label>
                                    <input type="color" id="brandkit_login_field_text" name="login_field_text" value="<?php echo htmlescape($loginFieldText); ?>">
                                </div>
                                <div>
                                    <label for="brandkit_login_text_size"><?php echo htmlescape(__('Page text size (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_text_size" name="login_text_size" min="10" max="30" step="1" value="<?php echo htmlescape($loginTextSize); ?>" oninput="document.getElementById('brandkit_login_text_size_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_text_size_value"><?php echo htmlescape($loginTextSize); ?>px</span>
                                    </div>
                                </div>
                                <?php if (!$isGlpi11) : ?>
                                <div>
                                    <label for="brandkit_login_title_size"><?php echo htmlescape(__('Title text size (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_title_size" name="login_title_size" min="16" max="48" step="1" value="<?php echo htmlescape($loginTitleSize); ?>" oninput="document.getElementById('brandkit_login_title_size_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_title_size_value"><?php echo htmlescape($loginTitleSize); ?>px</span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <label for="brandkit_login_field_text_size"><?php echo htmlescape(__('Field text size (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_field_text_size" name="login_field_text_size" min="10" max="36" step="1" value="<?php echo htmlescape($loginFieldTextSize); ?>" oninput="document.getElementById('brandkit_login_field_text_size_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_field_text_size_value"><?php echo htmlescape($loginFieldTextSize); ?>px</span>
                                    </div>
                                </div>
                                <div>
                                    <label for="brandkit_login_field_height"><?php echo htmlescape(__('Field height (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_field_height" name="login_field_height" min="32" max="72" step="1" value="<?php echo htmlescape($loginFieldHeight); ?>" oninput="document.getElementById('brandkit_login_field_height_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_field_height_value"><?php echo htmlescape($loginFieldHeight); ?>px</span>
                                    </div>
                                </div>
                                <div>
                                    <label for="brandkit_login_field_width"><?php echo htmlescape(__('Field width (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_field_width" name="login_field_width" min="260" max="720" step="10" value="<?php echo htmlescape($loginFieldWidth); ?>" oninput="document.getElementById('brandkit_login_field_width_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_field_width_value"><?php echo htmlescape($loginFieldWidth); ?>px</span>
                                    </div>
                                </div>
                                <?php if (!$isGlpi11) : ?>
                                <div>
                                    <label for="brandkit_login_field_spacing"><?php echo htmlescape(__('Field spacing (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_field_spacing" name="login_field_spacing" min="8" max="48" step="1" value="<?php echo htmlescape($loginFieldSpacing); ?>" oninput="document.getElementById('brandkit_login_field_spacing_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_field_spacing_value"><?php echo htmlescape($loginFieldSpacing); ?>px</span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="brandkit-field-focus">
                                    <label for="brandkit_login_field_focus"><?php echo htmlescape(__('Field focus glow', 'brandkit')); ?></label>
                                    <input type="color" id="brandkit_login_field_focus" name="login_field_focus" value="<?php echo htmlescape($loginFieldFocus); ?>">
                                </div>
                                <div>
                                    <label for="brandkit_login_field_radius"><?php echo htmlescape(__('Field radius (px)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                        <input type="range" id="brandkit_login_field_radius" name="login_field_radius" min="0" max="32" step="1" value="<?php echo htmlescape($loginFieldRadius); ?>" oninput="document.getElementById('brandkit_login_field_radius_value').textContent = this.value + 'px'">
                                        <span class="brandkit-range-value" id="brandkit_login_field_radius_value"><?php echo htmlescape($loginFieldRadius); ?>px</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="brandkit-step2-preview">
                            <div class="brandkit-preview-shell brandkit-preview-fields" data-brandkit-preview="fields">
                                <div class="brandkit-preview-field">
                                    <span class="brandkit-preview-field-text"><?php echo htmlescape(__('Login', 'brandkit')); ?></span>
                                </div>
                                <div class="brandkit-preview-field">
                                    <span class="brandkit-preview-field-text"><?php echo htmlescape(__('Password', 'brandkit')); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="brandkit-step2-row">
                        <div class="brandkit-section">
                            <h4><?php echo htmlescape(__('Field icons', 'brandkit')); ?></h4>
                            <?php $iconGridBg = brandkit_is_light_color($loginIconColor) ? '#0f172a' : '#ffffff'; ?>
                            <div class="brandkit-form-grid brandkit-icon-grid-scope" style="--brandkit-icon-color: <?php echo htmlescape($loginIconColor); ?>; --brandkit-icon-bg: <?php echo htmlescape($iconGridBg); ?>;">
                                <div>
                                    <label><?php echo htmlescape(__('Login icon', 'brandkit')); ?></label>
                                    <div class="brandkit-icon-grid">
                                        <?php foreach ($iconUserLabels as $value => $label) : ?>
                                            <label class="brandkit-icon-option">
                                                <input type="radio" name="login_icon_user" value="<?php echo htmlescape($value); ?>" <?php echo $loginIconUser === $value ? 'checked' : ''; ?>>
                                                <div class="brandkit-icon-card">
                                                    <?php if ($value === 'none') : ?>
                                                        <div class="brandkit-icon-placeholder"></div>
                                                        <span><?php echo htmlescape($label); ?></span>
                                                    <?php else : ?>
                                                        <?php echo $iconSvgMap[$value] ?? ''; ?>
                                                        <span><?php echo htmlescape($label); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div>
                                    <label><?php echo htmlescape(__('Password icon', 'brandkit')); ?></label>
                                    <div class="brandkit-icon-grid">
                                        <?php foreach ($iconPasswordLabels as $value => $label) : ?>
                                            <label class="brandkit-icon-option">
                                                <input type="radio" name="login_icon_password" value="<?php echo htmlescape($value); ?>" <?php echo $loginIconPassword === $value ? 'checked' : ''; ?>>
                                                <div class="brandkit-icon-card">
                                                    <?php if ($value === 'none') : ?>
                                                        <div class="brandkit-icon-placeholder"></div>
                                                        <span><?php echo htmlescape($label); ?></span>
                                                    <?php else : ?>
                                                        <?php echo $iconSvgMap[$value] ?? ''; ?>
                                                        <span><?php echo htmlescape($label); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div>
                                    <label for="brandkit_login_icon_color"><?php echo htmlescape(__('Icon color', 'brandkit')); ?></label>
                                    <input type="color" id="brandkit_login_icon_color" name="login_icon_color" value="<?php echo htmlescape($loginIconColor); ?>">
                                </div>
                                <div>
                                    <label for="brandkit_login_icon_position"><?php echo htmlescape(__('Icon position', 'brandkit')); ?></label>
                                    <select id="brandkit_login_icon_position" name="login_icon_position">
                                        <?php foreach ($iconPositionLabels as $value => $label) : ?>
                                            <option value="<?php echo htmlescape($value); ?>" <?php echo $loginIconPosition === $value ? 'selected' : ''; ?>>
                                                <?php echo htmlescape($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="brandkit-step2-preview">
                            <div class="brandkit-preview-shell brandkit-preview-icons<?php echo $loginIconPosition === 'right' ? ' icon-right' : ''; ?>" data-brandkit-preview="icons" data-login-icon="<?php echo htmlescape($loginIconUser); ?>" data-password-icon="<?php echo htmlescape($loginIconPassword); ?>">
                                <div class="brandkit-preview-icon-field">
                                    <div class="brandkit-preview-field brandkit-preview-field--icon" data-field="login">
                                        <span class="brandkit-preview-field-icon" data-brandkit-icon="user"><?php echo $iconSvgMap['user'] ?? ''; ?></span>
                                        <span class="brandkit-preview-field-icon" data-brandkit-icon="id"><?php echo $iconSvgMap['id'] ?? ''; ?></span>
                                        <span class="brandkit-preview-field-icon" data-brandkit-icon="mail"><?php echo $iconSvgMap['mail'] ?? ''; ?></span>
                                        <span class="brandkit-preview-field-text"><?php echo htmlescape(__('Login', 'brandkit')); ?></span>
                                    </div>
                                </div>
                                <div class="brandkit-preview-icon-field">
                                    <div class="brandkit-preview-field brandkit-preview-field--icon" data-field="password">
                                        <span class="brandkit-preview-field-icon" data-brandkit-icon="lock"><?php echo $iconSvgMap['lock'] ?? ''; ?></span>
                                        <span class="brandkit-preview-field-icon" data-brandkit-icon="key"><?php echo $iconSvgMap['key'] ?? ''; ?></span>
                                        <span class="brandkit-preview-field-icon" data-brandkit-icon="shield"><?php echo $iconSvgMap['shield'] ?? ''; ?></span>
                                        <span class="brandkit-preview-field-text"><?php echo htmlescape(__('Password', 'brandkit')); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="brandkit-step2-row">
                        <div class="brandkit-section">
                            <h4>
                                <?php echo htmlescape(__('Wallpaper', 'brandkit')); ?>
                                <span
                                    class="brandkit-info"
                                    title="<?php echo htmlescape(__('Recommended: 1920x1080 or larger.', 'brandkit')); ?>"
                                    data-info="<?php echo htmlescape(__('Recommended: 1920x1080 or larger.', 'brandkit')); ?>"
                                    aria-label="<?php echo htmlescape(__('Recommended: 1920x1080 or larger.', 'brandkit')); ?>"
                                    role="img"
                                    tabindex="0"
                                >i</span>
                            </h4>
                            <div class="brandkit-form-grid" style="margin-top: 12px;">
                                <div class="brandkit-grid-full">
                                    <label for="brandkit_wallpaper_source"><?php echo htmlescape(__('Wallpaper source', 'brandkit')); ?></label>
                                    <select id="brandkit_wallpaper_source" name="login_wallpaper_source">
                                        <option value="preset" <?php echo $wallpaperSource === 'preset' ? 'selected' : ''; ?>><?php echo htmlescape(__('Preset wallpapers', 'brandkit')); ?></option>
                                        <option value="custom" <?php echo $wallpaperSource === 'custom' ? 'selected' : ''; ?>><?php echo htmlescape(__('Custom upload', 'brandkit')); ?></option>
                                    </select>
                                </div>
                                <div id="brandkit_wallpaper_preset_wrap" class="brandkit-grid-full">
                                    <label for="brandkit_wallpaper_preset"><?php echo htmlescape(__('Preset wallpaper', 'brandkit')); ?></label>
                                    <select id="brandkit_wallpaper_preset" name="login_wallpaper_preset">
                                        <?php if (empty($wallpaperPresets)) : ?>
                                            <option value="" selected><?php echo htmlescape(__('No preset wallpapers available.', 'brandkit')); ?></option>
                                        <?php else : ?>
                                            <?php foreach ($wallpaperPresets as $presetFile) : ?>
                                                <?php
                                                    $presetLabel = $wallpaperPresetLabels[$presetFile] ?? $presetFile;
                                                    $presetAssets = brandkit_get_wallpaper_preset_assets($presetFile);
                                                    $presetPreviewUrl = $presetAssets['url'] ?? '';
                                                ?>
                                                <?php $presetPreviewWebp = $presetAssets['webp_url'] ?? ''; ?>
                                                <option
                                                    value="<?php echo htmlescape($presetFile); ?>"
                                                    data-preview-url="<?php echo htmlescape($presetPreviewUrl); ?>"
                                                    data-preview-webp-url="<?php echo htmlescape($presetPreviewWebp); ?>"
                                                    <?php echo $wallpaperPreset === $presetFile ? 'selected' : ''; ?>
                                                >
                                                    <?php echo htmlescape($presetLabel); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="brandkit_wallpaper_size"><?php echo htmlescape(__('Size', 'brandkit')); ?></label>
                                    <select id="brandkit_wallpaper_size" name="login_wallpaper_size">
                                        <?php foreach ($wallpaperSizeLabels as $value => $label) : ?>
                                            <option value="<?php echo htmlescape($value); ?>" <?php echo $loginWallpaperSize === $value ? 'selected' : ''; ?>>
                                                <?php echo htmlescape($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="brandkit_wallpaper_position"><?php echo htmlescape(__('Position', 'brandkit')); ?></label>
                                    <select id="brandkit_wallpaper_position" name="login_wallpaper_position">
                                        <?php foreach ($wallpaperPositionLabels as $value => $label) : ?>
                                            <option value="<?php echo htmlescape($value); ?>" <?php echo $loginWallpaperPosition === $value ? 'selected' : ''; ?>>
                                                <?php echo htmlescape($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="brandkit_wallpaper_attachment"><?php echo htmlescape(__('Attachment', 'brandkit')); ?></label>
                                    <select id="brandkit_wallpaper_attachment" name="login_wallpaper_attachment">
                                        <?php foreach ($wallpaperAttachmentLabels as $value => $label) : ?>
                                            <option value="<?php echo htmlescape($value); ?>" <?php echo $loginWallpaperAttachment === $value ? 'selected' : ''; ?>>
                                                <?php echo htmlescape($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="brandkit_wallpaper_overlay_color"><?php echo htmlescape(__('Overlay color', 'brandkit')); ?></label>
                                    <input type="color" id="brandkit_wallpaper_overlay_color" name="login_wallpaper_overlay_color" value="<?php echo htmlescape($loginWallpaperOverlayColor); ?>">
                                </div>
                                <div>
                                    <label for="brandkit_wallpaper_overlay_opacity"><?php echo htmlescape(__('Overlay opacity (%)', 'brandkit')); ?></label>
                                    <div class="brandkit-range">
                                    <input type="range" id="brandkit_wallpaper_overlay_opacity" name="login_wallpaper_overlay_opacity" min="0" max="100" value="<?php echo htmlescape($loginWallpaperOverlayOpacity); ?>" oninput="document.getElementById('brandkit_wallpaper_overlay_opacity_value').textContent = this.value + '%'">
                                        <span class="brandkit-range-value" id="brandkit_wallpaper_overlay_opacity_value"><?php echo htmlescape($loginWallpaperOverlayOpacity); ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="brandkit-step2-row">
                        <div class="brandkit-section brandkit-section-wide">
                            <div class="brandkit-wallpaper-card" data-wallpaper-source="<?php echo htmlescape($wallpaperSource); ?>">
                                <div class="brandkit-wallpaper-actions">
                                    <label class="brandkit-logo-action brandkit-logo-upload" data-brandkit-wallpaper-upload for="brandkit_login_wallpaper" aria-label="<?php echo htmlescape(__('Upload wallpaper', 'brandkit')); ?>">&#x2B06;</label>
                                    <?php if ($customWallpaperUrl) : ?>
                                        <button type="button" class="brandkit-logo-action brandkit-logo-remove" id="brandkit_login_wallpaper_delete" aria-label="<?php echo htmlescape(__('Remove uploaded wallpaper', 'brandkit')); ?>">×</button>
                                    <?php endif; ?>
                                </div>
                                <input class="brandkit-logo-input" type="file" id="brandkit_login_wallpaper" name="login_wallpaper" accept="image/png,image/jpeg,image/webp">
                                <input class="brandkit-toggle-hidden" type="checkbox" id="brandkit_login_wallpaper_remove" name="login_wallpaper_remove" value="1">
                                <div
                                    class="brandkit-wallpaper-preview"
                                    data-brandkit-wallpaper-preview
                                    data-wallpaper-image="<?php echo htmlescape($loginWallpaperUrl); ?>"
                                    data-wallpaper-image-webp="<?php echo htmlescape($loginWallpaperWebpUrl ?? ''); ?>"
                                    data-wallpaper-custom="<?php echo htmlescape($customWallpaperUrl ?? ''); ?>"
                                    data-wallpaper-custom-webp="<?php echo htmlescape($customWallpaperWebpUrl ?? ''); ?>"
                                >
                                    <?php if (!$loginWallpaperUrl) : ?>
                                        <div class="brandkit-wallpaper-placeholder"><?php echo htmlescape(__('No wallpaper selected yet.', 'brandkit')); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="brandkit-step2-row">
                        <div class="brandkit-section brandkit-section-wide" id="brandkit_sso_section">
                            <h4>
                                <?php echo htmlescape(__('Single sign-on (SSO)', 'brandkit')); ?>
                                <?php brandkit_render_info_badge(__('For SSO plugins that add a login button to the GLPI login page, such as samlSSO.', 'brandkit')); ?>
                            </h4>
                            <div class="brandkit-form-grid">
                                <div>
                                    <label for="brandkit_login_sso_mode"><?php echo htmlescape(__('SSO mode', 'brandkit')); ?></label>
                                    <select id="brandkit_login_sso_mode" name="login_sso_mode">
                                        <option value="0" <?php echo !$loginSso['enabled'] ? 'selected' : ''; ?>><?php echo htmlescape(__('No (show the login form)', 'brandkit')); ?></option>
                                        <option value="1" <?php echo $loginSso['enabled'] ? 'selected' : ''; ?>><?php echo htmlescape(__('Yes (only the SSO button)', 'brandkit')); ?></option>
                                    </select>
                                </div>
                                <div>
                                    <label for="brandkit_login_sso_local_link"><?php echo htmlescape(__('Show the local account link', 'brandkit')); ?></label>
                                    <select id="brandkit_login_sso_local_link" name="login_sso_local_link">
                                        <option value="1" <?php echo $loginSso['local_link'] ? 'selected' : ''; ?>><?php echo htmlescape(__('Yes', 'brandkit')); ?></option>
                                        <option value="0" <?php echo !$loginSso['local_link'] ? 'selected' : ''; ?>><?php echo htmlescape(__('No (only through the address below)', 'brandkit')); ?></option>
                                    </select>
                                </div>
                                <div>
                                    <label for="brandkit_login_sso_title"><?php echo htmlescape(__('Text above the SSO button (optional)', 'brandkit')); ?></label>
                                    <input type="text" id="brandkit_login_sso_title" name="login_sso_title" maxlength="120" value="<?php echo htmlescape($loginSso['title']); ?>" placeholder="<?php echo htmlescape(__('e.g. Sign in with your corporate account', 'brandkit')); ?>">
                                </div>
                                <div>
                                    <label for="brandkit_login_sso_button_label"><?php echo htmlescape(__('SSO button text (optional)', 'brandkit')); ?></label>
                                    <input type="text" id="brandkit_login_sso_button_label" name="login_sso_button_label" maxlength="60" value="<?php echo htmlescape($ssoButtonLabel); ?>" placeholder="<?php echo htmlescape(__('e.g. Sign in with Microsoft', 'brandkit')); ?>">
                                </div>
                                <div>
                                    <label for="brandkit_login_sso_button_icon"><?php echo htmlescape(__('SSO button icon', 'brandkit')); ?></label>
                                    <select id="brandkit_login_sso_button_icon" name="login_sso_button_icon">
                                        <option value="provider" <?php echo $ssoButtonIcon === 'provider' ? 'selected' : ''; ?>><?php echo htmlescape(__('From the SSO plugin', 'brandkit')); ?></option>
                                        <option value="microsoft" <?php echo $ssoButtonIcon === 'microsoft' ? 'selected' : ''; ?>><?php echo htmlescape(__('Microsoft logo (colored)', 'brandkit')); ?></option>
                                        <option value="none" <?php echo $ssoButtonIcon === 'none' ? 'selected' : ''; ?>><?php echo htmlescape(__('No icon', 'brandkit')); ?></option>
                                    </select>
                                </div>
                                <div>
                                    <label for="brandkit_login_sso_button_colors"><?php echo htmlescape(__('SSO button colors', 'brandkit')); ?></label>
                                    <select id="brandkit_login_sso_button_colors" name="login_sso_button_colors">
                                        <option value="login" <?php echo $ssoButtonColors === 'login' ? 'selected' : ''; ?>><?php echo htmlescape(__('Same as the login button', 'brandkit')); ?></option>
                                        <option value="custom" <?php echo $ssoButtonColors === 'custom' ? 'selected' : ''; ?>><?php echo htmlescape(__('Custom', 'brandkit')); ?></option>
                                    </select>
                                </div>
                                <div class="brandkit-sso-custom-color">
                                    <label for="brandkit_login_sso_button_bg"><?php echo htmlescape(__('SSO button color', 'brandkit')); ?></label>
                                    <input type="color" id="brandkit_login_sso_button_bg" name="login_sso_button_bg" value="<?php echo htmlescape($ssoButtonBg); ?>">
                                </div>
                                <div class="brandkit-sso-custom-color">
                                    <label for="brandkit_login_sso_button_text"><?php echo htmlescape(__('SSO button text color', 'brandkit')); ?></label>
                                    <input type="color" id="brandkit_login_sso_button_text" name="login_sso_button_text" value="<?php echo htmlescape($ssoButtonText); ?>">
                                </div>
                            </div>
                            <div class="brandkit-sso-help">
                                <p><?php echo htmlescape(__('With SSO mode on, the login page hides the user and password fields and shows the SSO button in their place. The fields are only hidden while an SSO button is on the page: if the SSO plugin is disabled, the normal form comes back.', 'brandkit')); ?></p>
                                <p>
                                    <strong><?php echo htmlescape(__('Local account access:', 'brandkit')); ?></strong>
                                    <code><?php echo htmlescape(brandkit_get_sso_bypass_url(true)); ?></code>
                                </p>
                                <p><?php echo htmlescape(__('Save this address: it always shows the user and password fields. It is also the bypass address of the samlSSO plugin.', 'brandkit')); ?></p>
                                <p><?php echo htmlescape(__('Hiding the fields is visual only: local accounts can still sign in. To forbid it, change the authentication settings of GLPI or of the SSO plugin.', 'brandkit')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="brandkit-actions brandkit-actions-floating">
                    <button class="submit brandkit-save-login" type="submit" name="brandkit_action" value="save_login_settings"><?php echo htmlescape(__('Save login page', 'brandkit')); ?></button>
                    <button class="submit brandkit-reset-login" type="submit" name="brandkit_action" value="reset_login_defaults"><?php echo htmlescape(__('Restore defaults', 'brandkit')); ?></button>
                </div>
                <input type="hidden" name="_glpi_csrf_token" value="<?php echo htmlescape(brandkit_get_csrf_token()); ?>">
            </form>
            <script src="<?php echo htmlescape(brandkit_get_public_asset_url('js/admin-login.js') . '?v=' . PLUGIN_BRANDKIT_VERSION); ?>"></script>
        <?php endif; ?>
    </div>
</div>

<?php

Html::footer();
