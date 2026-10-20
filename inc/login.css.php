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

require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/assets.php';

// GLPI 10.0.x ships htmlescape() only from 10.0.13 on.
if (!function_exists('htmlescape')) {
    function htmlescape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * Standalone CSRF token for a form (GLPI checks every POST centrally,
 * in inc/includes.php on 10.x and in a kernel listener on 11.x).
 */
function brandkit_get_csrf_token(): string
{
    return (string) Session::getNewCSRFToken(true);
}

function brandkit_get_login_defaults(): array
{
    $defaults = [
        'login_layout' => 'split-left',
        'login_panel_transparent' => '1',
        'login_panel_hover_effect' => '0',
        'login_panel_opacity' => '65',
        'login_panel_color' => '#0f172a',
        'login_panel_blur' => '6',
        'login_panel_width' => '520',
        'login_panel_height' => '560',
        'login_panel_scrollbar' => 'auto',
        'login_text_color' => '#ffffff',
        'login_logo_position' => 'outside',
        'login_logo_offset' => '0',
        'login_split_opacity' => '40',
        'login_split_color' => '#ffffff',
        'login_split_blur' => '3',
        'login_split_width' => '41',
        'login_button_style' => 'primary',
        'login_button_shape' => 'rounded',
        'login_button_color_mode' => 'preset',
        'login_button_color' => '#0ea5e9',
        'login_button_custom_style' => 'default',
        'login_button_hover_effect' => 'press',
        'login_button_hover_fill_mode' => 'button',
        'login_button_hover_fill_color' => '#0ea5e9',
        'login_button_hover_text_color' => '#ffffff',
        'login_button_text_size' => '20',
        'login_field_style' => 'solid',
        'login_field_bg' => '#ffffff',
        'login_field_border' => '#ffffff',
        'login_field_border_width' => '2',
        'login_field_text' => '#000000',
        'login_field_text_size' => '20',
        'login_field_height' => '50',
        'login_field_width' => '520',
        'login_text_size' => '20',
        'login_field_focus' => '#0ea5e9',
        'login_accent_color' => '#000000',
        'login_icon_user' => 'user',
        'login_icon_password' => 'lock',
        'login_icon_color' => '#000000',
        'login_icon_position' => 'left',
        'login_field_radius' => '10',
        'login_wallpaper_size' => 'cover',
        'login_wallpaper_position' => 'center',
        'login_wallpaper_attachment' => 'fixed',
        'login_wallpaper_overlay_color' => '#000000',
        'login_wallpaper_overlay_opacity' => '20',
        'login_hide_login_text' => '0',
        'login_wallpaper_source' => 'preset',
        'login_wallpaper_preset' => 'top-view-of-office.png',
        'login_wallpaper' => '',
        'login_logo_width' => '200',
        'login_logo_height' => '110',
        'login_logo_scale' => '290',
        'login_apply_error_page' => '1',
        // SSO mode: hide the local login form when an SSO button is present.
        'login_sso_mode' => '0',
        'login_sso_local_link' => '1',
        'login_sso_title' => '',
        'login_sso_button_colors' => 'login',
        'login_sso_button_bg' => '#ffffff',
        'login_sso_button_text' => '#1f2937',
        'login_sso_button_label' => '',
        'login_sso_button_icon' => 'provider',
    ];

    if (brandkit_is_glpi11()) {
        $defaults['login_field_width'] = '280';
    } else {
        $defaults['login_field_width'] = '280';
        $defaults['login_logo_width'] = '200';
        $defaults['login_field_border_width'] = '0';
        $defaults['login_text_size'] = '14';
        $defaults['login_title_size'] = '20';
        $defaults['login_field_text_size'] = '15';
        $defaults['login_field_height'] = '40';
        $defaults['login_field_spacing'] = '20';
        $defaults['login_button_text_size'] = '15';
    }

    return $defaults;
}

function brandkit_get_login_button_styles(): array
{
    return [
        'primary',
        'secondary',
        'success',
        'danger',
        'warning',
        'info',
        'dark',
        'light',
        'outline-primary',
        'outline-secondary',
        'outline-success',
        'outline-danger',
        'outline-warning',
        'outline-info',
        'outline-dark',
    ];
}

function brandkit_get_login_button_shapes(): array
{
    return [
        'square',
        'rounded',
        'pill',
        'cut',
        'soft',
    ];
}

function brandkit_get_login_settings(): array
{
    $defaults = brandkit_get_login_defaults();
    $config = Config::getConfigurationValues('plugin:brandkit', array_keys($defaults));

    $settings = array_merge(
        $defaults,
        array_filter($config, static fn($value) => $value !== null && $value !== '')
    );

    if (empty($config['login_wallpaper_source']) && !empty($settings['login_wallpaper'])) {
        $settings['login_wallpaper_source'] = 'custom';
    }

    if (empty($config['login_wallpaper_preset'])) {
        $settings['login_wallpaper_preset'] = $defaults['login_wallpaper_preset'] ?? 'top-view-of-office.png';
    }

    $resolvedWallpaper = brandkit_resolve_wallpaper_filename($settings['login_wallpaper'] ?? '');
    if ($resolvedWallpaper !== '') {
        $settings['login_wallpaper'] = $resolvedWallpaper;
        if (empty($settings['login_wallpaper_source'])) {
            $settings['login_wallpaper_source'] = 'custom';
        }
    }

    if (!empty($settings['login_wallpaper']) && ($settings['login_wallpaper_source'] ?? '') === 'custom') {
        $wallpaperPath = brandkit_get_wallpaper_path($settings['login_wallpaper']);
        if ($wallpaperPath && is_file($wallpaperPath)) {
            $settings['login_wallpaper_source'] = 'custom';
        }
    }

    return $settings;
}

function brandkit_clamp_int($value, int $min, int $max, int $default): int
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return $default;
    }

    $value = (int) $value;
    if ($value < $min) {
        return $min;
    }
    if ($value > $max) {
        return $max;
    }

    return $value;
}

function brandkit_svg_data_uri(string $svg): string
{
    $encoded = rawurlencode($svg);
    $encoded = str_replace(['%0A', '%0D'], '', $encoded);
    return "url(\"data:image/svg+xml,{$encoded}\")";
}

function brandkit_normalize_hex_color($value, string $fallback): string
{
    if (!is_string($value)) {
        return $fallback;
    }

    $value = trim($value);
    if (!preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $value)) {
        return $fallback;
    }

    if (strlen($value) === 4) {
        $value = sprintf(
            '#%s%s%s',
            $value[1] . $value[1],
            $value[2] . $value[2],
            $value[3] . $value[3]
        );
    }

    return strtolower($value);
}

function brandkit_hex_to_rgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}

function brandkit_get_contrast_color(string $hex): string
{
    [$r, $g, $b] = brandkit_hex_to_rgb($hex);
    $luma = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;

    return $luma > 0.6 ? '#0f172a' : '#ffffff';
}

function brandkit_build_login_css(array $settings): string
{
    $isGlpi11 = brandkit_is_glpi11();

    $layout = $settings['login_layout'];
    if (!in_array($layout, ['split-left', 'split-right', 'classic'], true)) {
        $layout = 'split-left';
    }

    $panelTransparent = $settings['login_panel_transparent'] === '1';
    $panelHoverEffect = ($settings['login_panel_hover_effect'] ?? '1') === '1';
    $panelOpacity = brandkit_clamp_int($settings['login_panel_opacity'], 0, 100, 65);
    $panelBlur = brandkit_clamp_int($settings['login_panel_blur'], 0, 24, 6);
    $panelWidth = brandkit_clamp_int($settings['login_panel_width'] ?? null, 280, 720, 520);
    $panelHeight = brandkit_clamp_int($settings['login_panel_height'] ?? null, 360, 780, 560);
    $panelScale = $isGlpi11 ? min(1, $panelHeight / 780) : 1;
    $panelScrollbar = 'auto';
    $panelColorHex = brandkit_normalize_hex_color($settings['login_panel_color'], '#0f172a');
    [$panelR, $panelG, $panelB] = brandkit_hex_to_rgb($panelColorHex);
    $panelRgba = sprintf('rgba(%d, %d, %d, %.2f)', $panelR, $panelG, $panelB, $panelOpacity / 100);
    $textColor = brandkit_normalize_hex_color($settings['login_text_color'] ?? '#ffffff', '#ffffff');

    $splitOpacity = brandkit_clamp_int($settings['login_split_opacity'], 0, 100, 55);
    $splitBlur = brandkit_clamp_int($settings['login_split_blur'], 0, 24, 10);
    $splitColorHex = brandkit_normalize_hex_color($settings['login_split_color'], '#0f172a');
    [$splitR, $splitG, $splitB] = brandkit_hex_to_rgb($splitColorHex);
    $splitRgba = sprintf('rgba(%d, %d, %d, %.2f)', $splitR, $splitG, $splitB, $splitOpacity / 100);
    $splitWidth = brandkit_clamp_int($settings['login_split_width'], 0, 100, 50);
    if ($layout === 'classic') {
        $splitWidth = 0;
    }

    $loginLogoWidth = brandkit_clamp_int($settings['login_logo_width'] ?? null, 80, 480, 200);
    $loginLogoHeight = brandkit_clamp_int($settings['login_logo_height'] ?? null, 40, 320, 110);
    $loginLogoScale = brandkit_clamp_int($settings['login_logo_scale'] ?? null, 80, 480, 200);
    $logoScaleFactor = $loginLogoWidth > 0 ? ($loginLogoScale / $loginLogoWidth) : 1;
    $loginLogoWidth = max(1, (int) round($loginLogoWidth * $logoScaleFactor));
    $loginLogoHeight = max(1, (int) round($loginLogoHeight * $logoScaleFactor));
    $loginLogoOffset = brandkit_clamp_int($settings['login_logo_offset'] ?? null, 0, 240, 0);
    $logoPosition = $settings['login_logo_position'] ?? 'outside';
    if (!in_array($logoPosition, ['outside', 'inside'], true)) {
        $logoPosition = 'outside';
    }

    $buttonColorMode = $settings['login_button_color_mode'] ?? 'preset';
    if (!in_array($buttonColorMode, ['preset', 'custom'], true)) {
        $buttonColorMode = 'preset';
    }
    $buttonStyle = $settings['login_button_style'] ?? 'primary';
    if (!in_array($buttonStyle, brandkit_get_login_button_styles(), true)) {
        $buttonStyle = 'primary';
    }
    $buttonPalette = [
        'primary' => ['color' => '#0d6efd', 'outline' => false],
        'secondary' => ['color' => '#6c757d', 'outline' => false],
        'success' => ['color' => '#198754', 'outline' => false],
        'danger' => ['color' => '#dc3545', 'outline' => false],
        'warning' => ['color' => '#ffc107', 'outline' => false],
        'info' => ['color' => '#0dcaf0', 'outline' => false],
        'dark' => ['color' => '#212529', 'outline' => false],
        'light' => ['color' => '#f8f9fa', 'outline' => false],
        'outline-primary' => ['color' => '#0d6efd', 'outline' => true],
        'outline-secondary' => ['color' => '#6c757d', 'outline' => true],
        'outline-success' => ['color' => '#198754', 'outline' => true],
        'outline-danger' => ['color' => '#dc3545', 'outline' => true],
        'outline-warning' => ['color' => '#ffc107', 'outline' => true],
        'outline-info' => ['color' => '#0dcaf0', 'outline' => true],
        'outline-dark' => ['color' => '#212529', 'outline' => true],
    ];
    $buttonOutline = false;
    if ($buttonColorMode === 'custom') {
        $buttonColorHex = brandkit_normalize_hex_color($settings['login_button_color'] ?? '#0ea5e9', '#0ea5e9');
        $customStyle = $settings['login_button_custom_style'] ?? 'default';
        $buttonOutline = $customStyle === 'outline';
    } else {
        $buttonColorHex = brandkit_normalize_hex_color($settings['login_accent_color'] ?? '#0ea5e9', '#0ea5e9');
        $customStyle = $settings['login_button_custom_style'] ?? 'default';
        $buttonOutline = $customStyle === 'outline';
    }
    $buttonTextColor = $buttonOutline ? $buttonColorHex : brandkit_get_contrast_color($buttonColorHex);
    $buttonHoverFillMode = $settings['login_button_hover_fill_mode'] ?? 'button';
    if (!in_array($buttonHoverFillMode, ['button', 'custom'], true)) {
        $buttonHoverFillMode = 'button';
    }
    $buttonHoverFillColor = brandkit_normalize_hex_color(
        $settings['login_button_hover_fill_color'] ?? $buttonColorHex,
        $buttonColorHex
    );
    $buttonHoverFill = $buttonHoverFillMode === 'custom' ? $buttonHoverFillColor : $buttonColorHex;
    $buttonHoverTextColor = brandkit_normalize_hex_color(
        $settings['login_button_hover_text_color'] ?? '',
        brandkit_get_contrast_color($buttonHoverFill)
    );
    $buttonTextSizeDefault = $isGlpi11 ? 14 : 15;
    $buttonTextSize = brandkit_clamp_int($settings['login_button_text_size'] ?? null, 10, 28, $buttonTextSizeDefault);
    $buttonBackground = $buttonOutline ? 'transparent' : $buttonColorHex;
    $buttonBorder = $buttonColorHex;
    $buttonShape = $settings['login_button_shape'] ?? 'rounded';
    if (!in_array($buttonShape, brandkit_get_login_button_shapes(), true)) {
        $buttonShape = 'rounded';
    }
    $shapeRadiusMap = [
        'square' => '4px',
        'rounded' => '10px',
        'pill' => '999px',
        'cut' => '6px 18px 6px 18px',
        'soft' => '14px',
    ];
    $shapeClipMap = [
        'cut' => 'polygon(0 0, 92% 0, 100% 35%, 100% 100%, 8% 100%, 0 65%)',
    ];
    $buttonRadius = $shapeRadiusMap[$buttonShape] ?? '10px';
    $buttonClip = $shapeClipMap[$buttonShape] ?? 'none';
    $buttonHoverEffect = $settings['login_button_hover_effect'] ?? 'press';
    if (!in_array($buttonHoverEffect, ['press', 'fill'], true)) {
        $buttonHoverEffect = 'press';
    }

    $fieldStyle = $settings['login_field_style'] ?? 'glass';
    $allowedFieldStyles = ['solid', 'glass', 'soft', 'outline', 'underline', 'neon'];
    if (!in_array($fieldStyle, $allowedFieldStyles, true)) {
        $fieldStyle = 'glass';
    }
    $fieldBg = brandkit_normalize_hex_color($settings['login_field_bg'] ?? '#ffffff', '#ffffff');
    $fieldBorder = brandkit_normalize_hex_color($settings['login_field_border'] ?? '#e2e8f0', '#e2e8f0');
    $fieldText = brandkit_normalize_hex_color($settings['login_field_text'] ?? '#000000', '#000000');
    $fieldTextSizeDefault = $isGlpi11 ? 14 : 15;
    $fieldHeightDefault = $isGlpi11 ? 44 : 40;
    $fieldTextSize = brandkit_clamp_int($settings['login_field_text_size'] ?? null, 10, 36, $fieldTextSizeDefault);
    $fieldHeight = brandkit_clamp_int($settings['login_field_height'] ?? null, 32, 72, $fieldHeightDefault);
    $fieldWidthDefault = 280;
    $fieldWidth = brandkit_clamp_int($settings['login_field_width'] ?? null, 260, 720, $fieldWidthDefault);
    $fieldSpacing = null;
    $textSizeDefault = $isGlpi11 ? 15 : 14;
    $textSize = brandkit_clamp_int($settings['login_text_size'] ?? null, 10, 30, $textSizeDefault);
    $titleSize = null;
    if (!$isGlpi11) {
        $fieldSpacing = brandkit_clamp_int($settings['login_field_spacing'] ?? null, 8, 48, 20);
        $titleSize = brandkit_clamp_int($settings['login_title_size'] ?? null, 16, 48, 20);
    }
    $fieldFocus = brandkit_normalize_hex_color($settings['login_field_focus'] ?? '#0ea5e9', '#0ea5e9');
    $fieldBorderWidthDefault = $isGlpi11 ? 1 : 0;
    $fieldBorderWidth = brandkit_clamp_int($settings['login_field_border_width'] ?? null, 0, 6, $fieldBorderWidthDefault);
    $accentColor = brandkit_normalize_hex_color($settings['login_accent_color'] ?? '#0ea5e9', '#0ea5e9');
    $focusBorderColor = $accentColor;
    $focusGlowColor = $fieldStyle === 'neon' ? $fieldFocus : $accentColor;
    $iconColor = brandkit_normalize_hex_color($settings['login_icon_color'] ?? '#0ea5e9', '#0ea5e9');
    $iconPosition = $settings['login_icon_position'] ?? 'right';
    if (!in_array($iconPosition, ['left', 'right'], true)) {
        $iconPosition = 'right';
    }
    $fieldRadius = brandkit_clamp_int($settings['login_field_radius'] ?? null, 0, 32, 12);
    [$fieldTextR, $fieldTextG, $fieldTextB] = brandkit_hex_to_rgb($fieldText);
    $fieldPlaceholder = sprintf('rgba(%d, %d, %d, 0.6)', $fieldTextR, $fieldTextG, $fieldTextB);
    switch ($fieldStyle) {
        case 'soft':
            $fieldShadow = '0 10px 24px rgba(15, 23, 42, 0.12)';
            break;
        case 'glass':
            $fieldShadow = '0 12px 24px rgba(15, 23, 42, 0.18)';
            break;
        case 'neon':
            $fieldShadow = '0 8px 20px rgba(15, 23, 42, 0.16)';
            break;
        default:
            $fieldShadow = 'none';
            break;
    }
    $fieldAutofillBg = 'var(--brandkit-login-field-bg)';

    $iconDefs = [
        'user' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c2-4 6-6 8-6s6 2 8 6"/></svg>',
        'id' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 15h4"/><circle cx="10" cy="10" r="2.5"/></svg>',
        'mail' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
        'lock' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>',
        'key' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="14.5" r="3.5"/><path d="M11 14h10l-2 2 2 2-2 2"/></svg>',
        'shield' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 5 3.5 8.5 8 9 4.5-.5 8-4 8-9V6z"/><path d="M9.5 12.5 11 14l3.5-3.5"/></svg>',
    ];
    $iconUser = $settings['login_icon_user'] ?? 'none';
    $iconPassword = $settings['login_icon_password'] ?? 'none';
    $userSvg = $iconDefs[$iconUser] ?? null;
    $passwordSvg = $iconDefs[$iconPassword] ?? null;
    $iconUserUri = $userSvg ? brandkit_svg_data_uri(sprintf($userSvg, $iconColor)) : 'none';
    $iconPasswordUri = $passwordSvg ? brandkit_svg_data_uri(sprintf($passwordSvg, $iconColor)) : 'none';
    $iconUserPadding = $userSvg ? '2.5rem' : '0.75rem';
    $iconPasswordPadding = $passwordSvg ? '2.5rem' : '0.75rem';
    if ($iconPosition === 'left') {
        $iconUserPaddingLeft = $iconUserPadding;
        $iconUserPaddingRight = '0.75rem';
        $iconPasswordPaddingLeft = $iconPasswordPadding;
        $iconPasswordPaddingRight = '0.75rem';
        $iconPositionValue = 'left 0.9rem center';
    } else {
        $iconUserPaddingLeft = '0.75rem';
        $iconUserPaddingRight = $iconUserPadding;
        $iconPasswordPaddingLeft = '0.75rem';
        $iconPasswordPaddingRight = $iconPasswordPadding;
        $iconPositionValue = 'right 0.9rem center';
    }

    $wallpaperSize = $settings['login_wallpaper_size'] ?? 'cover';
    $allowedWallpaperSizes = ['cover', 'contain', 'stretch', 'original'];
    if (!in_array($wallpaperSize, $allowedWallpaperSizes, true)) {
        $wallpaperSize = 'cover';
    }
    switch ($wallpaperSize) {
        case 'contain':
            $wallpaperSizeValue = 'contain';
            break;
        case 'stretch':
            $wallpaperSizeValue = '100% 100%';
            break;
        case 'original':
            $wallpaperSizeValue = 'auto';
            break;
        default:
            $wallpaperSizeValue = 'cover';
            break;
    }

    $wallpaperPosition = $settings['login_wallpaper_position'] ?? 'center';
    $allowedWallpaperPositions = ['center', 'top', 'bottom', 'left', 'right'];
    if (!in_array($wallpaperPosition, $allowedWallpaperPositions, true)) {
        $wallpaperPosition = 'center';
    }

    $wallpaperAttachment = $settings['login_wallpaper_attachment'] ?? 'fixed';
    $allowedWallpaperAttachments = ['fixed', 'scroll'];
    if (!in_array($wallpaperAttachment, $allowedWallpaperAttachments, true)) {
        $wallpaperAttachment = 'fixed';
    }

    $wallpaperOverlayOpacity = brandkit_clamp_int($settings['login_wallpaper_overlay_opacity'] ?? null, 0, 100, 35);
    $wallpaperOverlayColorHex = brandkit_normalize_hex_color($settings['login_wallpaper_overlay_color'] ?? '#000000', '#000000');
    [$overlayR, $overlayG, $overlayB] = brandkit_hex_to_rgb($wallpaperOverlayColorHex);
    $wallpaperOverlayRgba = sprintf('rgba(%d, %d, %d, %.2f)', $overlayR, $overlayG, $overlayB, $wallpaperOverlayOpacity / 100);
    $wallpaperSource = $settings['login_wallpaper_source'] ?? '';
    if ($wallpaperSource === '') {
        $wallpaperSource = !empty($settings['login_wallpaper']) ? 'custom' : 'preset';
    }
    if (!in_array($wallpaperSource, ['preset', 'custom'], true)) {
        $wallpaperSource = !empty($settings['login_wallpaper']) ? 'custom' : 'preset';
    }
    if ($wallpaperSource === 'custom' && !empty($settings['login_wallpaper']) && brandkit_get_wallpaper_path($settings['login_wallpaper'])) {
        $wallpaperSource = 'custom';
    }

    $wallpaperPresets = brandkit_get_wallpaper_presets();
    $wallpaperPresetDefault = brandkit_get_wallpaper_preset_default($wallpaperPresets);
    $wallpaperPreset = $settings['login_wallpaper_preset'] ?? $wallpaperPresetDefault;
    if (!in_array($wallpaperPreset, $wallpaperPresets, true)) {
        $wallpaperPreset = $wallpaperPresetDefault;
    }

    $wallpaperUrl = '';
    $wallpaperWebpUrl = '';
    $wallpaperMime = 'image/jpeg';
    if ($wallpaperSource === 'preset' && $wallpaperPreset) {
        $presetAssets = brandkit_get_wallpaper_preset_assets($wallpaperPreset);
        $wallpaperUrl = $presetAssets['url'] ?? '';
        $wallpaperWebpUrl = $presetAssets['webp_url'] ?? '';
        $wallpaperMime = $presetAssets['mime'] ?? 'image/jpeg';
    }
    if ($wallpaperSource === 'custom' && !empty($settings['login_wallpaper'])) {
        $wallpaperPath = brandkit_get_wallpaper_path($settings['login_wallpaper']);
        if ($wallpaperPath && is_file($wallpaperPath)) {
            $wallpaperVersion = (int) filemtime($wallpaperPath);
            $wallpaperUrl = brandkit_get_wallpaper_image_url($settings['login_wallpaper'], $wallpaperVersion);
            $wallpaperMime = brandkit_guess_mime_from_filename($settings['login_wallpaper']);
            $wallpaperWebpPath = preg_replace('/\.[^.]+$/', '.webp', $wallpaperPath);
            if ($wallpaperWebpPath && is_file($wallpaperWebpPath)) {
                $wallpaperWebpUrl = brandkit_get_wallpaper_image_url(basename($wallpaperWebpPath), (int) filemtime($wallpaperWebpPath));
            }
        }
    }
    if ($wallpaperUrl === '') {
        $resolvedWallpaper = brandkit_resolve_wallpaper_filename($settings['login_wallpaper'] ?? '');
        if ($resolvedWallpaper !== '') {
            $wallpaperPath = brandkit_get_wallpaper_path($resolvedWallpaper);
            if ($wallpaperPath && is_file($wallpaperPath)) {
                $wallpaperVersion = (int) filemtime($wallpaperPath);
                $wallpaperUrl = brandkit_get_wallpaper_image_url($resolvedWallpaper, $wallpaperVersion);
                $wallpaperMime = brandkit_guess_mime_from_filename($resolvedWallpaper);
                $wallpaperWebpPath = preg_replace('/\.[^.]+$/', '.webp', $wallpaperPath);
                if ($wallpaperWebpPath && is_file($wallpaperWebpPath)) {
                    $wallpaperWebpUrl = brandkit_get_wallpaper_image_url(basename($wallpaperWebpPath), (int) filemtime($wallpaperWebpPath));
                }
            }
        }
    }

    $hideLoginText = ($settings['login_hide_login_text'] ?? '0') === '1';

    $layoutVars = [
        'split-left' => [
            'split_width' => "{$splitWidth}vw",
            'split_left' => '0',
            'split_right' => 'auto',
            'container_max' => 'calc(var(--brandkit-login-split-width) - 1.5vw)',
            'container_left' => 'calc(var(--brandkit-login-split-width) / 2)',
            'container_right' => 'auto',
            'container_translate' => '-50%',
            'text_align' => 'left',
        ],
        'split-right' => [
            'split_width' => "{$splitWidth}vw",
            'split_left' => 'auto',
            'split_right' => '0',
            'container_max' => 'calc(var(--brandkit-login-split-width) - 1.5vw)',
            'container_left' => 'auto',
            'container_right' => 'calc(var(--brandkit-login-split-width) / 2)',
            'container_translate' => '50%',
            'text_align' => 'right',
        ],
        'classic' => [
            'split_width' => '0',
            'split_left' => 'auto',
            'split_right' => 'auto',
            'container_max' => '28rem',
            'container_left' => 'auto',
            'container_right' => 'auto',
            'container_translate' => '0',
            'text_align' => 'center',
        ],
    ];

    $vars = $layoutVars[$layout];

    $loginLogoFiles = [
        'logo-GLPI-250-black.png' => '--glpi-logo-dark-login',
        'logo-GLPI-250-white.png' => '--glpi-logo-light-login',
    ];
    $publicLogoDir = brandkit_get_pics_logos_dir();

    $css = ":root {\n";
    $css .= "  --brandkit-login-panel-color: {$panelRgba};\n";
    $css .= "  --brandkit-login-panel-blur: {$panelBlur}px;\n";
    $css .= "  --brandkit-login-panel-width: {$panelWidth}px;\n";
    $css .= "  --brandkit-login-panel-height: {$panelHeight}px;\n";
    $css .= "  --brandkit-login-panel-scale: " . number_format($panelScale, 3, '.', '') . ";\n";
    $css .= "  --brandkit-login-panel-overflow: {$panelScrollbar};\n";
    $css .= "  --brandkit-login-text-color: {$textColor};\n";
    $css .= "  --brandkit-login-split-color: {$splitRgba};\n";
    $css .= "  --brandkit-login-split-blur: {$splitBlur}px;\n";
    $css .= "  --brandkit-login-split-width: {$vars['split_width']};\n";
    $css .= "  --brandkit-login-split-left: {$vars['split_left']};\n";
    $css .= "  --brandkit-login-split-right: {$vars['split_right']};\n";
    $css .= "  --brandkit-login-container-max: {$vars['container_max']};\n";
    if ($panelTransparent) {
        $css .= "  --brandkit-login-container-max-effective: var(--brandkit-login-container-max);\n";
    } else {
        $css .= "  --brandkit-login-container-max-effective: max(var(--brandkit-login-container-max), var(--brandkit-login-panel-width));\n";
    }
    $css .= "  --brandkit-login-container-left: {$vars['container_left']};\n";
    $css .= "  --brandkit-login-container-right: {$vars['container_right']};\n";
    $css .= "  --brandkit-login-container-translate: {$vars['container_translate']};\n";
    $css .= "  --brandkit-login-text-align: {$vars['text_align']};\n";
    $css .= "  --brandkit-login-logo-width: {$loginLogoWidth}px;\n";
    $css .= "  --brandkit-login-logo-height: {$loginLogoHeight}px;\n";
    $css .= "  --brandkit-login-logo-offset: {$loginLogoOffset}px;\n";
    $css .= "  --brandkit-login-button-color: {$buttonColorHex};\n";
    $css .= "  --brandkit-login-button-bg: {$buttonBackground};\n";
    $css .= "  --brandkit-login-button-border: {$buttonBorder};\n";
    $css .= "  --brandkit-login-button-text: {$buttonTextColor};\n";
    $css .= "  --brandkit-login-button-hover-fill: {$buttonHoverFill};\n";
    $css .= "  --brandkit-login-button-hover-text: {$buttonHoverTextColor};\n";
    $css .= "  --brandkit-login-button-text-size: {$buttonTextSize}px;\n";
    $css .= "  --brandkit-login-button-radius: {$buttonRadius};\n";
    $css .= "  --brandkit-login-button-clip: {$buttonClip};\n";
    $css .= "  --brandkit-login-field-bg: {$fieldBg};\n";
    $css .= "  --brandkit-login-field-bg-autofill: {$fieldAutofillBg};\n";
    $css .= "  --brandkit-login-field-border: {$fieldBorder};\n";
    $css .= "  --brandkit-login-field-border-width: {$fieldBorderWidth}px;\n";
    $css .= "  --brandkit-login-field-text: {$fieldText};\n";
    $css .= "  --brandkit-login-field-text-autofill: var(--tblr-body-color);\n";
    $css .= "  --brandkit-login-field-text-size: {$fieldTextSize}px;\n";
    $css .= "  --brandkit-login-field-height: {$fieldHeight}px;\n";
    $css .= "  --brandkit-login-field-width: {$fieldWidth}px;\n";
    if (!$isGlpi11 && $fieldSpacing !== null) {
        $css .= "  --brandkit-login-field-spacing: {$fieldSpacing}px;\n";
    }
    $css .= "  --brandkit-login-text-size: {$textSize}px;\n";
    if (!$isGlpi11 && $titleSize !== null) {
        $css .= "  --brandkit-login-title-size: {$titleSize}px;\n";
    }
    $css .= "  --brandkit-login-field-focus: {$fieldFocus};\n";
    $css .= "  --brandkit-login-field-focus-border: {$focusBorderColor};\n";
    $css .= "  --brandkit-login-field-focus-glow: {$focusGlowColor};\n";
    $css .= "  --brandkit-login-accent-color: {$accentColor};\n";
    $css .= "  --brandkit-login-icon-user: {$iconUserUri};\n";
    $css .= "  --brandkit-login-icon-password: {$iconPasswordUri};\n";
    $css .= "  --brandkit-login-icon-user-padding-left: {$iconUserPaddingLeft};\n";
    $css .= "  --brandkit-login-icon-user-padding-right: {$iconUserPaddingRight};\n";
    $css .= "  --brandkit-login-icon-password-padding-left: {$iconPasswordPaddingLeft};\n";
    $css .= "  --brandkit-login-icon-password-padding-right: {$iconPasswordPaddingRight};\n";
    $css .= "  --brandkit-login-icon-position: {$iconPositionValue};\n";
    $css .= "  --brandkit-login-field-placeholder: {$fieldPlaceholder};\n";
    $css .= "  --brandkit-login-field-radius: {$fieldRadius}px;\n";
    $css .= "  --brandkit-login-field-shadow: {$fieldShadow};\n";
    foreach ($loginLogoFiles as $file => $variable) {
        $path = $publicLogoDir ? $publicLogoDir . '/' . $file : null;
        if ($path && is_file($path)) {
            $picsBase = brandkit_get_pics_web_base();
            if ($picsBase === '') {
                $picsBase = '/pics';
            }
            $picsBase = rtrim($picsBase, '/');
            $css .= "  {$variable}: url(\"{$picsBase}/logos/{$file}\") !important;\n";
        }
    }
    $css .= "  --brandkit-login-logo-image: var(--glpi-logo-dark-login, var(--logo));\n";
    if (!empty($wallpaperUrl)) {
        $css .= "  --brandkit-login-error-wallpaper: url(\"{$wallpaperUrl}\");\n";
        $css .= "  --brandkit-login-error-overlay: {$wallpaperOverlayRgba};\n";
    }
    $css .= "}\n";
    $css .= ":root[data-glpi-theme-dark=\"1\"] {\n";
    $css .= "  --brandkit-login-logo-image: var(--glpi-logo-light-login, var(--logo));\n";
    $css .= "}\n";

    $css .= "html, body {\n";
    $css .= "  height: 100%;\n";
    $css .= "  margin: 0;\n";
    $css .= "  padding: 0;\n";
    $css .= "}\n";
    $css .= ".page-anonymous,\n";
    $css .= "body.welcome-anonymous,\n";
    $css .= "body.brandkit-error-page {\n";
    $css .= "  position: relative;\n";
    $css .= "  min-height: 100vh;\n";
    $css .= "  width: 100%;\n";
    if (!empty($wallpaperUrl)) {
        $css .= "  background-image: linear-gradient({$wallpaperOverlayRgba}, {$wallpaperOverlayRgba}), url(\"{$wallpaperUrl}\") !important;\n";
        if (!empty($wallpaperWebpUrl)) {
            $css .= "  background-image: linear-gradient({$wallpaperOverlayRgba}, {$wallpaperOverlayRgba}), image-set(url(\"{$wallpaperWebpUrl}\") type(\"image/webp\"), url(\"{$wallpaperUrl}\") type(\"{$wallpaperMime}\")) !important;\n";
        }
        $css .= "  background-color: {$wallpaperOverlayRgba} !important;\n";
        $css .= "  background-size: {$wallpaperSizeValue} !important;\n";
        $css .= "  background-position: {$wallpaperPosition} !important;\n";
        $css .= "  background-repeat: no-repeat !important;\n";
        $css .= "  background-attachment: {$wallpaperAttachment} !important;\n";
    }
    $css .= "}\n";
    $css .= ".page-anonymous .flex-fill,\n";
    $css .= "body.welcome-anonymous .flex-fill {\n";
        $css .= "  position: relative;\n";
        $css .= "  z-index: 1;\n";
    $css .= "  min-height: 100vh;\n";
    $css .= "  margin-top: 0 !important;\n";
    $css .= "  padding-top: 0 !important;\n";
    if ($isGlpi11) {
        $css .= "  padding-bottom: 2rem !important;\n";
    } else {
        $css .= "  padding-bottom: 0.75rem !important;\n";
    }
    $css .= "}\n";
    $css .= "body.welcome-anonymous {\n";
    $css .= "  display: flex;\n";
    $css .= "  align-items: center;\n";
    $css .= "  justify-content: center;\n";
    $css .= "  min-height: 100vh;\n";
    $css .= "  overflow-x: hidden;\n";
    $css .= "  overflow-y: auto;\n";
    $css .= "  font-family: system-ui, -apple-system, sans-serif;\n";
    $css .= "  transition: background-color 0.5s ease;\n";
    $css .= "}\n";
    $css .= "body.brandkit-error-page {\n";
    $css .= "  font-family: system-ui, -apple-system, sans-serif;\n";
    $css .= "  transition: background-color 0.5s ease;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .glpi-logo,\n";
    $css .= "body.welcome-anonymous .glpi-logo {\n";
    $css .= "  background: var(--brandkit-login-logo-image) no-repeat center;\n";
    $css .= "  background-size: contain;\n";
    if ($isGlpi11) {
        $css .= "  width: calc(var(--brandkit-login-logo-width) * var(--brandkit-login-panel-scale));\n";
        $css .= "  height: calc(var(--brandkit-login-logo-height) * var(--brandkit-login-panel-scale));\n";
    } else {
        $css .= "  width: var(--brandkit-login-logo-width);\n";
        $css .= "  height: var(--brandkit-login-logo-height);\n";
    }
    $css .= "  display: block;\n";
    $css .= "  margin-left: auto;\n";
    $css .= "  margin-right: auto;\n";
    $css .= "  content: none !important;\n";
    $css .= "}\n";

    if ($panelTransparent) {
        $css .= ".page-anonymous .main-content-card,\n";
        $css .= "body.welcome-anonymous .main-content-card {\n";
        $css .= "  background: transparent !important;\n";
        $css .= "  box-shadow: none !important;\n";
        $css .= "  border-color: transparent !important;\n";
        $css .= "  box-sizing: border-box;\n";
        $css .= "}\n";
        $css .= "body.welcome-anonymous .page-anonymous .card {\n";
        $css .= "  background: transparent !important;\n";
        $css .= "  box-shadow: none !important;\n";
        $css .= "  border-color: transparent !important;\n";
        $css .= "}\n";
    } else {
        $css .= ".page-anonymous .main-content-card,\n";
        $css .= "body.welcome-anonymous .main-content-card {\n";
        $css .= "  background: var(--brandkit-login-panel-color) !important;\n";
        $css .= "  backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
        $css .= "  -webkit-backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
        $css .= "  width: min(100%, var(--brandkit-login-panel-width));\n";
        $css .= "  height: min(100vh, var(--brandkit-login-panel-height));\n";
        $css .= "  max-height: 100vh;\n";
        $css .= "  overflow-y: var(--brandkit-login-panel-overflow);\n";
        $css .= "  overflow-x: hidden;\n";
        $css .= "  margin-left: auto;\n";
        $css .= "  margin-right: auto;\n";
        $css .= "  box-sizing: border-box;\n";
        $css .= "  display: flex;\n";
        $css .= "  flex-direction: column;\n";
        $css .= "}\n";
    $css .= "body.welcome-anonymous .page-anonymous .card {\n";
        $css .= "  background: var(--brandkit-login-panel-color) !important;\n";
        $css .= "  backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
        $css .= "  -webkit-backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
        $css .= "  border-radius: 1.5rem !important;\n";
        $css .= "  border: 1px solid rgba(255, 255, 255, 0.08) !important;\n";
        $css .= "  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 5px 15px rgba(0, 0, 0, 0.3), 0 0 20px rgba(255, 255, 255, 0.05) !important;\n";
    $css .= "}\n";
    }

    if (!$isGlpi11) {
        $css .= ".page-anonymous .card.card-md,\n";
        $css .= "body.welcome-anonymous .card.card-md {\n";
        $css .= "  width: min(100%, var(--brandkit-login-panel-width));\n";
        $css .= "  height: min(100vh, var(--brandkit-login-panel-height));\n";
        $css .= "  max-height: 100vh;\n";
        $css .= "  overflow-y: var(--brandkit-login-panel-overflow);\n";
        $css .= "  overflow-x: hidden;\n";
        $css .= "  box-sizing: border-box;\n";
        $css .= "  margin-left: auto;\n";
        $css .= "  margin-right: auto;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card,\n";
        $css .= "body.welcome-anonymous .main-content-card,\n";
        $css .= ".page-anonymous .card.card-md,\n";
        $css .= "body.welcome-anonymous .card.card-md {\n";
        $css .= "  height: auto;\n";
        $css .= "  min-height: 0;\n";
        $css .= "  max-height: none;\n";
        $css .= "}\n";
    }

    $css .= ".page-anonymous .main-content-card,\n";
    $css .= "body.welcome-anonymous .main-content-card {\n";
    if ($isGlpi11) {
        $css .= "  font-size: calc(var(--brandkit-login-text-size) * var(--brandkit-login-panel-scale));\n";
    } else {
        $css .= "  font-size: var(--brandkit-login-text-size);\n";
    }
    $css .= "}\n";
    $css .= "body.welcome-anonymous .page-anonymous .card {\n";
    if ($isGlpi11) {
        $css .= "  padding: calc(2rem * var(--brandkit-login-panel-scale)) calc(2.5rem * var(--brandkit-login-panel-scale));\n";
    } else {
        $css .= "  padding: 2rem 2.5rem;\n";
    }
    $css .= "}\n";
    $css .= ".page-anonymous .main-content-card .card-body,\n";
    $css .= "body.welcome-anonymous .main-content-card .card-body {\n";
    if ($isGlpi11) {
        $css .= "  padding: calc(1.5rem * var(--brandkit-login-panel-scale));\n";
    } else {
        $css .= "  padding: 1.5rem 1.5rem 1rem;\n";
    }
    $css .= "}\n";
    $css .= ".page-anonymous .main-content-card .mb-3,\n";
    $css .= "body.welcome-anonymous .main-content-card .mb-3 {\n";
    if ($isGlpi11) {
        $css .= "  margin-bottom: calc(1rem * var(--brandkit-login-panel-scale));\n";
    } else {
        $css .= "  margin-bottom: var(--brandkit-login-field-spacing);\n";
    }
    $css .= "}\n";
    if (!$isGlpi11) {
        $css .= ".page-anonymous .main-content-card .mb-4,\n";
        $css .= "body.welcome-anonymous .main-content-card .mb-4 {\n";
        $css .= "  margin-bottom: calc(var(--brandkit-login-field-spacing) * 1.25);\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card .mb-3,\n";
        $css .= ".page-anonymous .main-content-card .mb-4,\n";
        $css .= "body.welcome-anonymous .main-content-card .mb-3,\n";
        $css .= "body.welcome-anonymous .main-content-card .mb-4 {\n";
        $css .= "  width: 100%;\n";
        $css .= "}\n";
    }
    $css .= ".page-anonymous .main-content-card .form-label,\n";
    $css .= "body.welcome-anonymous .main-content-card .form-label {\n";
    if ($isGlpi11) {
        $css .= "  font-size: calc(var(--brandkit-login-text-size) * 0.95 * var(--brandkit-login-panel-scale));\n";
        $css .= "  margin-bottom: calc(0.4rem * var(--brandkit-login-panel-scale));\n";
    } else {
        $css .= "  font-size: calc(var(--brandkit-login-text-size) * 0.95);\n";
        $css .= "  margin-bottom: 0.4rem;\n";
    }
    $css .= "  width: min(100%, var(--brandkit-login-field-width));\n";
    $css .= "  max-width: var(--brandkit-login-field-width);\n";
    $css .= "  margin-left: auto;\n";
    $css .= "  margin-right: auto;\n";
    $css .= "}\n";
    $css .= ".page-anonymous form[action*=\"lostpassword\"] .card-title,\n";
    $css .= "body.welcome-anonymous form[action*=\"lostpassword\"] .card-title,\n";
    $css .= ".page-anonymous form[action*=\"lostpassword\"] p.text-muted,\n";
    $css .= "body.welcome-anonymous form[action*=\"lostpassword\"] p.text-muted {\n";
    $css .= "  width: min(100%, var(--brandkit-login-field-width));\n";
    $css .= "  max-width: var(--brandkit-login-field-width);\n";
    $css .= "  margin-left: auto;\n";
    $css .= "  margin-right: auto;\n";
    $css .= "  text-align: left;\n";
    $css .= "}\n";
    $css .= ".page-anonymous form[action*=\"lostpassword\"] .form-field.row,\n";
    $css .= "body.welcome-anonymous form[action*=\"lostpassword\"] .form-field.row {\n";
    $css .= "  width: min(100%, var(--brandkit-login-field-width));\n";
    $css .= "  max-width: var(--brandkit-login-field-width);\n";
    $css .= "  margin-left: auto;\n";
    $css .= "  margin-right: auto;\n";
    $css .= "  flex-direction: column;\n";
    $css .= "  align-items: stretch;\n";
    $css .= "}\n";
    $css .= ".page-anonymous form[action*=\"lostpassword\"] .form-field.row .col-form-label,\n";
    $css .= "body.welcome-anonymous form[action*=\"lostpassword\"] .form-field.row .col-form-label {\n";
    $css .= "  width: 100%;\n";
    $css .= "  max-width: 100%;\n";
    $css .= "  text-align: left !important;\n";
    $css .= "  justify-content: flex-start;\n";
    $css .= "  align-self: flex-start;\n";
    $css .= "  padding-left: 0;\n";
    $css .= "  padding-right: 0;\n";
    $css .= "  margin-bottom: calc(0.4rem * var(--brandkit-login-panel-scale));\n";
    $css .= "}\n";
    $css .= ".page-anonymous form[action*=\"lostpassword\"] .form-field.row .field-container,\n";
    $css .= "body.welcome-anonymous form[action*=\"lostpassword\"] .form-field.row .field-container {\n";
    $css .= "  width: 100%;\n";
    $css .= "  max-width: 100%;\n";
    $css .= "  padding-left: 0;\n";
    $css .= "  padding-right: 0;\n";
    $css .= "}\n";
    $css .= ".page-anonymous form[action*=\"lostpassword\"] .form-footer,\n";
    $css .= "body.welcome-anonymous form[action*=\"lostpassword\"] .form-footer {\n";
    $css .= "  width: min(100%, var(--brandkit-login-field-width));\n";
    $css .= "  max-width: var(--brandkit-login-field-width);\n";
    $css .= "  margin-left: auto;\n";
    $css .= "  margin-right: auto;\n";
    $css .= "}\n";
    $css .= ".page-anonymous form[action*=\"lostpassword\"] .form-footer .btn,\n";
    $css .= "body.welcome-anonymous form[action*=\"lostpassword\"] .form-footer .btn {\n";
    $css .= "  color: var(--brandkit-login-button-text) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous form[action*=\"lostpassword\"] .form-footer .btn .ti,\n";
    $css .= "body.welcome-anonymous form[action*=\"lostpassword\"] .form-footer .btn .ti {\n";
    $css .= "  color: currentColor;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .text-center.text-muted.mt-3,\n";
    $css .= "body.welcome-anonymous .text-center.text-muted.mt-3 {\n";
    $css .= "  width: min(100%, var(--brandkit-login-field-width));\n";
    $css .= "  max-width: var(--brandkit-login-field-width);\n";
    $css .= "  margin-left: auto;\n";
    $css .= "  margin-right: auto;\n";
    if (!$isGlpi11) {
        $css .= "  margin-top: 0.5rem !important;\n";
    }
    $css .= "}\n";
    if ($isGlpi11) {
        $css .= ".page-anonymous .main-content-card .d-flex:has(.forgot_password),\n";
        $css .= "body.welcome-anonymous .main-content-card .d-flex:has(.forgot_password) {\n";
        $css .= "  width: min(100%, var(--brandkit-login-field-width));\n";
        $css .= "  max-width: var(--brandkit-login-field-width);\n";
        $css .= "  margin-left: auto;\n";
        $css .= "  margin-right: auto;\n";
        $css .= "  align-items: center;\n";
        $css .= "  justify-content: space-between;\n";
        $css .= "  gap: 0.5rem;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card .d-flex:has(.forgot_password) .form-label,\n";
        $css .= "body.welcome-anonymous .main-content-card .d-flex:has(.forgot_password) .form-label {\n";
        $css .= "  display: flex;\n";
        $css .= "  align-items: center;\n";
        $css .= "  justify-content: flex-start;\n";
        $css .= "  gap: 0.4rem;\n";
        $css .= "  width: auto;\n";
        $css .= "  max-width: none;\n";
        $css .= "  margin-left: 0;\n";
        $css .= "  margin-right: 0;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card .form-label + .forgot_password,\n";
        $css .= "body.welcome-anonymous .main-content-card .form-label + .forgot_password {\n";
        $css .= "  margin-left: 0 !important;\n";
        $css .= "  margin-right: 0 !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card .form-label + .forgot_password a,\n";
        $css .= "body.welcome-anonymous .main-content-card .form-label + .forgot_password a {\n";
        $css .= "  white-space: nowrap;\n";
        $css .= "}\n";
    }
    $css .= ".page-anonymous .main-content-card .card-header,\n";
    $css .= "body.welcome-anonymous .main-content-card .card-header {\n";
    $css .= "  border-bottom: none !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .main-content-card hr,\n";
    $css .= "body.welcome-anonymous .main-content-card hr,\n";
    $css .= ".page-anonymous .main-content-card .hr-text,\n";
    $css .= "body.welcome-anonymous .main-content-card .hr-text {\n";
    $css .= "  display: none !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .main-content-card .form-control,\n";
    $css .= ".page-anonymous .main-content-card .form-select,\n";
    $css .= "body.welcome-anonymous .main-content-card .form-control,\n";
    $css .= "body.welcome-anonymous .main-content-card .form-select {\n";
    if ($isGlpi11) {
        $css .= "  padding: calc(0.6rem * var(--brandkit-login-panel-scale)) calc(0.8rem * var(--brandkit-login-panel-scale));\n";
        $css .= "  font-size: calc(var(--brandkit-login-field-text-size) * var(--brandkit-login-panel-scale));\n";
        $css .= "  height: calc(var(--brandkit-login-field-height) * var(--brandkit-login-panel-scale));\n";
        $css .= "  width: min(100%, var(--brandkit-login-field-width));\n";
        $css .= "  max-width: var(--brandkit-login-field-width);\n";
        $css .= "  margin-left: auto;\n";
        $css .= "  margin-right: auto;\n";
        $css .= "  box-sizing: border-box;\n";
    } else {
        $css .= "  padding: 0.6rem 0.8rem;\n";
        $css .= "  font-size: var(--brandkit-login-field-text-size);\n";
        $css .= "  height: var(--brandkit-login-field-height);\n";
        $css .= "  width: min(100%, var(--brandkit-login-field-width));\n";
        $css .= "  max-width: var(--brandkit-login-field-width);\n";
        $css .= "  margin-left: auto;\n";
        $css .= "  margin-right: auto;\n";
        $css .= "  box-sizing: border-box;\n";
    }
    $css .= "}\n";
    $css .= ".page-anonymous select[name=\"auth\"],\n";
    $css .= "body.welcome-anonymous select[name=\"auth\"],\n";
    $css .= ".page-anonymous select[name=\"auth\"] + .select2,\n";
    $css .= "body.welcome-anonymous select[name=\"auth\"] + .select2 {\n";
    $css .= "  width: min(100%, var(--brandkit-login-field-width)) !important;\n";
    $css .= "  max-width: var(--brandkit-login-field-width) !important;\n";
    $css .= "  margin-left: auto !important;\n";
    $css .= "  margin-right: auto !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous select[name=\"auth\"] + .select2,\n";
    $css .= "body.welcome-anonymous select[name=\"auth\"] + .select2 {\n";
    $css .= "  display: block;\n";
    $css .= "}\n";
    $css .= ".page-anonymous select[name=\"auth\"],\n";
    $css .= "body.welcome-anonymous select[name=\"auth\"] {\n";
    $css .= "  text-align: left !important;\n";
    $css .= "  text-align-last: left !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .main-content-card .form-check,\n";
    $css .= "body.welcome-anonymous .main-content-card .form-check {\n";
    if ($isGlpi11) {
        $css .= "  margin-bottom: calc(0.75rem * var(--brandkit-login-panel-scale));\n";
    } else {
        $css .= "  margin-bottom: calc(var(--brandkit-login-field-spacing) * 0.75);\n";
    }
    $css .= "  width: min(100%, var(--brandkit-login-field-width));\n";
    $css .= "  max-width: var(--brandkit-login-field-width);\n";
    $css .= "  margin-left: auto;\n";
    $css .= "  margin-right: auto;\n";
    $css .= "}\n";
    if ($isGlpi11) {
        $css .= ".page-anonymous .form-footer a[href*=\"lostpassword\"],\n";
        $css .= "body.welcome-anonymous .form-footer a[href*=\"lostpassword\"] {\n";
        $css .= "  white-space: nowrap;\n";
        $css .= "  display: inline-flex;\n";
        $css .= "  align-items: center;\n";
        $css .= "  gap: 0.25rem;\n";
        $css .= "}\n";
    }
    if (!$isGlpi11) {
        $css .= ".page-anonymous .main-content-card .form-footer,\n";
        $css .= "body.welcome-anonymous .main-content-card .form-footer {\n";
        $css .= "  width: 100%;\n";
        $css .= "  margin-bottom: 0;\n";
        $css .= "}\n";
    }
    $css .= "body.welcome-anonymous button[type=\"submit\"],\n";
    $css .= "body.welcome-anonymous .form-footer .btn {\n";
    if ($isGlpi11) {
        $css .= "  padding: calc(0.9rem * var(--brandkit-login-panel-scale)) calc(1.6rem * var(--brandkit-login-panel-scale));\n";
        $css .= "  font-size: calc(var(--brandkit-login-button-text-size) * var(--brandkit-login-panel-scale));\n";
    } else {
        $css .= "  padding: 0.9rem 1.6rem;\n";
        $css .= "  font-size: var(--brandkit-login-button-text-size);\n";
    }
    $css .= "  width: min(100%, var(--brandkit-login-field-width));\n";
    $css .= "  max-width: var(--brandkit-login-field-width);\n";
    $css .= "  margin-left: auto;\n";
    $css .= "  margin-right: auto;\n";
    $css .= "  display: block;\n";
    $css .= "  transition: transform 0.15s ease, box-shadow 0.2s ease, background-color 0.2s ease, color 0.2s ease;\n";
    $css .= "}\n";
    if ($buttonHoverEffect === 'press') {
        $css .= "body.welcome-anonymous button[type=\"submit\"]:hover,\n";
        $css .= "body.welcome-anonymous .form-footer .btn:hover,\n";
        $css .= "body.welcome-anonymous .btn.btn-primary:not(.brandkit-login-button):hover {\n";
        $css .= "  transform: translateY(1px) scale(0.98);\n";
        $css .= "  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);\n";
        $css .= "}\n";
        $css .= "body.welcome-anonymous button[type=\"submit\"]:active,\n";
        $css .= "body.welcome-anonymous .form-footer .btn:active,\n";
        $css .= "body.welcome-anonymous .btn.btn-primary:not(.brandkit-login-button):active {\n";
        $css .= "  transform: translateY(2px) scale(0.97);\n";
        $css .= "  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);\n";
        $css .= "}\n";
    } elseif ($buttonHoverEffect === 'fill') {
        $css .= "body.welcome-anonymous button[type=\"submit\"]:hover,\n";
        $css .= "body.welcome-anonymous .form-footer .btn:hover,\n";
        $css .= "body.welcome-anonymous .btn.btn-primary:not(.brandkit-login-button):hover {\n";
        $css .= "  background: var(--brandkit-login-button-hover-fill) !important;\n";
        $css .= "  color: var(--brandkit-login-button-hover-text) !important;\n";
        $css .= "  border-color: var(--brandkit-login-button-hover-fill) !important;\n";
        $css .= "  box-shadow: inset 0 0 0 999px var(--brandkit-login-button-hover-fill), 0 4px 15px rgba(0, 0, 0, 0.3) !important;\n";
        $css .= "}\n";
    }

    $css .= ".page-anonymous::before,\n";
    $css .= "body.welcome-anonymous::before {\n";
    $css .= "  content: '';\n";
    $css .= "  position: fixed;\n";
    $css .= "  top: 0;\n";
    $css .= "  bottom: 0;\n";
    $css .= "  left: var(--brandkit-login-split-left);\n";
    $css .= "  right: var(--brandkit-login-split-right);\n";
    $css .= "  width: var(--brandkit-login-split-width);\n";
    $css .= "  background: var(--brandkit-login-split-color);\n";
    $css .= "  backdrop-filter: blur(var(--brandkit-login-split-blur));\n";
    $css .= "  -webkit-backdrop-filter: blur(var(--brandkit-login-split-blur));\n";
    $css .= "  z-index: 0;\n";
    $css .= "  pointer-events: none;\n";
    $css .= "}\n";

    $css .= ".page-anonymous .container-tight,\n";
    $css .= "body.welcome-anonymous .container-tight {\n";
    $css .= "  max-width: var(--brandkit-login-container-max-effective) !important;\n";
    $css .= "  width: min(100%, var(--brandkit-login-container-max-effective));\n";
    $css .= "  margin-left: var(--brandkit-login-container-left);\n";
    $css .= "  margin-right: var(--brandkit-login-container-right);\n";
    $css .= "  transform: translateX(var(--brandkit-login-container-translate));\n";
    $css .= "}\n";
    if (!$isGlpi11) {
        $css .= ".page-anonymous .card-body .row.justify-content-center,\n";
        $css .= "body.welcome-anonymous .card-body .row.justify-content-center {\n";
        $css .= "  width: 100%;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .card-body .row.justify-content-center > .col-md-5,\n";
        $css .= "body.welcome-anonymous .card-body .row.justify-content-center > .col-md-5 {\n";
        $css .= "  flex: 0 0 100%;\n";
        $css .= "  max-width: 100%;\n";
        $css .= "}\n";
    }
    $css .= "body.welcome-anonymous .container-tight {\n";
    $css .= "  width: 100%;\n";
    $css .= "  perspective: 1000px;\n";
    $css .= "  position: relative;\n";
    $css .= "}\n";
    $css .= "@media screen and (min-width: 768px) {\n";
    $css .= "  body.welcome-anonymous .container-tight {\n";
    $css .= "    min-width: 650px;\n";
    $css .= "    max-width: 700px;\n";
    $css .= "  }\n";
    $css .= "}\n";
    $css .= "@media screen and (max-width: 767px) {\n";
    $css .= "  body.welcome-anonymous .container-tight {\n";
    $css .= "    max-width: 450px;\n";
    $css .= "    min-width: unset;\n";
    $css .= "  }\n";
    $css .= "  .page-anonymous,\n";
    $css .= "  body.welcome-anonymous,\n";
    $css .= "  body.brandkit-error-page {\n";
    $css .= "    background-size: cover !important;\n";
    $css .= "    background-position: center !important;\n";
    $css .= "    background-attachment: scroll !important;\n";
    $css .= "  }\n";
    $css .= "  body.welcome-anonymous .page-anonymous .card-body,\n";
    $css .= "  body.welcome-anonymous .main-content-card .card-body,\n";
    $css .= "  .page-anonymous .main-content-card .card-body {\n";
    if ($isGlpi11) {
        $css .= "    padding: calc(1.1rem * var(--brandkit-login-panel-scale));\n";
    } else {
        $css .= "    padding: 1rem 1rem 0.75rem;\n";
    }
    $css .= "  }\n";
    $css .= "}\n";
    $css .= "@media screen and (max-height: 760px) {\n";
    $css .= "  body.welcome-anonymous {\n";
    $css .= "    align-items: flex-start;\n";
    $css .= "  }\n";
    $css .= "  body.welcome-anonymous .flex-fill {\n";
    $css .= "    padding-top: 2rem !important;\n";
    $css .= "  }\n";
    $css .= "}\n";

    $css .= ".page-anonymous .container-tight .text-center,\n";
    $css .= "body.welcome-anonymous .container-tight .text-center {\n";
    $css .= "  text-align: var(--brandkit-login-text-align) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous.brandkit-logo-inside .container-tight > .text-center,\n";
    $css .= "body.welcome-anonymous.brandkit-logo-inside .container-tight > .text-center {\n";
    $css .= "  display: block !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous.brandkit-logo-inside .container-tight > .text-center .glpi-logo,\n";
    $css .= "body.welcome-anonymous.brandkit-logo-inside .container-tight > .text-center .glpi-logo {\n";
    $css .= "  display: none !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .main-content-card,\n";
    $css .= ".page-anonymous .main-content-card a,\n";
    $css .= ".page-anonymous .main-content-card .text-muted,\n";
    $css .= "body.welcome-anonymous .main-content-card,\n";
    $css .= "body.welcome-anonymous .main-content-card a,\n";
    $css .= "body.welcome-anonymous .main-content-card .text-muted {\n";
    $css .= "  color: var(--brandkit-login-text-color) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .main-content-card input,\n";
    $css .= ".page-anonymous .main-content-card select,\n";
    $css .= ".page-anonymous .main-content-card textarea,\n";
    $css .= "body.welcome-anonymous .main-content-card input,\n";
    $css .= "body.welcome-anonymous .main-content-card select,\n";
    $css .= "body.welcome-anonymous .main-content-card textarea {\n";
    $css .= "  color: var(--brandkit-login-field-text) !important;\n";
    $css .= "  caret-color: var(--brandkit-login-field-text);\n";
    $css .= "}\n";
    $css .= "body.welcome-anonymous .page-anonymous,\n";
    $css .= "body.welcome-anonymous .page-anonymous * {\n";
    $css .= "  color: var(--brandkit-login-text-color) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]),\n";
    $css .= ".page-anonymous .main-content-card select,\n";
    $css .= ".page-anonymous .main-content-card textarea,\n";
    $css .= ".page-anonymous .main-content-card .form-control,\n";
    $css .= ".page-anonymous .main-content-card .form-select,\n";
    $css .= "body.welcome-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]),\n";
    $css .= "body.welcome-anonymous .main-content-card select,\n";
    $css .= "body.welcome-anonymous .main-content-card textarea,\n";
    $css .= "body.welcome-anonymous .main-content-card .form-control,\n";
    $css .= "body.welcome-anonymous .main-content-card .form-select {\n";
    $css .= "  background-color: var(--brandkit-login-field-bg) !important;\n";
    $css .= "  color: var(--brandkit-login-field-text) !important;\n";
    $css .= "  border: var(--brandkit-login-field-border-width) solid var(--brandkit-login-field-border) !important;\n";
    $css .= "  border-radius: var(--brandkit-login-field-radius) !important;\n";
    $css .= "  box-shadow: var(--brandkit-login-field-shadow) !important;\n";
    $css .= "  --brandkit-login-field-shadow-current: var(--brandkit-login-field-shadow);\n";
    $css .= "}\n";
    $css .= ".page-anonymous #login_name,\n";
    $css .= "body.welcome-anonymous #login_name {\n";
    $css .= "  background-image: var(--brandkit-login-icon-user) !important;\n";
    $css .= "  background-repeat: no-repeat !important;\n";
    $css .= "  background-position: var(--brandkit-login-icon-position) !important;\n";
    $css .= "  background-size: 18px 18px !important;\n";
    $css .= "  padding-left: var(--brandkit-login-icon-user-padding-left) !important;\n";
    $css .= "  padding-right: var(--brandkit-login-icon-user-padding-right) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous #login_password,\n";
    $css .= "body.welcome-anonymous #login_password {\n";
    $css .= "  background-image: var(--brandkit-login-icon-password) !important;\n";
    $css .= "  background-repeat: no-repeat !important;\n";
    $css .= "  background-position: var(--brandkit-login-icon-position) !important;\n";
    $css .= "  background-size: 18px 18px !important;\n";
    $css .= "  padding-left: var(--brandkit-login-icon-password-padding-left) !important;\n";
    $css .= "  padding-right: var(--brandkit-login-icon-password-padding-right) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .main-content-card input::placeholder,\n";
    $css .= ".page-anonymous .main-content-card textarea::placeholder,\n";
    $css .= "body.welcome-anonymous .main-content-card input::placeholder,\n";
    $css .= "body.welcome-anonymous .main-content-card textarea::placeholder {\n";
    $css .= "  color: var(--brandkit-login-field-placeholder) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .main-content-card .form-control:focus,\n";
    $css .= ".page-anonymous .main-content-card .form-select:focus,\n";
    $css .= "body.welcome-anonymous .main-content-card .form-control:focus,\n";
    $css .= "body.welcome-anonymous .main-content-card .form-select:focus {\n";
    $css .= "  border-color: var(--brandkit-login-field-focus-border) !important;\n";
    $css .= "  box-shadow: 0 0 0 3px color-mix(in srgb, var(--brandkit-login-field-focus-glow) 35%, transparent) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .main-content-card .select2-container--default .select2-selection--single,\n";
    $css .= "body.welcome-anonymous .main-content-card .select2-container--default .select2-selection--single {\n";
    $css .= "  background-color: var(--brandkit-login-field-bg) !important;\n";
    $css .= "  border: var(--brandkit-login-field-border-width) solid var(--brandkit-login-field-border) !important;\n";
    $css .= "  border-radius: var(--brandkit-login-field-radius) !important;\n";
    $css .= "  box-shadow: var(--brandkit-login-field-shadow) !important;\n";
    if ($isGlpi11) {
        $css .= "  height: calc(var(--brandkit-login-field-height) * var(--brandkit-login-panel-scale)) !important;\n";
    } else {
        $css .= "  height: var(--brandkit-login-field-height) !important;\n";
    }
    $css .= "}\n";
    $css .= ".page-anonymous .main-content-card .select2-selection__rendered,\n";
    $css .= "body.welcome-anonymous .main-content-card .select2-selection__rendered {\n";
    $css .= "  color: var(--brandkit-login-field-text) !important;\n";
    if ($isGlpi11) {
        $css .= "  line-height: calc(var(--brandkit-login-field-height) * var(--brandkit-login-panel-scale) - 2px);\n";
    } else {
        $css .= "  line-height: calc(var(--brandkit-login-field-height) - 2px);\n";
    }
    $css .= "}\n";
    $css .= ".page-anonymous .select2-dropdown,\n";
    $css .= "body.welcome-anonymous .select2-dropdown {\n";
    $css .= "  background-color: var(--brandkit-login-field-bg) !important;\n";
    $css .= "  border-color: var(--brandkit-login-field-border) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .select2-results__option,\n";
    $css .= "body.welcome-anonymous .select2-results__option {\n";
    $css .= "  color: var(--brandkit-login-field-text) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .select2-container--default .select2-selection--single:focus,\n";
    $css .= "body.welcome-anonymous .select2-container--default .select2-selection--single:focus {\n";
    $css .= "  border-color: var(--brandkit-login-field-focus-border) !important;\n";
    $css .= "  box-shadow: 0 0 0 3px color-mix(in srgb, var(--brandkit-login-field-focus-glow) 35%, transparent) !important;\n";
    $css .= "}\n";
    $css .= "body.welcome-anonymous .select2-container--default .select2-selection--single .select2-selection__rendered {\n";
    $css .= "  color: var(--brandkit-login-field-text) !important;\n";
    $css .= "}\n";
    $css .= "body.welcome-anonymous .select2-container--default .select2-results__option,\n";
    $css .= "body.welcome-anonymous .select2-container--default .select2-results__group {\n";
    $css .= "  color: var(--brandkit-login-field-text) !important;\n";
    $css .= "}\n";
    $css .= "body.welcome-anonymous .select2-container--open .select2-dropdown {\n";
    $css .= "  color: var(--brandkit-login-field-text) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous select[name=\"auth\"] + .select2 .select2-selection__rendered,\n";
    $css .= ".page-anonymous select[name=\"auth\"] + .select2 .select2-selection__rendered span,\n";
    $css .= ".page-anonymous select[name=\"auth\"] + .select2 .select2-selection__placeholder,\n";
    $css .= "body.welcome-anonymous select[name=\"auth\"] + .select2 .select2-selection__rendered,\n";
    $css .= "body.welcome-anonymous select[name=\"auth\"] + .select2 .select2-selection__rendered span,\n";
    $css .= "body.welcome-anonymous select[name=\"auth\"] + .select2 .select2-selection__placeholder {\n";
    $css .= "  color: var(--brandkit-login-field-text) !important;\n";
    $css .= "  text-align: left !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous select[name=\"auth\"] + .select2 .select2-selection__rendered,\n";
    $css .= "body.welcome-anonymous select[name=\"auth\"] + .select2 .select2-selection__rendered {\n";
    $css .= "  line-height: normal !important;\n";
    $css .= "  padding-left: calc(0.8rem * var(--brandkit-login-panel-scale)) !important;\n";
    $css .= "  padding-right: calc(2rem * var(--brandkit-login-panel-scale)) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous select[name=\"auth\"] + .select2 .select2-selection--single,\n";
    $css .= "body.welcome-anonymous select[name=\"auth\"] + .select2 .select2-selection--single {\n";
    $css .= "  display: flex !important;\n";
    $css .= "  align-items: center !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous select,\n";
    $css .= "body.welcome-anonymous select,\n";
    $css .= ".page-anonymous .form-select,\n";
    $css .= "body.welcome-anonymous .form-select,\n";
    $css .= ".page-anonymous .select2-selection__rendered,\n";
    $css .= "body.welcome-anonymous .select2-selection__rendered,\n";
    $css .= ".page-anonymous .select2-search__field,\n";
    $css .= "body.welcome-anonymous .select2-search__field {\n";
    $css .= "  color: var(--brandkit-login-field-text) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous select option,\n";
    $css .= "body.welcome-anonymous select option {\n";
    $css .= "  color: var(--brandkit-login-field-text) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .form-check-input:checked,\n";
    $css .= "body.welcome-anonymous .form-check-input:checked {\n";
    $css .= "  background-color: var(--brandkit-login-accent-color) !important;\n";
    $css .= "  border-color: var(--brandkit-login-accent-color) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .form-check-input:focus,\n";
    $css .= "body.welcome-anonymous .form-check-input:focus {\n";
    $css .= "  box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--brandkit-login-accent-color) 35%, transparent) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .select2-results__option--highlighted,\n";
    $css .= "body.welcome-anonymous .select2-results__option--highlighted {\n";
    $css .= "  background-color: var(--brandkit-login-accent-color) !important;\n";
    $css .= "  color: #ffffff !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .select2-results__option[aria-selected=\"true\"],\n";
    $css .= "body.welcome-anonymous .select2-results__option[aria-selected=\"true\"] {\n";
    $css .= "  background-color: color-mix(in srgb, var(--brandkit-login-accent-color) 75%, transparent) !important;\n";
    $css .= "  color: #ffffff !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous input::selection,\n";
    $css .= ".page-anonymous textarea::selection,\n";
    $css .= ".page-anonymous .select2-search__field::selection,\n";
    $css .= "body.welcome-anonymous input::selection,\n";
    $css .= "body.welcome-anonymous textarea::selection,\n";
    $css .= "body.welcome-anonymous .select2-search__field::selection {\n";
    $css .= "  background: var(--brandkit-login-accent-color) !important;\n";
    $css .= "  color: #ffffff !important;\n";
    $css .= "}\n";

    if ($fieldStyle === 'solid') {
        $css .= ".page-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]),\n";
        $css .= ".page-anonymous .main-content-card textarea,\n";
        $css .= ".page-anonymous .main-content-card .form-control,\n";
        $css .= "body.welcome-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]),\n";
        $css .= "body.welcome-anonymous .main-content-card textarea,\n";
        $css .= "body.welcome-anonymous .main-content-card .form-control {\n";
        $css .= "  background-color: var(--brandkit-login-field-bg) !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous #login_name,\n";
        $css .= ".page-anonymous #login_password,\n";
        $css .= "body.welcome-anonymous #login_name,\n";
        $css .= "body.welcome-anonymous #login_password {\n";
        $css .= "  background-color: var(--brandkit-login-field-bg) !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous #login_name,\n";
        $css .= "body.welcome-anonymous #login_name {\n";
        $css .= "  background-image: linear-gradient(var(--brandkit-login-field-bg), var(--brandkit-login-field-bg)), var(--brandkit-login-icon-user) !important;\n";
        $css .= "  background-repeat: no-repeat, no-repeat !important;\n";
        $css .= "  background-position: 0 0, var(--brandkit-login-icon-position) !important;\n";
        $css .= "  background-size: 100% 100%, 18px 18px !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous #login_password,\n";
        $css .= "body.welcome-anonymous #login_password {\n";
        $css .= "  background-image: linear-gradient(var(--brandkit-login-field-bg), var(--brandkit-login-field-bg)), var(--brandkit-login-icon-password) !important;\n";
        $css .= "  background-repeat: no-repeat, no-repeat !important;\n";
        $css .= "  background-position: 0 0, var(--brandkit-login-icon-position) !important;\n";
        $css .= "  background-size: 100% 100%, 18px 18px !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]):focus,\n";
        $css .= ".page-anonymous .main-content-card input[type=\"password\"]:focus,\n";
        $css .= ".page-anonymous .main-content-card textarea:focus,\n";
        $css .= ".page-anonymous .main-content-card .form-control:focus,\n";
        $css .= "body.welcome-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]):focus,\n";
        $css .= "body.welcome-anonymous .main-content-card input[type=\"password\"]:focus,\n";
        $css .= "body.welcome-anonymous .main-content-card textarea:focus,\n";
        $css .= "body.welcome-anonymous .main-content-card .form-control:focus {\n";
        $css .= "  background-color: var(--brandkit-login-field-bg) !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card input:-webkit-autofill,\n";
        $css .= ".page-anonymous .main-content-card input:-webkit-autofill:hover,\n";
        $css .= ".page-anonymous .main-content-card input:-webkit-autofill:focus,\n";
        $css .= ".page-anonymous .main-content-card input:-webkit-autofill:active,\n";
        $css .= ".page-anonymous .main-content-card input.form-control:-webkit-autofill,\n";
        $css .= ".page-anonymous .main-content-card input.form-control:-webkit-autofill:hover,\n";
        $css .= ".page-anonymous .main-content-card input.form-control:-webkit-autofill:focus,\n";
        $css .= ".page-anonymous .main-content-card input:autofill,\n";
        $css .= ".page-anonymous .main-content-card input:-internal-autofill-selected,\n";
        $css .= ".page-anonymous .main-content-card input:-moz-autofill,\n";
        $css .= ".page-anonymous .main-content-card input:-moz-autofill:focus,\n";
        $css .= ".page-anonymous .main-content-card input.form-control:-moz-autofill,\n";
        $css .= ".page-anonymous .main-content-card input.form-control:-moz-autofill:focus,\n";
        $css .= ".page-anonymous #login_name:-webkit-autofill,\n";
        $css .= ".page-anonymous #login_name:-webkit-autofill:hover,\n";
        $css .= ".page-anonymous #login_name:-webkit-autofill:focus,\n";
        $css .= ".page-anonymous #login_name:autofill,\n";
        $css .= ".page-anonymous #login_name:-internal-autofill-selected,\n";
        $css .= ".page-anonymous #login_name:-moz-autofill,\n";
        $css .= ".page-anonymous #login_name:-moz-autofill:focus,\n";
        $css .= ".page-anonymous #login_password:-webkit-autofill,\n";
        $css .= ".page-anonymous #login_password:-webkit-autofill:hover,\n";
        $css .= ".page-anonymous #login_password:-webkit-autofill:focus,\n";
        $css .= ".page-anonymous #login_password:autofill,\n";
        $css .= ".page-anonymous #login_password:-internal-autofill-selected,\n";
        $css .= ".page-anonymous #login_password:-moz-autofill,\n";
        $css .= ".page-anonymous #login_password:-moz-autofill:focus,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-webkit-autofill,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-webkit-autofill:hover,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-webkit-autofill:focus,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-webkit-autofill:active,\n";
        $css .= "body.welcome-anonymous .main-content-card input.form-control:-webkit-autofill,\n";
        $css .= "body.welcome-anonymous .main-content-card input.form-control:-webkit-autofill:hover,\n";
        $css .= "body.welcome-anonymous .main-content-card input.form-control:-webkit-autofill:focus,\n";
        $css .= "body.welcome-anonymous .main-content-card input:autofill,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-internal-autofill-selected,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-moz-autofill,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-moz-autofill:focus,\n";
        $css .= "body.welcome-anonymous .main-content-card input.form-control:-moz-autofill,\n";
        $css .= "body.welcome-anonymous .main-content-card input.form-control:-moz-autofill:focus {\n";
        $css .= "  -webkit-box-shadow: 0 0 0px 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
        $css .= "  box-shadow: 0 0 0px 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
        $css .= "  background-color: var(--brandkit-login-field-bg-autofill) !important;\n";
        $css .= "  -webkit-text-fill-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  caret-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  transition: background-color 99999s ease-in-out 0s !important;\n";
        $css .= "  -webkit-background-clip: padding-box !important;\n";
        $css .= "  background-clip: padding-box !important;\n";
        $css .= "  filter: none !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous #login_name:-webkit-autofill,\n";
        $css .= ".page-anonymous #login_name:-webkit-autofill:hover,\n";
        $css .= ".page-anonymous #login_name:-webkit-autofill:focus,\n";
        $css .= ".page-anonymous #login_name:-webkit-autofill:active,\n";
        $css .= ".page-anonymous #login_name:autofill,\n";
        $css .= ".page-anonymous #login_name:-internal-autofill-selected,\n";
        $css .= ".page-anonymous #login_name:-moz-autofill,\n";
        $css .= ".page-anonymous #login_name:-moz-autofill:focus,\n";
        $css .= "body.welcome-anonymous #login_name:-webkit-autofill,\n";
        $css .= "body.welcome-anonymous #login_name:-webkit-autofill:hover,\n";
        $css .= "body.welcome-anonymous #login_name:-webkit-autofill:focus,\n";
        $css .= "body.welcome-anonymous #login_name:-webkit-autofill:active,\n";
        $css .= "body.welcome-anonymous #login_name:autofill,\n";
        $css .= "body.welcome-anonymous #login_name:-internal-autofill-selected,\n";
        $css .= "body.welcome-anonymous #login_name:-moz-autofill,\n";
        $css .= "body.welcome-anonymous #login_name:-moz-autofill:focus {\n";
        $css .= "  background: var(--brandkit-login-icon-user), linear-gradient(var(--brandkit-login-field-bg-autofill), var(--brandkit-login-field-bg-autofill)) !important;\n";
        $css .= "  background-repeat: no-repeat, no-repeat !important;\n";
        $css .= "  background-position: var(--brandkit-login-icon-position), 0 0 !important;\n";
        $css .= "  background-size: 18px 18px, 100% 100% !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous #login_password:-webkit-autofill,\n";
        $css .= ".page-anonymous #login_password:-webkit-autofill:hover,\n";
        $css .= ".page-anonymous #login_password:-webkit-autofill:focus,\n";
        $css .= ".page-anonymous #login_password:-webkit-autofill:active,\n";
        $css .= ".page-anonymous #login_password:autofill,\n";
        $css .= ".page-anonymous #login_password:-internal-autofill-selected,\n";
        $css .= ".page-anonymous #login_password:-moz-autofill,\n";
        $css .= ".page-anonymous #login_password:-moz-autofill:focus,\n";
        $css .= "body.welcome-anonymous #login_password:-webkit-autofill,\n";
        $css .= "body.welcome-anonymous #login_password:-webkit-autofill:hover,\n";
        $css .= "body.welcome-anonymous #login_password:-webkit-autofill:focus,\n";
        $css .= "body.welcome-anonymous #login_password:-webkit-autofill:active,\n";
        $css .= "body.welcome-anonymous #login_password:autofill,\n";
        $css .= "body.welcome-anonymous #login_password:-internal-autofill-selected,\n";
        $css .= "body.welcome-anonymous #login_password:-moz-autofill,\n";
        $css .= "body.welcome-anonymous #login_password:-moz-autofill:focus {\n";
        $css .= "  background: var(--brandkit-login-icon-password), linear-gradient(var(--brandkit-login-field-bg-autofill), var(--brandkit-login-field-bg-autofill)) !important;\n";
        $css .= "  background-repeat: no-repeat, no-repeat !important;\n";
        $css .= "  background-position: var(--brandkit-login-icon-position), 0 0 !important;\n";
        $css .= "  background-size: 18px 18px, 100% 100% !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous #login_name:-webkit-autofill,\n";
        $css .= ".page-anonymous #login_name:-webkit-autofill:hover,\n";
        $css .= ".page-anonymous #login_name:-webkit-autofill:focus,\n";
        $css .= ".page-anonymous #login_name:autofill,\n";
        $css .= ".page-anonymous #login_name:-internal-autofill-selected,\n";
        $css .= ".page-anonymous #login_name:-moz-autofill,\n";
        $css .= ".page-anonymous #login_name:-moz-autofill:focus,\n";
        $css .= "body.welcome-anonymous #login_name:-webkit-autofill,\n";
        $css .= "body.welcome-anonymous #login_name:-webkit-autofill:hover,\n";
        $css .= "body.welcome-anonymous #login_name:-webkit-autofill:focus,\n";
        $css .= "body.welcome-anonymous #login_name:autofill,\n";
        $css .= "body.welcome-anonymous #login_name:-internal-autofill-selected,\n";
        $css .= "body.welcome-anonymous #login_name:-moz-autofill,\n";
        $css .= "body.welcome-anonymous #login_name:-moz-autofill:focus {\n";
        $css .= "  -webkit-box-shadow: 0 0 0px 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
        $css .= "  box-shadow: 0 0 0px 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
        $css .= "  background-color: var(--brandkit-login-field-bg-autofill) !important;\n";
        $css .= "  background-image: var(--brandkit-login-icon-user) !important;\n";
        $css .= "  background-repeat: no-repeat !important;\n";
        $css .= "  background-position: var(--brandkit-login-icon-position) !important;\n";
        $css .= "  background-size: 18px 18px !important;\n";
        $css .= "  padding-left: var(--brandkit-login-icon-user-padding-left) !important;\n";
        $css .= "  padding-right: var(--brandkit-login-icon-user-padding-right) !important;\n";
        $css .= "  -webkit-text-fill-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  caret-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
    $css .= "  -webkit-background-clip: padding-box !important;\n";
    $css .= "  background-clip: padding-box !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .card input.form-control#login_name,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name {\n";
    $css .= "  background-color: var(--brandkit-login-field-bg) !important;\n";
    $css .= "  background-image: var(--brandkit-login-icon-user) !important;\n";
    $css .= "  background-repeat: no-repeat !important;\n";
    $css .= "  background-position: var(--brandkit-login-icon-position) !important;\n";
    $css .= "  background-size: 18px 18px !important;\n";
    $css .= "  -webkit-background-clip: padding-box !important;\n";
    $css .= "  background-clip: padding-box !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .card input.form-control#login_password,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password {\n";
    $css .= "  background-color: var(--brandkit-login-field-bg) !important;\n";
    $css .= "  background-image: var(--brandkit-login-icon-password) !important;\n";
    $css .= "  background-repeat: no-repeat !important;\n";
    $css .= "  background-position: var(--brandkit-login-icon-position) !important;\n";
    $css .= "  background-size: 18px 18px !important;\n";
    $css .= "  -webkit-background-clip: padding-box !important;\n";
    $css .= "  background-clip: padding-box !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .card input.form-control#login_name:-webkit-autofill,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:-webkit-autofill:hover,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:-webkit-autofill:focus,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:-webkit-autofill:active,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:-webkit-autofill,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:-webkit-autofill:hover,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:-webkit-autofill:focus,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:-webkit-autofill:active,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:autofill,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:autofill:hover,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:autofill:focus,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:autofill:active,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:-moz-autofill,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:-moz-autofill:focus,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:autofill,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:autofill:hover,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:autofill:focus,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:autofill:active,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:-moz-autofill,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:-moz-autofill:focus {\n";
    $css .= "  background: var(--brandkit-login-icon-user), linear-gradient(var(--brandkit-login-field-bg-autofill), var(--brandkit-login-field-bg-autofill)) !important;\n";
    $css .= "  background-repeat: no-repeat, no-repeat !important;\n";
    $css .= "  background-position: var(--brandkit-login-icon-position), 0 0 !important;\n";
    $css .= "  background-size: 18px 18px, 100% 100% !important;\n";
    $css .= "  padding-left: var(--brandkit-login-icon-user-padding-left) !important;\n";
    $css .= "  padding-right: var(--brandkit-login-icon-user-padding-right) !important;\n";
    $css .= "  -webkit-box-shadow: 0 0 0 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
    $css .= "  box-shadow: 0 0 0 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
    $css .= "  background-color: var(--brandkit-login-field-bg-autofill) !important;\n";
    $css .= "  -webkit-background-clip: padding-box !important;\n";
    $css .= "  background-clip: padding-box !important;\n";
    $css .= "  -webkit-text-fill-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
    $css .= "  color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
    $css .= "  filter: none !important;\n";
    $css .= "  appearance: none !important;\n";
    $css .= "  -moz-appearance: none !important;\n";
    $css .= "  -webkit-appearance: none !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .card input.form-control#login_password:-webkit-autofill,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:-webkit-autofill:hover,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:-webkit-autofill:focus,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:-webkit-autofill:active,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:-webkit-autofill,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:-webkit-autofill:hover,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:-webkit-autofill:focus,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:-webkit-autofill:active,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:autofill,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:autofill:hover,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:autofill:focus,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:autofill:active,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:-moz-autofill,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:-moz-autofill:focus,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:autofill,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:autofill:hover,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:autofill:focus,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:autofill:active,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:-moz-autofill,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:-moz-autofill:focus {\n";
    $css .= "  background: var(--brandkit-login-icon-password), linear-gradient(var(--brandkit-login-field-bg-autofill), var(--brandkit-login-field-bg-autofill)) !important;\n";
    $css .= "  background-repeat: no-repeat, no-repeat !important;\n";
    $css .= "  background-position: var(--brandkit-login-icon-position), 0 0 !important;\n";
    $css .= "  background-size: 18px 18px, 100% 100% !important;\n";
    $css .= "  padding-left: var(--brandkit-login-icon-password-padding-left) !important;\n";
    $css .= "  padding-right: var(--brandkit-login-icon-password-padding-right) !important;\n";
    $css .= "  -webkit-box-shadow: 0 0 0 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
    $css .= "  box-shadow: 0 0 0 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
    $css .= "  background-color: var(--brandkit-login-field-bg-autofill) !important;\n";
    $css .= "  -webkit-background-clip: padding-box !important;\n";
    $css .= "  background-clip: padding-box !important;\n";
    $css .= "  -webkit-text-fill-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
    $css .= "  color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
    $css .= "  filter: none !important;\n";
    $css .= "  appearance: none !important;\n";
    $css .= "  -moz-appearance: none !important;\n";
    $css .= "  -webkit-appearance: none !important;\n";
    $css .= "}\n";
        $css .= ".page-anonymous #login_password:-webkit-autofill,\n";
        $css .= ".page-anonymous #login_password:-webkit-autofill:hover,\n";
        $css .= ".page-anonymous #login_password:-webkit-autofill:focus,\n";
        $css .= ".page-anonymous #login_password:autofill,\n";
        $css .= ".page-anonymous #login_password:-internal-autofill-selected,\n";
        $css .= ".page-anonymous #login_password:-moz-autofill,\n";
        $css .= ".page-anonymous #login_password:-moz-autofill:focus,\n";
        $css .= "body.welcome-anonymous #login_password:-webkit-autofill,\n";
        $css .= "body.welcome-anonymous #login_password:-webkit-autofill:hover,\n";
        $css .= "body.welcome-anonymous #login_password:-webkit-autofill:focus,\n";
        $css .= "body.welcome-anonymous #login_password:autofill,\n";
        $css .= "body.welcome-anonymous #login_password:-internal-autofill-selected,\n";
        $css .= "body.welcome-anonymous #login_password:-moz-autofill,\n";
        $css .= "body.welcome-anonymous #login_password:-moz-autofill:focus {\n";
        $css .= "  -webkit-box-shadow: 0 0 0px 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
        $css .= "  box-shadow: 0 0 0px 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
        $css .= "  background-color: var(--brandkit-login-field-bg-autofill) !important;\n";
        $css .= "  background-image: var(--brandkit-login-icon-password) !important;\n";
        $css .= "  background-repeat: no-repeat !important;\n";
        $css .= "  background-position: var(--brandkit-login-icon-position) !important;\n";
        $css .= "  background-size: 18px 18px !important;\n";
        $css .= "  padding-left: var(--brandkit-login-icon-password-padding-left) !important;\n";
        $css .= "  padding-right: var(--brandkit-login-icon-password-padding-right) !important;\n";
        $css .= "  -webkit-text-fill-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  caret-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous #login_name:-webkit-autofill,\n";
        $css .= ".page-anonymous #login_name:-webkit-autofill:hover,\n";
        $css .= ".page-anonymous #login_name:-webkit-autofill:focus,\n";
        $css .= ".page-anonymous #login_name:autofill,\n";
        $css .= ".page-anonymous #login_name:-internal-autofill-selected,\n";
        $css .= ".page-anonymous #login_name:-moz-autofill,\n";
        $css .= ".page-anonymous #login_name:-moz-autofill:focus,\n";
        $css .= "body.welcome-anonymous #login_name:-webkit-autofill,\n";
        $css .= "body.welcome-anonymous #login_name:-webkit-autofill:hover,\n";
        $css .= "body.welcome-anonymous #login_name:-webkit-autofill:focus,\n";
        $css .= "body.welcome-anonymous #login_name:autofill,\n";
        $css .= "body.welcome-anonymous #login_name:-internal-autofill-selected,\n";
        $css .= "body.welcome-anonymous #login_name:-moz-autofill,\n";
        $css .= "body.welcome-anonymous #login_name:-moz-autofill:focus {\n";
        $css .= "  background-image: var(--brandkit-login-icon-user) !important;\n";
        $css .= "  background-repeat: no-repeat !important;\n";
        $css .= "  background-position: var(--brandkit-login-icon-position) !important;\n";
        $css .= "  background-size: 18px 18px !important;\n";
        $css .= "  padding-left: var(--brandkit-login-icon-user-padding-left) !important;\n";
        $css .= "  padding-right: var(--brandkit-login-icon-user-padding-right) !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous #login_password:-webkit-autofill,\n";
        $css .= ".page-anonymous #login_password:-webkit-autofill:hover,\n";
        $css .= ".page-anonymous #login_password:-webkit-autofill:focus,\n";
        $css .= ".page-anonymous #login_password:autofill,\n";
        $css .= ".page-anonymous #login_password:-internal-autofill-selected,\n";
        $css .= ".page-anonymous #login_password:-moz-autofill,\n";
        $css .= ".page-anonymous #login_password:-moz-autofill:focus,\n";
        $css .= "body.welcome-anonymous #login_password:-webkit-autofill,\n";
        $css .= "body.welcome-anonymous #login_password:-webkit-autofill:hover,\n";
        $css .= "body.welcome-anonymous #login_password:-webkit-autofill:focus,\n";
        $css .= "body.welcome-anonymous #login_password:autofill,\n";
        $css .= "body.welcome-anonymous #login_password:-internal-autofill-selected,\n";
        $css .= "body.welcome-anonymous #login_password:-moz-autofill,\n";
        $css .= "body.welcome-anonymous #login_password:-moz-autofill:focus {\n";
        $css .= "  background-image: var(--brandkit-login-icon-password) !important;\n";
        $css .= "  background-repeat: no-repeat !important;\n";
        $css .= "  background-position: var(--brandkit-login-icon-position) !important;\n";
        $css .= "  background-size: 18px 18px !important;\n";
        $css .= "  padding-left: var(--brandkit-login-icon-password-padding-left) !important;\n";
        $css .= "  padding-right: var(--brandkit-login-icon-password-padding-right) !important;\n";
        $css .= "}\n";
    } elseif ($fieldStyle === 'outline') {
        $css .= ".page-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]),\n";
        $css .= ".page-anonymous .main-content-card select,\n";
        $css .= ".page-anonymous .main-content-card textarea,\n";
        $css .= ".page-anonymous .main-content-card .form-control,\n";
        $css .= ".page-anonymous .main-content-card .form-select,\n";
        $css .= "body.welcome-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]),\n";
        $css .= "body.welcome-anonymous .main-content-card select,\n";
        $css .= "body.welcome-anonymous .main-content-card textarea,\n";
        $css .= "body.welcome-anonymous .main-content-card .form-control,\n";
        $css .= "body.welcome-anonymous .main-content-card .form-select {\n";
        $css .= "  background-color: transparent !important;\n";
        $css .= "  border-width: var(--brandkit-login-field-border-width) !important;\n";
        $css .= "  box-shadow: none !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card .select2-container--default .select2-selection--single,\n";
        $css .= "body.welcome-anonymous .main-content-card .select2-container--default .select2-selection--single {\n";
        $css .= "  background-color: transparent !important;\n";
        $css .= "  border-width: var(--brandkit-login-field-border-width) !important;\n";
        $css .= "  box-shadow: none !important;\n";
        $css .= "}\n";
    } elseif ($fieldStyle === 'underline') {
        $css .= ".page-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]),\n";
        $css .= ".page-anonymous .main-content-card select,\n";
        $css .= ".page-anonymous .main-content-card textarea,\n";
        $css .= ".page-anonymous .main-content-card .form-control,\n";
        $css .= ".page-anonymous .main-content-card .form-select,\n";
        $css .= "body.welcome-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]),\n";
        $css .= "body.welcome-anonymous .main-content-card select,\n";
        $css .= "body.welcome-anonymous .main-content-card textarea,\n";
        $css .= "body.welcome-anonymous .main-content-card .form-control,\n";
        $css .= "body.welcome-anonymous .main-content-card .form-select {\n";
        $css .= "  background-color: transparent !important;\n";
        $css .= "  border-radius: 0 !important;\n";
        $css .= "  border-width: 0 0 var(--brandkit-login-field-border-width) 0 !important;\n";
        $css .= "  box-shadow: none !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card .select2-container--default .select2-selection--single,\n";
        $css .= "body.welcome-anonymous .main-content-card .select2-container--default .select2-selection--single {\n";
        $css .= "  background-color: transparent !important;\n";
        $css .= "  border-radius: 0 !important;\n";
        $css .= "  border-width: 0 0 var(--brandkit-login-field-border-width) 0 !important;\n";
        $css .= "  box-shadow: none !important;\n";
        $css .= "}\n";
    } elseif ($fieldStyle === 'glass') {
        $css .= ".page-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]),\n";
        $css .= ".page-anonymous .main-content-card select,\n";
        $css .= ".page-anonymous .main-content-card textarea,\n";
        $css .= ".page-anonymous .main-content-card .form-control,\n";
        $css .= ".page-anonymous .main-content-card .form-select,\n";
        $css .= "body.welcome-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]),\n";
        $css .= "body.welcome-anonymous .main-content-card select,\n";
        $css .= "body.welcome-anonymous .main-content-card textarea,\n";
        $css .= "body.welcome-anonymous .main-content-card .form-control,\n";
        $css .= "body.welcome-anonymous .main-content-card .form-select {\n";
        $css .= "  background-color: color-mix(in srgb, var(--brandkit-login-field-bg) 82%, transparent) !important;\n";
        $css .= "  backdrop-filter: blur(8px);\n";
        $css .= "  -webkit-backdrop-filter: blur(8px);\n";
        $css .= "}\n";
    } elseif ($fieldStyle === 'neon') {
        $css .= ".page-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]),\n";
        $css .= ".page-anonymous .main-content-card select,\n";
        $css .= ".page-anonymous .main-content-card textarea,\n";
        $css .= ".page-anonymous .main-content-card .form-control,\n";
        $css .= ".page-anonymous .main-content-card .form-select,\n";
        $css .= "body.welcome-anonymous .main-content-card input:not([type=\"checkbox\"]):not([type=\"radio\"]),\n";
        $css .= "body.welcome-anonymous .main-content-card select,\n";
        $css .= "body.welcome-anonymous .main-content-card textarea,\n";
        $css .= "body.welcome-anonymous .main-content-card .form-control,\n";
        $css .= "body.welcome-anonymous .main-content-card .form-select {\n";
        $css .= "  --brandkit-login-field-shadow-current: 0 0 0 1px var(--brandkit-login-field-border), 0 12px 26px color-mix(in srgb, var(--brandkit-login-field-focus) 35%, transparent);\n";
        $css .= "  box-shadow: var(--brandkit-login-field-shadow-current) !important;\n";
        $css .= "}\n";
    }
    $css .= ".page-anonymous .main-content-card input:-webkit-autofill,\n";
    $css .= ".page-anonymous .main-content-card input:-webkit-autofill:hover,\n";
    $css .= ".page-anonymous .main-content-card input:-webkit-autofill:focus,\n";
    $css .= ".page-anonymous .main-content-card input:-webkit-autofill:active,\n";
    $css .= ".page-anonymous .main-content-card input.form-control:-webkit-autofill,\n";
    $css .= ".page-anonymous .main-content-card input.form-control:-webkit-autofill:hover,\n";
    $css .= ".page-anonymous .main-content-card input.form-control:-webkit-autofill:focus,\n";
    $css .= ".page-anonymous .main-content-card input:autofill,\n";
    $css .= ".page-anonymous .main-content-card input:autofill:hover,\n";
    $css .= ".page-anonymous .main-content-card input:autofill:focus,\n";
    $css .= ".page-anonymous .main-content-card input:autofill:active,\n";
    $css .= ".page-anonymous .main-content-card input:-internal-autofill-selected,\n";
    $css .= ".page-anonymous .main-content-card input:-moz-autofill,\n";
    $css .= ".page-anonymous .main-content-card input:-moz-autofill:focus,\n";
    $css .= ".page-anonymous .main-content-card input.form-control:-moz-autofill,\n";
    $css .= ".page-anonymous .main-content-card input.form-control:-moz-autofill:focus,\n";
    $css .= "body.welcome-anonymous .main-content-card input:-webkit-autofill,\n";
    $css .= "body.welcome-anonymous .main-content-card input:-webkit-autofill:hover,\n";
    $css .= "body.welcome-anonymous .main-content-card input:-webkit-autofill:focus,\n";
    $css .= "body.welcome-anonymous .main-content-card input:-webkit-autofill:active,\n";
    $css .= "body.welcome-anonymous .main-content-card input.form-control:-webkit-autofill,\n";
    $css .= "body.welcome-anonymous .main-content-card input.form-control:-webkit-autofill:hover,\n";
    $css .= "body.welcome-anonymous .main-content-card input.form-control:-webkit-autofill:focus,\n";
    $css .= "body.welcome-anonymous .main-content-card input:autofill,\n";
    $css .= "body.welcome-anonymous .main-content-card input:autofill:hover,\n";
    $css .= "body.welcome-anonymous .main-content-card input:autofill:focus,\n";
    $css .= "body.welcome-anonymous .main-content-card input:autofill:active,\n";
    $css .= "body.welcome-anonymous .main-content-card input:-internal-autofill-selected,\n";
    $css .= "body.welcome-anonymous .main-content-card input:-moz-autofill,\n";
    $css .= "body.welcome-anonymous .main-content-card input:-moz-autofill:focus,\n";
    $css .= "body.welcome-anonymous .main-content-card input.form-control:-moz-autofill,\n";
    $css .= "body.welcome-anonymous .main-content-card input.form-control:-moz-autofill:focus {\n";
    $css .= "  --brandkit-login-field-shadow-current: none;\n";
    $css .= "  background-color: var(--brandkit-login-field-bg-autofill) !important;\n";
    $css .= "  border: var(--brandkit-login-field-border-width) solid var(--brandkit-login-field-border) !important;\n";
    $css .= "  border-radius: var(--brandkit-login-field-radius) !important;\n";
    $css .= "  -webkit-box-shadow: 0 0 0 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current) !important;\n";
    $css .= "  box-shadow: 0 0 0 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current) !important;\n";
    $css .= "  -webkit-text-fill-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
    $css .= "  color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
    $css .= "  caret-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
    $css .= "  background-clip: padding-box !important;\n";
    $css .= "  filter: none !important;\n";
    $css .= "  appearance: none !important;\n";
    $css .= "  -moz-appearance: none !important;\n";
    $css .= "  -webkit-appearance: none !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .card input.form-control#login_name:-webkit-autofill,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:-webkit-autofill:hover,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:-webkit-autofill:focus,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:-webkit-autofill:active,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:autofill,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:autofill:hover,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:autofill:focus,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:autofill:active,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:-moz-autofill,\n";
    $css .= ".page-anonymous .card input.form-control#login_name:-moz-autofill:focus,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:-webkit-autofill,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:-webkit-autofill:hover,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:-webkit-autofill:focus,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:-webkit-autofill:active,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:autofill,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:autofill:hover,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:autofill:focus,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:autofill:active,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:-moz-autofill,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_name:-moz-autofill:focus {\n";
    $css .= "  background: var(--brandkit-login-icon-user), linear-gradient(var(--brandkit-login-field-bg-autofill), var(--brandkit-login-field-bg-autofill)) !important;\n";
    $css .= "  background-repeat: no-repeat, no-repeat !important;\n";
    $css .= "  background-position: var(--brandkit-login-icon-position), 0 0 !important;\n";
    $css .= "  background-size: 18px 18px, 100% 100% !important;\n";
    $css .= "  padding-left: var(--brandkit-login-icon-user-padding-left) !important;\n";
    $css .= "  padding-right: var(--brandkit-login-icon-user-padding-right) !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .card input.form-control#login_password:-webkit-autofill,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:-webkit-autofill:hover,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:-webkit-autofill:focus,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:-webkit-autofill:active,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:autofill,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:autofill:hover,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:autofill:focus,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:autofill:active,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:-moz-autofill,\n";
    $css .= ".page-anonymous .card input.form-control#login_password:-moz-autofill:focus,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:-webkit-autofill,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:-webkit-autofill:hover,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:-webkit-autofill:focus,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:-webkit-autofill:active,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:autofill,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:autofill:hover,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:autofill:focus,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:autofill:active,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:-moz-autofill,\n";
    $css .= "body.welcome-anonymous .card input.form-control#login_password:-moz-autofill:focus {\n";
    $css .= "  background: var(--brandkit-login-icon-password), linear-gradient(var(--brandkit-login-field-bg-autofill), var(--brandkit-login-field-bg-autofill)) !important;\n";
    $css .= "  background-repeat: no-repeat, no-repeat !important;\n";
    $css .= "  background-position: var(--brandkit-login-icon-position), 0 0 !important;\n";
    $css .= "  background-size: 18px 18px, 100% 100% !important;\n";
    $css .= "  padding-left: var(--brandkit-login-icon-password-padding-left) !important;\n";
    $css .= "  padding-right: var(--brandkit-login-icon-password-padding-right) !important;\n";
    $css .= "}\n";
    $css .= "body.welcome-anonymous .page-anonymous .card {\n";
    $css .= "  padding: 2rem 2.5rem;\n";
    $css .= "  margin-top: 5px;\n";
    if ($panelHoverEffect) {
        $css .= "  transition: transform 0.3s ease, box-shadow 0.3s ease;\n";
    } else {
        $css .= "  transition: none;\n";
    }
    $css .= "}\n";
    if ($panelHoverEffect) {
        $css .= "body.welcome-anonymous .page-anonymous .card:hover {\n";
        $css .= "  transform: translateY(-5px);\n";
        $css .= "  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 8px 20px rgba(0, 0, 0, 0.4), 0 0 25px rgba(255, 255, 255, 0.075);\n";
        $css .= "  background: var(--brandkit-login-panel-color) !important;\n";
        $css .= "  backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
        $css .= "  -webkit-backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
        $css .= "}\n";
    }
    $css .= "@media (max-width: 850px) {\n";
    $css .= "  .page-anonymous .main-content-card,\n";
    $css .= "  body.welcome-anonymous .main-content-card {\n";
    $css .= "    width: 90vw !important;\n";
    $css .= "    height: 90vh !important;\n";
    $css .= "    max-height: none !important;\n";
    $css .= "  }\n";
    $css .= "  body.welcome-anonymous {\n";
    $css .= "    align-items: flex-start;\n";
    $css .= "  }\n";
    $css .= "  body.welcome-anonymous .flex-fill {\n";
    $css .= "    padding-top: 2rem !important;\n";
    $css .= "  }\n";
    $css .= "  .page-anonymous .container-tight,\n";
    $css .= "  body.welcome-anonymous .container-tight {\n";
    $css .= "    max-width: 90vw !important;\n";
    $css .= "    width: 90vw !important;\n";
    $css .= "  }\n";
    $css .= "  .page-anonymous .main-content-card,\n";
    $css .= "  body.welcome-anonymous .main-content-card {\n";
    $css .= "    padding: 1.25rem 1.5rem !important;\n";
    $css .= "  }\n";
    $css .= "  .page-anonymous .main-content-card::before,\n";
    $css .= "  body.welcome-anonymous .main-content-card::before,\n";
    $css .= "  .page-anonymous .glpi-logo,\n";
    $css .= "  body.welcome-anonymous .glpi-logo {\n";
    $css .= "    width: min(var(--brandkit-login-logo-width), 70vw) !important;\n";
    $css .= "    height: min(var(--brandkit-login-logo-height), 120px) !important;\n";
    $css .= "  }\n";
    $css .= "  .page-anonymous .login-title,\n";
    $css .= "  body.welcome-anonymous .login-title {\n";
    $css .= "    font-size: 1.1rem !important;\n";
    $css .= "  }\n";
    $css .= "  .page-anonymous,\n";
    $css .= "  body.welcome-anonymous {\n";
    $css .= "    --brandkit-login-split-width: 100% !important;\n";
    $css .= "  }\n";
    $css .= "  .page-anonymous .container-tight,\n";
    $css .= "  body.welcome-anonymous .container-tight {\n";
    $css .= "    margin-left: auto !important;\n";
    $css .= "    margin-right: auto !important;\n";
    $css .= "    transform: translateX(0) !important;\n";
    $css .= "  }\n";
    $css .= "  .page-anonymous .container-tight .text-center,\n";
    $css .= "  body.welcome-anonymous .container-tight .text-center {\n";
    $css .= "    text-align: center !important;\n";
    $css .= "  }\n";
    $css .= "}\n";
    $css .= "@media (max-width: 640px) {\n";
    $css .= "  .page-anonymous,\n";
    $css .= "  body.welcome-anonymous,\n";
    $css .= "  body.brandkit-error-page {\n";
    $css .= "    background-size: cover !important;\n";
    $css .= "    background-repeat: no-repeat !important;\n";
    $css .= "    background-position: center !important;\n";
    $css .= "    background-attachment: scroll !important;\n";
    $css .= "  }\n";
    $css .= "  .page-anonymous .main-content-card,\n";
    $css .= "  body.welcome-anonymous .main-content-card {\n";
    $css .= "    height: auto !important;\n";
    $css .= "    max-height: none !important;\n";
    $css .= "  }\n";
    $css .= "  .page-anonymous .main-content-card .card-body,\n";
    $css .= "  body.welcome-anonymous .main-content-card .card-body {\n";
    $css .= "    padding-bottom: calc(1rem * var(--brandkit-login-panel-scale));\n";
    $css .= "  }\n";
    $css .= "  body.welcome-anonymous .page-anonymous .card {\n";
    $css .= "    padding-bottom: calc(1.25rem * var(--brandkit-login-panel-scale));\n";
    $css .= "  }\n";
    $css .= "  .page-anonymous .form-footer,\n";
    $css .= "  body.welcome-anonymous .form-footer {\n";
    $css .= "    margin-bottom: 0 !important;\n";
    $css .= "  }\n";
    $css .= "}\n";
    $css .= "@-moz-document url-prefix() {\n";
    $css .= "  body.welcome-anonymous .main-content-card {\n";
    $css .= "    padding-top: 12px;\n";
    $css .= "    padding-bottom: 12px;\n";
    $css .= "  }\n";
    $css .= "  body.welcome-anonymous .page-anonymous .card {\n";
    $css .= "    margin-top: 12px;\n";
    $css .= "  }\n";
    if ($panelHoverEffect) {
        $css .= "  body.welcome-anonymous .page-anonymous .card {\n";
        $css .= "    position: relative;\n";
        $css .= "    top: 0;\n";
        $css .= "    transition: top 0.3s ease, box-shadow 0.3s ease;\n";
        $css .= "  }\n";
        $css .= "  body.welcome-anonymous .page-anonymous .card:hover {\n";
        $css .= "    top: -5px;\n";
        $css .= "    transform: none;\n";
        $css .= "    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 8px 20px rgba(0, 0, 0, 0.4), 0 0 25px rgba(255, 255, 255, 0.075);\n";
        $css .= "    background: var(--brandkit-login-panel-color) !important;\n";
        $css .= "    backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
        $css .= "    -webkit-backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
        $css .= "  }\n";
    }
    $css .= "  .page-anonymous .main-content-card input:-moz-autofill,\n";
    $css .= "  .page-anonymous .main-content-card input:-moz-autofill:focus,\n";
    $css .= "  .page-anonymous .main-content-card input.form-control:-moz-autofill,\n";
    $css .= "  .page-anonymous .main-content-card input.form-control:-moz-autofill:focus,\n";
    $css .= "  body.welcome-anonymous .main-content-card input:-moz-autofill,\n";
    $css .= "  body.welcome-anonymous .main-content-card input:-moz-autofill:focus,\n";
    $css .= "  body.welcome-anonymous .main-content-card input.form-control:-moz-autofill,\n";
    $css .= "  body.welcome-anonymous .main-content-card input.form-control:-moz-autofill:focus {\n";
    $css .= "    box-shadow: 0 0 0px 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
    $css .= "    background-color: var(--brandkit-login-field-bg-autofill) !important;\n";
    $css .= "    color: var(--brandkit-login-field-text) !important;\n";
    $css .= "    caret-color: var(--brandkit-login-field-text) !important;\n";
    $css .= "    background-clip: padding-box !important;\n";
    $css .= "  }\n";
    $css .= "  .page-anonymous #login_name:-moz-autofill,\n";
    $css .= "  .page-anonymous #login_name:-moz-autofill:focus,\n";
    $css .= "  body.welcome-anonymous #login_name:-moz-autofill,\n";
    $css .= "  body.welcome-anonymous #login_name:-moz-autofill:focus {\n";
    $css .= "    background: var(--brandkit-login-icon-user), linear-gradient(var(--brandkit-login-field-bg-autofill), var(--brandkit-login-field-bg-autofill)) !important;\n";
    $css .= "    background-repeat: no-repeat, no-repeat !important;\n";
    $css .= "    background-position: var(--brandkit-login-icon-position), 0 0 !important;\n";
    $css .= "    background-size: 18px 18px, 100% 100% !important;\n";
    $css .= "    padding-left: var(--brandkit-login-icon-user-padding-left) !important;\n";
    $css .= "    padding-right: var(--brandkit-login-icon-user-padding-right) !important;\n";
    $css .= "  }\n";
    $css .= "  .page-anonymous #login_password:-moz-autofill,\n";
    $css .= "  .page-anonymous #login_password:-moz-autofill:focus,\n";
    $css .= "  body.welcome-anonymous #login_password:-moz-autofill,\n";
    $css .= "  body.welcome-anonymous #login_password:-moz-autofill:focus {\n";
    $css .= "    background: var(--brandkit-login-icon-password), linear-gradient(var(--brandkit-login-field-bg-autofill), var(--brandkit-login-field-bg-autofill)) !important;\n";
    $css .= "    background-repeat: no-repeat, no-repeat !important;\n";
    $css .= "    background-position: var(--brandkit-login-icon-position), 0 0 !important;\n";
    $css .= "    background-size: 18px 18px, 100% 100% !important;\n";
    $css .= "    padding-left: var(--brandkit-login-icon-password-padding-left) !important;\n";
    $css .= "    padding-right: var(--brandkit-login-icon-password-padding-right) !important;\n";
    $css .= "  }\n";
    $css .= "}\n";
    $css .= "body.welcome-anonymous .card-header {\n";
    $css .= "  margin: 0;\n";
    $css .= "  padding: 0 !important;\n";
    $css .= "  min-height: 0 !important;\n";
    $css .= "}\n";
    $css .= "body.welcome-anonymous .card-header.mb-4 {\n";
    if ($isGlpi11) {
        $css .= "  margin-bottom: calc(0.2rem * var(--brandkit-login-panel-scale)) !important;\n";
    } else {
        $css .= "  margin-bottom: calc(var(--brandkit-login-field-spacing) * 0.75) !important;\n";
    }
    $css .= "}\n";
    $css .= "body.welcome-anonymous .card-header h2,\n";
    $css .= "body.welcome-anonymous .card-header .mx-auto {\n";
    if (!$isGlpi11) {
        $css .= "  font-size: var(--brandkit-login-title-size) !important;\n";
        $css .= "  line-height: 1.2 !important;\n";
        $css .= "  text-align: center !important;\n";
    }
    $css .= "  margin-bottom: 0 !important;\n";
    $css .= "}\n";
    $css .= "body.welcome-anonymous .card-header:empty {\n";
    $css .= "  display: none !important;\n";
    $css .= "}\n";

    if ($isGlpi11) {
        $css .= "body.welcome-anonymous .alert,\n";
        $css .= "body.welcome-anonymous .alert-danger,\n";
        $css .= "body.welcome-anonymous .alert-warning,\n";
        $css .= "body.welcome-anonymous .alert-info {\n";
        $css .= "  background-color: transparent !important;\n";
        $css .= "  border: 1px solid rgba(220, 53, 69, 0.3) !important;\n";
        $css .= "  border-radius: 0.75rem !important;\n";
        $css .= "  color: var(--brandkit-login-text-color) !important;\n";
        $css .= "  backdrop-filter: blur(5px);\n";
        $css .= "  -webkit-backdrop-filter: blur(5px);\n";
        $css .= "  box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);\n";
        $css .= "  margin-bottom: 1rem;\n";
        $css .= "  padding: 1rem 1.25rem;\n";
        $css .= "}\n";
        $css .= "body.welcome-anonymous .alert-danger {\n";
        $css .= "  border-color: rgba(220, 53, 69, 0.4) !important;\n";
        $css .= "  background-color: rgba(220, 53, 69, 0.1) !important;\n";
        $css .= "}\n";
        $css .= "body.welcome-anonymous .alert-warning {\n";
        $css .= "  border-color: rgba(245, 158, 11, 0.45) !important;\n";
        $css .= "  background-color: rgba(245, 158, 11, 0.12) !important;\n";
        $css .= "}\n";
        $css .= "body.welcome-anonymous .alert *,\n";
        $css .= "body.welcome-anonymous .alert-danger *,\n";
        $css .= "body.welcome-anonymous .alert p,\n";
        $css .= "body.welcome-anonymous .alert span,\n";
        $css .= "body.welcome-anonymous .alert div {\n";
        $css .= "  color: var(--brandkit-login-text-color) !important;\n";
        $css .= "  background-color: transparent !important;\n";
        $css .= "}\n";
        $css .= "body.welcome-anonymous .alert .btn,\n";
        $css .= "body.welcome-anonymous .alert-danger .btn {\n";
        $css .= "  background: rgba(255, 255, 255, 0.1) !important;\n";
        $css .= "  border: 1px solid rgba(255, 255, 255, 0.2) !important;\n";
        $css .= "  color: var(--brandkit-login-text-color) !important;\n";
        $css .= "  border-radius: 0.5rem;\n";
        $css .= "  transition: all 0.3s ease;\n";
        $css .= "}\n";
        $css .= "body.welcome-anonymous .alert .btn.btn-primary {\n";
        $css .= "  background: var(--brandkit-login-button-bg) !important;\n";
        $css .= "  border-color: var(--brandkit-login-button-border) !important;\n";
        $css .= "  color: var(--brandkit-login-button-text) !important;\n";
        $css .= "  font-size: calc(var(--brandkit-login-button-text-size) * var(--brandkit-login-panel-scale));\n";
        $css .= "  border-radius: var(--brandkit-login-button-radius) !important;\n";
        $css .= "  clip-path: var(--brandkit-login-button-clip);\n";
        $css .= "}\n";
        $css .= "body.welcome-anonymous .alert .btn:hover,\n";
        $css .= "body.welcome-anonymous .alert-danger .btn:hover {\n";
        $css .= "  background: rgba(255, 255, 255, 0.2) !important;\n";
        $css .= "  border-color: rgba(255, 255, 255, 0.3) !important;\n";
        $css .= "  transform: translateY(-1px);\n";
        $css .= "}\n";
    } else {
        $css .= ".page-anonymous .main-content-card .alert,\n";
        $css .= ".page-anonymous .main-content-card .alert-warning,\n";
        $css .= ".page-anonymous .main-content-card .alert-danger,\n";
        $css .= "body.welcome-anonymous .main-content-card .alert,\n";
        $css .= "body.welcome-anonymous .main-content-card .alert-warning,\n";
        $css .= "body.welcome-anonymous .main-content-card .alert-danger {\n";
        $css .= "  background: transparent !important;\n";
        $css .= "  border: none !important;\n";
        $css .= "  border-radius: 0 !important;\n";
        $css .= "  box-shadow: none !important;\n";
        $css .= "  padding: 0 !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card .alert .btn,\n";
        $css .= "body.welcome-anonymous .main-content-card .alert .btn {\n";
        $css .= "  display: inline-flex;\n";
        $css .= "  align-items: center;\n";
        $css .= "  justify-content: center;\n";
        $css .= "  gap: 0.35rem;\n";
        $css .= "  margin-top: calc(var(--brandkit-login-field-spacing) * 0.5);\n";
        $css .= "  width: 321px;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card .alert-title,\n";
        $css .= "body.welcome-anonymous .main-content-card .alert-title {\n";
        $css .= "  margin-bottom: calc(var(--brandkit-login-field-spacing) * 0.5) !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card .alert-title + div,\n";
        $css .= "body.welcome-anonymous .main-content-card .alert-title + div {\n";
        $css .= "  margin-bottom: calc(var(--brandkit-login-field-spacing) * 0.75) !important;\n";
        $css .= "  display: block;\n";
        $css .= "  background: transparent !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card .alert .btn.btn-primary,\n";
        $css .= "body.welcome-anonymous .main-content-card .alert .btn.btn-primary {\n";
        $css .= "  background: var(--brandkit-login-button-bg) !important;\n";
        $css .= "  border-color: var(--brandkit-login-button-border) !important;\n";
        $css .= "  color: var(--brandkit-login-button-text) !important;\n";
        $css .= "  font-size: var(--brandkit-login-button-text-size);\n";
        $css .= "  border-radius: var(--brandkit-login-button-radius) !important;\n";
        $css .= "  clip-path: var(--brandkit-login-button-clip);\n";
        $css .= "  font-weight: 600;\n";
        $css .= "  letter-spacing: 0.03em;\n";
        $css .= "  padding: 0.9rem 1.75rem;\n";
        $css .= "  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);\n";
        $css .= "  text-decoration: none;\n";
        $css .= "  width: 321px !important;\n";
        $css .= "  min-width: 321px !important;\n";
        $css .= "  max-width: 321px !important;\n";
        $css .= "}\n";
    }
    $css .= "body.welcome-anonymous .btn.btn-primary:not(.brandkit-login-button) {\n";
    $css .= "  background: var(--brandkit-login-button-bg) !important;\n";
    $css .= "  border-color: var(--brandkit-login-button-border) !important;\n";
    $css .= "  color: var(--brandkit-login-button-text) !important;\n";
    if ($isGlpi11) {
        $css .= "  font-size: calc(var(--brandkit-login-button-text-size) * var(--brandkit-login-panel-scale));\n";
    } else {
        $css .= "  font-size: var(--brandkit-login-button-text-size);\n";
    }
    $css .= "  border-radius: var(--brandkit-login-button-radius) !important;\n";
    $css .= "  clip-path: var(--brandkit-login-button-clip);\n";
    $css .= "  font-weight: 600;\n";
    $css .= "  letter-spacing: 0.03em;\n";
    $css .= "  padding: 0.9rem 1.75rem;\n";
    $css .= "  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);\n";
    $css .= "  width: 100%;\n";
    $css .= "}\n";
    $css .= "body.welcome-anonymous button[type=\"submit\"][name=\"submit\"],\n";
    $css .= "body.welcome-anonymous .form-footer .btn {\n";
    $css .= "  background: var(--brandkit-login-button-bg) !important;\n";
    $css .= "  border-color: var(--brandkit-login-button-border) !important;\n";
    $css .= "  color: var(--brandkit-login-button-text) !important;\n";
    if ($isGlpi11) {
        $css .= "  font-size: calc(var(--brandkit-login-button-text-size) * var(--brandkit-login-panel-scale));\n";
    } else {
        $css .= "  font-size: var(--brandkit-login-button-text-size);\n";
    }
    $css .= "  border-radius: var(--brandkit-login-button-radius) !important;\n";
    $css .= "  clip-path: var(--brandkit-login-button-clip);\n";
    $css .= "  font-weight: 600;\n";
    $css .= "  letter-spacing: 0.03em;\n";
    $css .= "  padding: 0.9rem 1.75rem;\n";
    $css .= "  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);\n";
    $css .= "  width: 100%;\n";
    $css .= "}\n";
    $css .= "body.welcome-anonymous,\n";
    $css .= "body.welcome-anonymous #page,\n";
    $css .= "body.welcome-anonymous main {\n";
    $css .= "  background-color: var(--brandkit-login-error-overlay, {$wallpaperOverlayRgba}) !important;\n";
    $css .= "  background-image: linear-gradient(var(--brandkit-login-error-overlay, {$wallpaperOverlayRgba}), var(--brandkit-login-error-overlay, {$wallpaperOverlayRgba})), var(--brandkit-login-error-wallpaper, none) !important;\n";
    $css .= "  background-size: {$wallpaperSizeValue} !important;\n";
    $css .= "  background-position: {$wallpaperPosition} !important;\n";
    $css .= "  background-repeat: no-repeat !important;\n";
    $css .= "  background-attachment: {$wallpaperAttachment} !important;\n";
    $css .= "}\n";
    $css .= "body.brandkit-error-page .navbar {\n";
    $css .= "  display: none !important;\n";
    $css .= "}\n";
    $css .= "body.brandkit-error-page .page,\n";
    $css .= "body.brandkit-error-page .page-wrapper,\n";
    $css .= "body.brandkit-error-page .page-body {\n";
    $css .= "  min-height: 100vh;\n";
    $css .= "}\n";
    $css .= "body.brandkit-error-page #page {\n";
    $css .= "  min-height: 100vh;\n";
    $css .= "  display: flex;\n";
    $css .= "  align-items: center;\n";
    $css .= "  justify-content: center;\n";
    $css .= "  padding: 2rem 1.5rem;\n";
    $css .= "}\n";
    $css .= "body.brandkit-error-page #page > .card {\n";
    $css .= "  background: var(--brandkit-login-panel-color) !important;\n";
    $css .= "  backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
    $css .= "  -webkit-backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
    $css .= "  border-radius: 1.5rem !important;\n";
    $css .= "  border: 1px solid rgba(255, 255, 255, 0.08) !important;\n";
    $css .= "  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 5px 15px rgba(0, 0, 0, 0.3), 0 0 20px rgba(255, 255, 255, 0.05) !important;\n";
    $css .= "  width: min(100%, 700px);\n";
    $css .= "  padding: 2rem 2.5rem;\n";
    $css .= "}\n";
    $css .= "body.brandkit-error-page #page .alert,\n";
    $css .= "body.brandkit-error-page #page .alert-danger,\n";
    $css .= "body.brandkit-error-page #page .alert-warning,\n";
    $css .= "body.brandkit-error-page #page .alert-info {\n";
    $css .= "  background-color: transparent !important;\n";
    $css .= "  border: 1px solid rgba(220, 53, 69, 0.3) !important;\n";
    $css .= "  border-radius: 0.75rem !important;\n";
    $css .= "  color: var(--brandkit-login-text-color) !important;\n";
    $css .= "  backdrop-filter: blur(5px);\n";
    $css .= "  -webkit-backdrop-filter: blur(5px);\n";
    $css .= "  box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);\n";
    $css .= "  padding: 1rem 1.25rem;\n";
    $css .= "}\n";
    $css .= "body.brandkit-error-page #page .alert-warning {\n";
    $css .= "  border-color: rgba(245, 158, 11, 0.45) !important;\n";
    $css .= "  background-color: rgba(245, 158, 11, 0.12) !important;\n";
    $css .= "}\n";
    $css .= "body.brandkit-error-page #page .alert * {\n";
    $css .= "  color: var(--brandkit-login-text-color) !important;\n";
    $css .= "}\n";
    $css .= "body.brandkit-error-page #page .alert a,\n";
    $css .= "body.brandkit-error-page #page .btn.btn-primary {\n";
    $css .= "  background: var(--brandkit-login-button-bg) !important;\n";
    $css .= "  border-color: var(--brandkit-login-button-border) !important;\n";
    $css .= "  color: var(--brandkit-login-button-text) !important;\n";
    $css .= "  border-radius: var(--brandkit-login-button-radius) !important;\n";
    $css .= "  clip-path: var(--brandkit-login-button-clip);\n";
    $css .= "  font-weight: 600;\n";
    $css .= "  letter-spacing: 0.03em;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) .page,\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) .page-wrapper,\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) .page-body {\n";
    $css .= "  min-height: 100vh;\n";
    $css .= "  background: transparent !important;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) .navbar {\n";
    $css .= "  display: none !important;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) #page {\n";
    $css .= "  min-height: 100vh;\n";
    $css .= "  display: flex;\n";
    $css .= "  align-items: center;\n";
    $css .= "  justify-content: center;\n";
    $css .= "  padding: 2rem 1.5rem;\n";
    $css .= "  background-image: var(--brandkit-login-error-wallpaper, none) !important;\n";
    $css .= "  background-color: var(--brandkit-login-error-overlay, transparent) !important;\n";
    $css .= "  background-size: cover !important;\n";
    $css .= "  background-position: center !important;\n";
    $css .= "  background-repeat: no-repeat !important;\n";
    $css .= "  background-attachment: fixed !important;\n";
    $css .= "  background-blend-mode: darken !important;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) #page.legacy {\n";
    $css .= "  min-height: 100vh;\n";
    $css .= "  display: flex;\n";
    $css .= "  align-items: center;\n";
    $css .= "  justify-content: center;\n";
    $css .= "  padding: 2rem 1.5rem;\n";
    $css .= "  background-image: var(--brandkit-login-error-wallpaper, none) !important;\n";
    $css .= "  background-color: var(--brandkit-login-error-overlay, transparent) !important;\n";
    $css .= "  background-size: cover !important;\n";
    $css .= "  background-position: center !important;\n";
    $css .= "  background-repeat: no-repeat !important;\n";
    $css .= "  background-attachment: fixed !important;\n";
    $css .= "  background-blend-mode: darken !important;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) #page > .card,\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) #page .card,\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) #page.legacy > .card {\n";
    $css .= "  background: var(--brandkit-login-panel-color) !important;\n";
    $css .= "  backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
    $css .= "  -webkit-backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
    $css .= "  border-radius: 1.5rem !important;\n";
    $css .= "  border: 1px solid rgba(255, 255, 255, 0.08) !important;\n";
    $css .= "  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 5px 15px rgba(0, 0, 0, 0.3), 0 0 20px rgba(255, 255, 255, 0.05) !important;\n";
    $css .= "  width: min(100%, 700px);\n";
    $css .= "  padding: 2rem 2.5rem;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) #page .alert,\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) #page .alert-danger,\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) #page .alert-warning,\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) #page .alert-info {\n";
    $css .= "  background-color: transparent !important;\n";
    $css .= "  border: 1px solid rgba(220, 53, 69, 0.3) !important;\n";
    $css .= "  border-radius: 0.75rem !important;\n";
    $css .= "  color: var(--brandkit-login-text-color) !important;\n";
    $css .= "  backdrop-filter: blur(5px);\n";
    $css .= "  -webkit-backdrop-filter: blur(5px);\n";
    $css .= "  box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);\n";
    $css .= "  padding: 1rem 1.25rem;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) #page .alert-warning {\n";
    $css .= "  border-color: rgba(245, 158, 11, 0.45) !important;\n";
    $css .= "  background-color: rgba(245, 158, 11, 0.12) !important;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) #page .alert * {\n";
    $css .= "  color: var(--brandkit-login-text-color) !important;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) #page .alert a,\n";
    $css .= "body.horizontal-layout:not(.central):not(.helpdesk) #page .btn.btn-primary {\n";
    $css .= "  display: inline-flex;\n";
    $css .= "  align-items: center;\n";
    $css .= "  gap: 0.35rem;\n";
    $css .= "  margin-top: 0.75rem;\n";
    $css .= "  background: var(--brandkit-login-button-bg) !important;\n";
    $css .= "  border-color: var(--brandkit-login-button-border) !important;\n";
    $css .= "  color: var(--brandkit-login-button-text) !important;\n";
    $css .= "  border-radius: var(--brandkit-login-button-radius) !important;\n";
    $css .= "  clip-path: var(--brandkit-login-button-clip);\n";
    $css .= "  font-weight: 600;\n";
    $css .= "  letter-spacing: 0.03em;\n";
    $css .= "  padding: 0.9rem 1.75rem;\n";
    $css .= "  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);\n";
    $css .= "  text-decoration: none;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) {\n";
    $css .= "  background-image: var(--brandkit-login-error-wallpaper, none);\n";
    $css .= "  background-color: var(--brandkit-login-error-overlay, transparent);\n";
    $css .= "  background-size: cover;\n";
    $css .= "  background-position: center;\n";
    $css .= "  background-repeat: no-repeat;\n";
    $css .= "  background-attachment: fixed;\n";
    $css .= "  background-blend-mode: darken;\n";
    $css .= "  min-height: 100vh;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) .navbar {\n";
    $css .= "  display: none !important;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) .page,\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) .page-wrapper,\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) .page-body {\n";
    $css .= "  min-height: 100vh;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) #page {\n";
    $css .= "  min-height: 100vh;\n";
    $css .= "  display: flex;\n";
    $css .= "  align-items: center;\n";
    $css .= "  justify-content: center;\n";
    $css .= "  padding: 2rem 1.5rem;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) #page > .card {\n";
    $css .= "  background: var(--brandkit-login-panel-color) !important;\n";
    $css .= "  backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
    $css .= "  -webkit-backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
    $css .= "  border-radius: 1.5rem !important;\n";
    $css .= "  border: 1px solid rgba(255, 255, 255, 0.08) !important;\n";
    $css .= "  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 5px 15px rgba(0, 0, 0, 0.3), 0 0 20px rgba(255, 255, 255, 0.05) !important;\n";
    $css .= "  width: min(100%, 700px);\n";
    $css .= "  padding: 2rem 2.5rem;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) #page .alert,\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) #page .alert-danger,\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) #page .alert-warning,\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) #page .alert-info {\n";
    $css .= "  background-color: transparent !important;\n";
    $css .= "  border: 1px solid rgba(220, 53, 69, 0.3) !important;\n";
    $css .= "  border-radius: 0.75rem !important;\n";
    $css .= "  color: var(--brandkit-login-text-color) !important;\n";
    $css .= "  backdrop-filter: blur(5px);\n";
    $css .= "  -webkit-backdrop-filter: blur(5px);\n";
    $css .= "  box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);\n";
    $css .= "  padding: 1rem 1.25rem;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) #page .alert-warning {\n";
    $css .= "  border-color: rgba(245, 158, 11, 0.45) !important;\n";
    $css .= "  background-color: rgba(245, 158, 11, 0.12) !important;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) #page .alert * {\n";
    $css .= "  color: var(--brandkit-login-text-color) !important;\n";
    $css .= "}\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) #page .alert a,\n";
    $css .= "body.horizontal-layout:has(#page.legacy > .card) #page .btn.btn-primary {\n";
    $css .= "  display: inline-flex;\n";
    $css .= "  align-items: center;\n";
    $css .= "  gap: 0.35rem;\n";
    $css .= "  margin-top: 0.75rem;\n";
    $css .= "  background: var(--brandkit-login-button-bg) !important;\n";
    $css .= "  border-color: var(--brandkit-login-button-border) !important;\n";
    $css .= "  color: var(--brandkit-login-button-text) !important;\n";
    $css .= "  border-radius: var(--brandkit-login-button-radius) !important;\n";
    $css .= "  clip-path: var(--brandkit-login-button-clip);\n";
    $css .= "  font-weight: 600;\n";
    $css .= "  letter-spacing: 0.03em;\n";
    $css .= "  padding: 0.9rem 1.75rem;\n";
    $css .= "  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);\n";
    $css .= "  text-decoration: none;\n";
    $css .= "}\n";
    $css .= "#page.legacy > .card {\n";
    $css .= "  position: relative;\n";
    $css .= "  background: var(--brandkit-login-panel-color) !important;\n";
    $css .= "  backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
    $css .= "  -webkit-backdrop-filter: blur(var(--brandkit-login-panel-blur)) !important;\n";
    $css .= "  border-radius: 1.5rem !important;\n";
    $css .= "  border: 1px solid rgba(255, 255, 255, 0.08) !important;\n";
    $css .= "  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 5px 15px rgba(0, 0, 0, 0.3), 0 0 20px rgba(255, 255, 255, 0.05) !important;\n";
    $css .= "  width: min(100%, 700px);\n";
    $css .= "  margin: 8vh auto 0 auto;\n";
    $css .= "  padding: 2rem 2.5rem;\n";
    $css .= "}\n";
    $css .= "#page.legacy > .card::before {\n";
    $css .= "  content: '';\n";
    $css .= "  position: fixed;\n";
    $css .= "  inset: 0;\n";
    $css .= "  background-image: var(--brandkit-login-error-wallpaper, none);\n";
    $css .= "  background-color: var(--brandkit-login-error-overlay, transparent);\n";
    $css .= "  background-size: cover;\n";
    $css .= "  background-position: center;\n";
    $css .= "  background-repeat: no-repeat;\n";
    $css .= "  background-attachment: fixed;\n";
    $css .= "  background-blend-mode: darken;\n";
    $css .= "  z-index: -1;\n";
    $css .= "}\n";
    $css .= "#page.legacy > .card .alert,\n";
    $css .= "#page.legacy > .card .alert-danger,\n";
    $css .= "#page.legacy > .card .alert-warning,\n";
    $css .= "#page.legacy > .card .alert-info {\n";
    $css .= "  background-color: transparent !important;\n";
    $css .= "  border: 1px solid rgba(220, 53, 69, 0.3) !important;\n";
    $css .= "  border-radius: 0.75rem !important;\n";
    $css .= "  color: var(--brandkit-login-text-color) !important;\n";
    $css .= "  backdrop-filter: blur(5px);\n";
    $css .= "  -webkit-backdrop-filter: blur(5px);\n";
    $css .= "  box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);\n";
    $css .= "  padding: 1rem 1.25rem;\n";
    $css .= "}\n";
    $css .= "#page.legacy > .card .alert-warning {\n";
    $css .= "  border-color: rgba(245, 158, 11, 0.45) !important;\n";
    $css .= "  background-color: rgba(245, 158, 11, 0.12) !important;\n";
    $css .= "}\n";
    $css .= "#page.legacy > .card .alert * {\n";
    $css .= "  color: var(--brandkit-login-text-color) !important;\n";
    $css .= "}\n";
    $css .= "#page.legacy > .card .alert a,\n";
    $css .= "#page.legacy > .card .btn.btn-primary {\n";
    $css .= "  display: inline-flex;\n";
    $css .= "  align-items: center;\n";
    $css .= "  gap: 0.35rem;\n";
    $css .= "  margin-top: 0.75rem;\n";
    $css .= "  background: var(--brandkit-login-button-bg) !important;\n";
    $css .= "  border-color: var(--brandkit-login-button-border) !important;\n";
    $css .= "  color: var(--brandkit-login-button-text) !important;\n";
    $css .= "  border-radius: var(--brandkit-login-button-radius) !important;\n";
    $css .= "  clip-path: var(--brandkit-login-button-clip);\n";
    $css .= "  font-weight: 600;\n";
    $css .= "  letter-spacing: 0.03em;\n";
    $css .= "  padding: 0.9rem 1.75rem;\n";
    $css .= "  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);\n";
    $css .= "  text-decoration: none;\n";
    $css .= "}\n";
    $css .= "body.welcome-anonymous .alert .btn-close,\n";
    $css .= "body.welcome-anonymous .alert .close {\n";
    $css .= "  filter: brightness(0) invert(1);\n";
    $css .= "  opacity: 0.8;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .main-content-card .row.justify-content-center > .col-md-5,\n";
    $css .= "body.welcome-anonymous .main-content-card .row.justify-content-center > .col-md-5 {\n";
    $css .= "  flex: 0 0 100%;\n";
    $css .= "  max-width: 100%;\n";
    $css .= "}\n";
    $css .= "body.welcome-anonymous .page-anonymous .card-body,\n";
    $css .= "body.welcome-anonymous .main-content-card .card-body {\n";
    $css .= "  background: transparent !important;\n";
    $css .= "  box-sizing: border-box;\n";
    $css .= "  flex: 1;\n";
    $css .= "  overflow-y: auto;\n";
    $css .= "}\n";
    $css .= ".page-anonymous.brandkit-logo-inside .main-content-card .glpi-logo,\n";
    $css .= "body.welcome-anonymous.brandkit-logo-inside .main-content-card .glpi-logo {\n";
    $css .= "  margin: 0 auto 1rem auto;\n";
    $css .= "}\n";
    if ($logoPosition === 'inside') {
        $css .= ".page-anonymous .container-tight > .text-center,\n";
        $css .= "body.welcome-anonymous .container-tight > .text-center {\n";
        $css .= "  display: block !important;\n";
        $css .= "  text-align: center;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .container-tight > .text-center .glpi-logo,\n";
        $css .= "body.welcome-anonymous .container-tight > .text-center .glpi-logo {\n";
        $css .= "  display: none !important;\n";
        $css .= "}\n";
        $css .= ".page-anonymous .main-content-card::before,\n";
        $css .= "body.welcome-anonymous .main-content-card::before {\n";
        $css .= "  content: '';\n";
        $css .= "  display: block;\n";
        $css .= "  background: var(--glpi-logo-dark-login) no-repeat center;\n";
        $css .= "  background-size: contain;\n";
        $css .= "  width: calc(var(--brandkit-login-logo-width) * var(--brandkit-login-panel-scale));\n";
        $css .= "  max-width: none;\n";
        $css .= "  height: calc(var(--brandkit-login-logo-height) * var(--brandkit-login-panel-scale));\n";
        $css .= "  max-height: none;\n";
        $css .= "  flex: 0 0 auto;\n";
        $css .= "  margin: 0 auto calc(0.25rem * var(--brandkit-login-panel-scale)) auto;\n";
        $css .= "  align-self: center;\n";
        $css .= "}\n";
        $css .= ":root[data-glpi-theme-dark=\"1\"] .page-anonymous .main-content-card::before,\n";
        $css .= ":root[data-glpi-theme-dark=\"1\"] body.welcome-anonymous .main-content-card::before {\n";
        $css .= "  background: var(--glpi-logo-light-login) no-repeat center;\n";
        $css .= "  background-size: contain;\n";
        $css .= "}\n";
    } else {
        $css .= ".page-anonymous .flex-fill,\n";
        $css .= "body.welcome-anonymous .flex-fill {\n";
        $css .= "  padding-top: var(--brandkit-login-logo-offset) !important;\n";
        $css .= "}\n";
        if (!$isGlpi11) {
            $css .= ".page-anonymous > .text-center,\n";
            $css .= "body.welcome-anonymous > .text-center {\n";
            $css .= "  margin-top: var(--brandkit-login-logo-offset) !important;\n";
            $css .= "  width: min(100%, var(--brandkit-login-panel-width));\n";
            $css .= "  margin-left: auto;\n";
            $css .= "  margin-right: auto;\n";
            $css .= "}\n";
        }
    }

    if ($hideLoginText) {
        $css .= ".page-anonymous .card-header,\n";
        $css .= ".page-anonymous .login-title,\n";
        $css .= ".page-anonymous .page-title,\n";
        $css .= "body.welcome-anonymous .card-header,\n";
        $css .= "body.welcome-anonymous .login-title,\n";
        $css .= "body.welcome-anonymous .page-title {\n";
        $css .= "  display: none !important;\n";
        $css .= "}\n";
    }

    $css .= ".page-anonymous .brandkit-login-button,\n";
    $css .= "body.welcome-anonymous .brandkit-login-button {\n";
    $css .= "  font-weight: 600;\n";
    $css .= "  transition: transform 0.2s ease, box-shadow 0.2s ease;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .brandkit-login-button.brandkit-button--square,\n";
    $css .= "body.welcome-anonymous .brandkit-login-button.brandkit-button--square {\n";
    $css .= "  border-radius: 4px;\n";
    $css .= "  box-shadow: 0 8px 16px rgba(15, 23, 42, 0.18);\n";
    $css .= "}\n";
    $css .= ".page-anonymous .brandkit-login-button.brandkit-button--rounded,\n";
    $css .= "body.welcome-anonymous .brandkit-login-button.brandkit-button--rounded {\n";
    $css .= "  border-radius: 10px;\n";
    $css .= "  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.18);\n";
    $css .= "}\n";
    $css .= ".page-anonymous .brandkit-login-button.brandkit-button--pill,\n";
    $css .= "body.welcome-anonymous .brandkit-login-button.brandkit-button--pill {\n";
    $css .= "  border-radius: 999px;\n";
    $css .= "  padding-left: 1.6rem;\n";
    $css .= "  padding-right: 1.6rem;\n";
    $css .= "  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.2);\n";
    $css .= "}\n";
    $css .= ".page-anonymous .brandkit-login-button.brandkit-button--cut,\n";
    $css .= "body.welcome-anonymous .brandkit-login-button.brandkit-button--cut {\n";
    $css .= "  border-radius: 6px 18px 6px 18px;\n";
    $css .= "  letter-spacing: 0.04em;\n";
    $css .= "  text-transform: uppercase;\n";
    $css .= "  box-shadow: 0 14px 28px rgba(15, 23, 42, 0.2);\n";
    $css .= "  clip-path: polygon(0 0, 92% 0, 100% 35%, 100% 100%, 8% 100%, 0 65%);\n";
    $css .= "}\n";
    $css .= ".page-anonymous .brandkit-login-button.brandkit-button--soft,\n";
    $css .= "body.welcome-anonymous .brandkit-login-button.brandkit-button--soft {\n";
    $css .= "  border-radius: 14px;\n";
    $css .= "  box-shadow: 0 14px 30px rgba(15, 23, 42, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.35);\n";
    $css .= "}\n";

    if ($buttonColorMode === 'custom') {
        $css .= ".page-anonymous .brandkit-login-button.brandkit-color--custom,\n";
        $css .= "body.welcome-anonymous .brandkit-login-button.brandkit-color--custom {\n";
        $css .= "  background-color: var(--brandkit-login-button-bg) !important;\n";
        $css .= "  border-color: var(--brandkit-login-button-border) !important;\n";
        $css .= "  color: var(--brandkit-login-button-text) !important;\n";
        $css .= "}\n";
    }

    $css .= "body.brandkit-error-page,\n";
    $css .= "body.brandkit-error-page #page,\n";
    $css .= "body.brandkit-error-page main,\n";
    $css .= "body.brandkit-error-page .page-anonymous {\n";
    $css .= "  background-color: #000000 !important;\n";
    $css .= "  background-image: none !important;\n";
    $css .= "}\n";
    $css .= "body.brandkit-error-page .card,\n";
    $css .= "body.brandkit-error-page .card-body {\n";
    $css .= "  background: transparent !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .copyright,\n";
    $css .= "body.welcome-anonymous .copyright {\n";
    $css .= "  display: block !important;\n";
    $css .= "  visibility: visible !important;\n";
    $css .= "  opacity: 1 !important;\n";
    $css .= "  position: relative;\n";
    $css .= "  z-index: 2;\n";
    $css .= "  width: min(100%, var(--brandkit-login-container-max-effective));\n";
    $css .= "  margin: 1rem auto 2rem auto;\n";
    $css .= "  text-align: center;\n";
    $css .= "  padding: 0 1rem;\n";
    $css .= "  color: color-mix(in srgb, var(--brandkit-login-text-color) 75%, transparent) !important;\n";
    $css .= "  font-size: 0.75rem;\n";
    $css .= "  letter-spacing: 0.02em;\n";
    $css .= "  text-decoration: none;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .copyright:hover,\n";
    $css .= "body.welcome-anonymous .copyright:hover {\n";
    $css .= "  color: var(--brandkit-login-text-color) !important;\n";
    $css .= "  text-decoration: underline;\n";
    $css .= "}\n";
    if ($fieldStyle !== 'solid') {
        $css .= ".page-anonymous .main-content-card input:-webkit-autofill,\n";
        $css .= ".page-anonymous .main-content-card input:-webkit-autofill:hover,\n";
        $css .= ".page-anonymous .main-content-card input:-webkit-autofill:focus,\n";
        $css .= ".page-anonymous .main-content-card input:-webkit-autofill:active,\n";
        $css .= ".page-anonymous .main-content-card input.form-control:-webkit-autofill,\n";
        $css .= ".page-anonymous .main-content-card input.form-control:-webkit-autofill:hover,\n";
        $css .= ".page-anonymous .main-content-card input.form-control:-webkit-autofill:focus,\n";
        $css .= ".page-anonymous .main-content-card input:autofill,\n";
        $css .= ".page-anonymous .main-content-card input:-internal-autofill-selected,\n";
        $css .= ".page-anonymous .main-content-card input:-moz-autofill,\n";
        $css .= ".page-anonymous .main-content-card input:-moz-autofill:focus,\n";
        $css .= ".page-anonymous .main-content-card input.form-control:-moz-autofill,\n";
        $css .= ".page-anonymous .main-content-card input.form-control:-moz-autofill:focus,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-webkit-autofill,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-webkit-autofill:hover,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-webkit-autofill:focus,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-webkit-autofill:active,\n";
        $css .= "body.welcome-anonymous .main-content-card input.form-control:-webkit-autofill,\n";
        $css .= "body.welcome-anonymous .main-content-card input.form-control:-webkit-autofill:hover,\n";
        $css .= "body.welcome-anonymous .main-content-card input.form-control:-webkit-autofill:focus,\n";
        $css .= "body.welcome-anonymous .main-content-card input:autofill,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-internal-autofill-selected,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-moz-autofill,\n";
        $css .= "body.welcome-anonymous .main-content-card input:-moz-autofill:focus,\n";
        $css .= "body.welcome-anonymous .main-content-card input.form-control:-moz-autofill,\n";
        $css .= "body.welcome-anonymous .main-content-card input.form-control:-moz-autofill:focus {\n";
        $css .= "  -webkit-box-shadow: 0 0 0px 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
        $css .= "  box-shadow: 0 0 0px 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
        $css .= "  background-color: var(--brandkit-login-field-bg-autofill) !important;\n";
        $css .= "  -webkit-text-fill-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  caret-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  -webkit-background-clip: padding-box !important;\n";
        $css .= "  background-clip: padding-box !important;\n";
        $css .= "  filter: none !important;\n";
        $css .= "}\n";
    }
    $css .= ".page-anonymous #login_name:-webkit-autofill,\n";
    $css .= ".page-anonymous #login_name:-webkit-autofill:hover,\n";
    $css .= ".page-anonymous #login_name:-webkit-autofill:focus,\n";
    $css .= ".page-anonymous #login_name:-webkit-autofill:active,\n";
    $css .= ".page-anonymous #login_name:autofill,\n";
    $css .= ".page-anonymous #login_name:-internal-autofill-selected,\n";
    $css .= ".page-anonymous #login_name:-moz-autofill,\n";
    $css .= ".page-anonymous #login_name:-moz-autofill:focus,\n";
    $css .= "body.welcome-anonymous #login_name:-webkit-autofill,\n";
    $css .= "body.welcome-anonymous #login_name:-webkit-autofill:hover,\n";
    $css .= "body.welcome-anonymous #login_name:-webkit-autofill:focus,\n";
    $css .= "body.welcome-anonymous #login_name:-webkit-autofill:active,\n";
    $css .= "body.welcome-anonymous #login_name:autofill,\n";
    $css .= "body.welcome-anonymous #login_name:-internal-autofill-selected,\n";
    $css .= "body.welcome-anonymous #login_name:-moz-autofill,\n";
    $css .= "body.welcome-anonymous #login_name:-moz-autofill:focus {\n";
    $css .= "  background: var(--brandkit-login-icon-user), linear-gradient(var(--brandkit-login-field-bg-autofill), var(--brandkit-login-field-bg-autofill)) !important;\n";
        $css .= "  background-repeat: no-repeat, no-repeat !important;\n";
        $css .= "  background-position: var(--brandkit-login-icon-position), 0 0 !important;\n";
        $css .= "  background-size: 18px 18px, 100% 100% !important;\n";
    $css .= "  -webkit-box-shadow: 0 0 0px 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
    $css .= "  box-shadow: 0 0 0px 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
        $css .= "  -webkit-text-fill-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  caret-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  -webkit-background-clip: padding-box !important;\n";
        $css .= "  background-clip: padding-box !important;\n";
        $css .= "}\n";
    $css .= ".page-anonymous #login_password:-webkit-autofill,\n";
    $css .= ".page-anonymous #login_password:-webkit-autofill:hover,\n";
    $css .= ".page-anonymous #login_password:-webkit-autofill:focus,\n";
    $css .= ".page-anonymous #login_password:-webkit-autofill:active,\n";
    $css .= ".page-anonymous #login_password:autofill,\n";
    $css .= ".page-anonymous #login_password:-internal-autofill-selected,\n";
    $css .= ".page-anonymous #login_password:-moz-autofill,\n";
    $css .= ".page-anonymous #login_password:-moz-autofill:focus,\n";
    $css .= "body.welcome-anonymous #login_password:-webkit-autofill,\n";
    $css .= "body.welcome-anonymous #login_password:-webkit-autofill:hover,\n";
    $css .= "body.welcome-anonymous #login_password:-webkit-autofill:focus,\n";
    $css .= "body.welcome-anonymous #login_password:-webkit-autofill:active,\n";
    $css .= "body.welcome-anonymous #login_password:autofill,\n";
    $css .= "body.welcome-anonymous #login_password:-internal-autofill-selected,\n";
    $css .= "body.welcome-anonymous #login_password:-moz-autofill,\n";
    $css .= "body.welcome-anonymous #login_password:-moz-autofill:focus {\n";
    $css .= "  background: var(--brandkit-login-icon-password), linear-gradient(var(--brandkit-login-field-bg-autofill), var(--brandkit-login-field-bg-autofill)) !important;\n";
        $css .= "  background-repeat: no-repeat, no-repeat !important;\n";
        $css .= "  background-position: var(--brandkit-login-icon-position), 0 0 !important;\n";
        $css .= "  background-size: 18px 18px, 100% 100% !important;\n";
    $css .= "  -webkit-box-shadow: 0 0 0px 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
    $css .= "  box-shadow: 0 0 0px 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
        $css .= "  -webkit-text-fill-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
        $css .= "  caret-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
    $css .= "  -webkit-background-clip: padding-box !important;\n";
    $css .= "  background-clip: padding-box !important;\n";
    $css .= "}\n";
    $css .= ".page-anonymous .form-control:autofill,\n";
    $css .= ".page-anonymous .form-control:autofill:hover,\n";
    $css .= ".page-anonymous .form-control:autofill:focus,\n";
    $css .= ".page-anonymous .form-control:autofill:active,\n";
    $css .= "body.welcome-anonymous .form-control:autofill,\n";
    $css .= "body.welcome-anonymous .form-control:autofill:hover,\n";
    $css .= "body.welcome-anonymous .form-control:autofill:focus,\n";
    $css .= "body.welcome-anonymous .form-control:autofill:active {\n";
    $css .= "  background-color: var(--brandkit-login-field-bg-autofill) !important;\n";
    $css .= "  border: var(--brandkit-login-field-border-width) solid var(--brandkit-login-field-border) !important;\n";
    $css .= "  border-radius: var(--brandkit-login-field-radius) !important;\n";
    $css .= "  -webkit-box-shadow: 0 0 0 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
    $css .= "  box-shadow: 0 0 0 1000px var(--brandkit-login-field-bg-autofill) inset, var(--brandkit-login-field-shadow-current, var(--brandkit-login-field-shadow)) !important;\n";
    $css .= "  -webkit-text-fill-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
    $css .= "  color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
    $css .= "  caret-color: var(--brandkit-login-field-text-autofill, var(--tblr-body-color)) !important;\n";
    $css .= "  -webkit-background-clip: padding-box !important;\n";
    $css .= "  background-clip: padding-box !important;\n";
    $css .= "  filter: none !important;\n";
    $css .= "  appearance: none !important;\n";
    $css .= "  -moz-appearance: none !important;\n";
    $css .= "  -webkit-appearance: none !important;\n";
    $css .= "}\n";

    // GLPI 11 moved the "Forgotten password?" link under the submit button,
    // inside a wider .form-footer: keep it under the button, centered.
    $css .= "body.welcome-anonymous .form-footer .forgot_password {\n";
    $css .= "  width: min(100%, var(--brandkit-login-field-width));\n";
    $css .= "  margin-left: auto !important;\n";
    $css .= "  margin-right: auto !important;\n";
    $css .= "  text-align: center !important;\n";
    $css .= "}\n";
    $css .= brandkit_build_login_footer_css();
    $css .= brandkit_build_login_extras_css();
    $css .= brandkit_build_sso_button_vars_css($settings);
    $css .= brandkit_build_sso_button_content_css($settings);
    $css .= brandkit_build_secondary_pages_css();

    return $css;
}

/**
 * GLPI's "Copyright ... Teclib' and contributors" footer of the login page:
 * kept under the login area and pushed to the bottom of the screen.
 *
 * Two cases, because samlSSO 1.3.x prints one "</div>" too many in the login
 * page: that closes GLPI's containers early and moves the footer out of
 * .container-tight into .flex-fill, where it showed up in the middle of the
 * screen instead of under the login area.
 *
 * Only layout in normal flow (auto margins), no fixed positioning:
 * .container-tight is transformed, which would anchor a fixed footer to it.
 */
function brandkit_build_login_footer_css(): string
{
    $page = 'body.welcome-anonymous:has(#login_name)';
    $footer = '.text-center.text-muted.mt-3';

    return <<<CSS
/* Brandkit: GLPI copyright footer under the login area, at the bottom */
{$page} .flex-fill > {$footer} {
  width: min(100%, var(--brandkit-login-field-width));
  margin-left: auto;
  margin-right: auto;
}
@media screen and (min-width: 768px) {
  {$page} .flex-fill > {$footer} {
    margin-left: var(--brandkit-login-container-left);
    margin-right: var(--brandkit-login-container-right);
    transform: translateX(var(--brandkit-login-container-translate));
  }
}
@media screen and (max-width: 767px) {
  /* The copyright width follows the split panel, which is a thin strip on phones. */
  {$page} {$footer} .copyright {
    width: 100% !important;
  }
}
@media screen and (min-width: 768px) and (min-height: 600px) {
  {$page} .flex-fill:has(> {$footer}) > .container-tight,
  {$page} .flex-fill > {$footer} {
    margin-top: auto !important;
  }
  {$page} .container-tight:has(> {$footer} > .copyright) {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 2rem - var(--brandkit-login-logo-offset, 0px));
  }
  {$page} .container-tight:has(> {$footer} > .copyright) > .text-center:first-child,
  {$page} .container-tight > {$footer} {
    margin-top: auto !important;
  }
}

CSS;
}

/**
 * Error page button text and GLPI's public FAQ button.
 */
function brandkit_build_login_extras_css(): string
{
    $page = 'body.welcome-anonymous:has(#login_name)';
    // Not "form > ...": samlSSO's extra </div> moves this block out of the form.
    $faq = $page . ' .main-content-card .text-center.border-top:has(> a[href*="helpdesk.faq.php"])';

    return <<<CSS
/* Brandkit: buttons inside error messages ("Log in again"): the alert text
   color was also painting the button's label and icon, which disappeared
   on dark buttons. They follow the button text color instead. */
body.welcome-anonymous .alert .btn *,
body.brandkit-error-page #page .alert .btn *,
body.horizontal-layout:not(.central):not(.helpdesk) #page .alert .btn * {
  color: inherit !important;
}
/* Brandkit: GLPI public FAQ button on the login page, under the login area
   and in the login button style (outlined). */
{$faq} {
  width: min(100%, var(--brandkit-login-field-width));
  margin: 1.5rem auto 0 !important;
  padding-top: 0.25rem;
  text-align: center !important;
  border-top-color: color-mix(in srgb, var(--brandkit-login-text-color) 25%, transparent) !important;
}
{$faq} > a.btn {
  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  width: 100%;
  margin-top: 1rem !important;
  padding: 0.6rem 1.25rem !important;
  background: transparent !important;
  border: 1px solid var(--brandkit-login-button-border) !important;
  border-radius: var(--brandkit-login-button-radius) !important;
  color: var(--brandkit-login-text-color) !important;
  font-size: calc(var(--brandkit-login-button-text-size) * 0.8);
  font-weight: 600;
  box-shadow: none !important;
  transition: background-color 0.2s ease, color 0.2s ease;
}
{$faq} > a.btn:hover {
  background: var(--brandkit-login-button-bg) !important;
  color: var(--brandkit-login-button-text) !important;
}
{$faq} > a.btn * {
  color: inherit !important;
}

CSS;
}

/**
 * Own colors for the SSO button (SSO mode), used by public/css/brandkit-sso.css.
 * Without them the SSO button takes the login button colors.
 */
function brandkit_build_sso_button_vars_css(array $settings): string
{
    if (($settings['login_sso_button_colors'] ?? 'login') !== 'custom') {
        return '';
    }

    $bg = brandkit_normalize_hex_color($settings['login_sso_button_bg'] ?? '', '#ffffff');
    $text = brandkit_normalize_hex_color($settings['login_sso_button_text'] ?? '', brandkit_get_contrast_color($bg));

    return ":root {\n"
        . "  --brandkit-sso-button-bg: {$bg};\n"
        . "  --brandkit-sso-button-border: {$bg};\n"
        . "  --brandkit-sso-button-text: {$text};\n"
        . "}\n";
}

/**
 * Text for a CSS `content` property: every character but letters, digits and
 * spaces becomes a CSS escape, so nothing in the text can end the string or
 * start a SCSS interpolation (the file is compiled by GLPI's SCSS compiler).
 */
function brandkit_css_string(string $text): string
{
    $out = '';
    foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
        if (preg_match('/^[A-Za-z0-9 ]$/', $char) === 1) {
            $out .= $char;
        } else {
            $out .= '\\' . dechex(mb_ord($char, 'UTF-8')) . ' ';
        }
    }

    return '"' . $out . '"';
}

/**
 * Colored Microsoft logo (four squares), as used on "Sign in with Microsoft" buttons.
 */
function brandkit_get_microsoft_logo_uri(): string
{
    return brandkit_svg_data_uri(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21">'
        . '<rect x="1" y="1" width="9" height="9" fill="#f25022"/>'
        . '<rect x="11" y="1" width="9" height="9" fill="#7fba00"/>'
        . '<rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>'
        . '<rect x="11" y="11" width="9" height="9" fill="#ffb900"/>'
        . '</svg>'
    );
}

/**
 * Text and icon of the samlSSO button.
 *
 * samlSSO cuts the identity provider name to 12 characters for its small
 * tiles ("Entrar com Microsoft" became "Entrar com M"). The text set here
 * replaces it, and only when there is a single SSO button. The icon can be
 * replaced by the colored Microsoft logo or hidden.
 */
function brandkit_build_sso_button_content_css(array $settings): string
{
    $scope = 'body.welcome-anonymous .plugin_glpisaml_login #saml_buttons';
    $css = '';

    $label = trim((string) ($settings['login_sso_button_label'] ?? ''));
    if ($label !== '') {
        $label = function_exists('mb_substr') ? mb_substr($label, 0, 60) : substr($label, 0, 60);
        $single = $scope . ':has(> [class*="col-"]:only-child) button';
        // Only the span whose <b> was hidden gets the new text: while it
        // redirects, samlSSO swaps the button content for a spinning
        // <span class="spinner-border">, which must stay empty.
        $css .= "{$single} span > b {\n  display: none !important;\n}\n"
            . "{$single} span:has(> b)::after {\n  content: " . brandkit_css_string($label) . ";\n  font-weight: 600;\n}\n";
    }

    $icon = $settings['login_sso_button_icon'] ?? 'provider';
    if ($icon === 'microsoft') {
        $css .= "{$scope} button i {\n"
            . "  display: inline-block !important;\n"
            . "  width: 1em !important;\n"
            . "  height: 1em !important;\n"
            . "  background: " . brandkit_get_microsoft_logo_uri() . " center / contain no-repeat !important;\n"
            . "  color: transparent !important;\n"
            . "  -webkit-mask: none !important;\n"
            . "  mask: none !important;\n"
            . "}\n"
            . "{$scope} button i::before,\n{$scope} button i::after {\n  content: none !important;\n}\n";
    } elseif ($icon === 'none') {
        $css .= "{$scope} button i {\n  display: none !important;\n}\n";
    }

    return $css;
}

/**
 * Other anonymous pages that GLPI renders in the same card as the login form:
 * two-factor code prompt, two-factor setup, backup codes, password reset.
 *
 * The login rules above size every field and button of the card for the
 * login form (fixed field width, icons, full-width submit button, 650px
 * container). On these pages that broke the layout (issue #4: 2FA code
 * inputs crushed, buttons stacked and cut off). They keep the branding
 * (wallpaper, panel, logo, colors) but get back GLPI's own layout.
 *
 * Pages are told apart with :has(#login_name): only the login form has it.
 */
function brandkit_build_secondary_pages_css(): string
{
    $page = 'body.welcome-anonymous:not(:has(#login_name))';
    $card = $page . ' .main-content-card';

    return <<<CSS
/* Brandkit: secondary anonymous pages (2FA, backup codes, password reset) */
/* Tall pages (2FA setup) must scroll from the top instead of being cut off. */
{$page} {
  align-items: flex-start !important;
}
{$page} .flex-fill {
  min-height: 100vh;
  justify-content: center;
}
{$page} .container-tight {
  min-width: 0 !important;
  width: min(100%, 34rem, var(--brandkit-login-container-max-effective)) !important;
  max-width: min(100%, 34rem, var(--brandkit-login-container-max-effective)) !important;
}
@media screen and (max-width: 767px) {
  {$page} .container-tight {
    width: min(100%, 34rem) !important;
    max-width: min(100%, 34rem) !important;
  }
}
{$card} {
  width: 100% !important;
  height: auto !important;
  max-height: none !important;
}
{$card} .card-header {
  display: block !important;
  padding: 0 0 0.75rem !important;
  text-align: center;
}
{$card} .card-header:empty {
  display: none !important;
}
{$card} .card-title {
  color: inherit !important;
  font-size: 1.1em;
  font-weight: 600;
  margin: 0;
}
{$card} .card-body {
  overflow: visible !important;
}
{$card} form[data-code-type],
{$card} form.m-n5 {
  margin: 0 !important;
}
{$card} .form-control:not([type="checkbox"]):not([type="radio"]) {
  width: 100% !important;
  max-width: none !important;
  height: auto !important;
  min-height: 2.5rem;
  margin-left: 0 !important;
  margin-right: 0 !important;
  padding: 0.5rem 0.75rem !important;
  background-image: none !important;
  text-align: start;
}
{$card} input.form-control[name="totp_code[]"]:not([type="checkbox"]):not([type="radio"]) {
  flex: 0 0 auto;
  width: 2.6em !important;
  height: 2.6em !important;
  min-height: 0;
  padding: 0 !important;
  font-size: 1.4em !important;
  text-align: center !important;
}
{$card} input.form-control[name="totp_code[]"]:not([type="checkbox"]):not([type="radio"]) + input[name="totp_code[]"] {
  margin-left: 0.4rem !important;
}
{$card} .btn-group .form-control {
  flex: 1 1 auto;
  width: auto !important;
}
{$card} form .btn {
  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  width: auto !important;
  max-width: none !important;
  margin-left: 0 !important;
  margin-right: 0 !important;
  padding: 0.5rem 1rem !important;
  font-size: 0.95em !important;
  vertical-align: middle;
}
{$card} .border-top > .ms-auto,
{$card} .card-footer > .ms-auto {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  align-items: center;
  gap: 0.4rem;
}
{$card} input[name="backup_code"]:not(.d-none) {
  flex: 1 0 100%;
}
{$card} .btn-link,
{$card} .btn-outline-secondary,
{$card} .btn-icon {
  background: transparent !important;
  color: var(--brandkit-login-text-color) !important;
  box-shadow: none !important;
}
{$card} .btn-outline-secondary,
{$card} .btn-icon {
  border: 1px solid currentColor !important;
}
{$card} .btn-link {
  border-color: transparent !important;
  text-decoration: underline;
}
{$card} .form-footer .btn {
  width: 100% !important;
}
{$card} .card {
  width: auto !important;
  margin: 0 !important;
  padding: 0 !important;
  background: transparent !important;
  border: 0 !important;
  box-shadow: none !important;
}
{$card} .card .card-body {
  padding: 0.5rem 0 !important;
}
{$card} .card-footer {
  gap: 0.5rem;
  align-items: center;
  padding: 0.75rem 0 0 !important;
  background: transparent !important;
  border-top: 1px solid rgba(127, 127, 127, 0.35) !important;
  color: inherit !important;
}
{$card} img[alt="QR Code"] {
  max-width: 100%;
  height: auto;
  padding: 8px;
  border-radius: 8px;
  background: #ffffff;
}
{$card} .border-top {
  border-color: rgba(127, 127, 127, 0.35) !important;
}

CSS;
}

/**
 * Absolute path of the generated login stylesheet (served through GLPI's
 * front/css.php, which accepts files from GLPI_PLUGIN_DOC_DIR).
 */
function brandkit_get_login_css_path(bool $create = false): ?string
{
    $dir = brandkit_get_plugin_storage_dir($create);
    return $dir !== null ? $dir . DIRECTORY_SEPARATOR . 'brandkit-login.scss' : null;
}

/**
 * URL GLPI's css.php needs to serve the generated login stylesheet, or null
 * when it has not been generated yet.
 */
function brandkit_get_login_css_url(): ?string
{
    $path = brandkit_get_login_css_path();
    if ($path === null || !is_file($path)) {
        return null;
    }

    $glpiRoot = realpath(GLPI_ROOT);
    $realPath = realpath($path);
    if ($glpiRoot === false || $realPath === false) {
        return null;
    }

    // css.php resolves "file" relative to GLPI_ROOT and appends ".scss".
    $relative = brandkit_relative_path($glpiRoot, dirname($realPath)) . '/brandkit-login';

    return brandkit_get_glpi_root_doc() . '/front/css.php?file='
        . str_replace('%2F', '/', rawurlencode(ltrim($relative, '/')))
        . '&bk=' . filemtime($realPath);
}

/**
 * Regenerate the login stylesheet. The file is only rewritten when its
 * content changes, so its mtime (used as cache buster) stays stable.
 */
function brandkit_write_login_css(?array $settings = null): bool
{
    $path = brandkit_get_login_css_path(true);
    if ($path === null) {
        return false;
    }

    $css = brandkit_build_login_css($settings ?? brandkit_get_login_settings());

    return brandkit_write_file_if_changed($path, $css);
}

/**
 * Name of the query parameter that shows the local login form while SSO mode
 * is on. Same convention as the samlSSO plugin (?bypass=1), so a single URL
 * skips both plugins' SSO-only behaviour.
 */
const BRANDKIT_SSO_BYPASS_PARAM = 'bypass';

function brandkit_is_sso_bypass_requested(): bool
{
    return isset($_GET[BRANDKIT_SSO_BYPASS_PARAM]) && (string) $_GET[BRANDKIT_SSO_BYPASS_PARAM] === '1';
}

/**
 * Login page URL that shows the local (user/password) form.
 * noAUTO=1 also stops GLPI/samlSSO automatic SSO redirections.
 */
function brandkit_get_sso_bypass_url(bool $absolute = false): string
{
    $base = brandkit_get_glpi_root_doc();
    if ($absolute) {
        $urlBase = $GLOBALS['CFG_GLPI']['url_base'] ?? '';
        if (is_string($urlBase) && $urlBase !== '') {
            $base = rtrim($urlBase, '/');
        }
    }

    return $base . '/?' . BRANDKIT_SSO_BYPASS_PARAM . '=1&noAUTO=1';
}

/**
 * @return array{enabled: bool, local_link: bool, title: string}
 */
function brandkit_get_sso_settings(): array
{
    $defaults = brandkit_get_login_defaults();
    $config = Config::getConfigurationValues('plugin:brandkit', ['login_sso_mode', 'login_sso_local_link', 'login_sso_title']);

    return [
        'enabled' => ($config['login_sso_mode'] ?? $defaults['login_sso_mode']) === '1',
        'local_link' => ($config['login_sso_local_link'] ?? $defaults['login_sso_local_link']) === '1',
        'title' => trim((string) ($config['login_sso_title'] ?? '')),
    ];
}

/**
 * HTML printed in the login page's plugin area (display_login hook):
 * the optional title above the SSO buttons and the local access link.
 */
function brandkit_render_sso_login_extras(): string
{
    $sso = brandkit_get_sso_settings();
    if (!$sso['enabled']) {
        return '';
    }

    if (brandkit_is_sso_bypass_requested()) {
        return '<a class="brandkit-sso-back" href="' . htmlescape(brandkit_get_glpi_root_doc() . '/') . '">'
            . htmlescape(__('Back to single sign-on', 'brandkit')) . '</a>';
    }

    $html = '';
    if ($sso['title'] !== '') {
        $html .= '<div class="brandkit-sso-title">' . htmlescape($sso['title']) . '</div>';
    }
    if ($sso['local_link']) {
        $html .= '<a class="brandkit-local-login" href="' . htmlescape(brandkit_get_sso_bypass_url()) . '">'
            . htmlescape(__('Sign in with a local account', 'brandkit')) . '</a>';
    }

    return $html;
}
