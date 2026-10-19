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
 * HTML fragments of the configuration page that repeat (logo cards).
 *
 * @package   Brandkit Plugin
 * @license   AGPL-3.0-or-later
 * ------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

require_once __DIR__ . '/login.css.php';

function brandkit_render_info_badge(?string $info): void
{
    if ($info === null || $info === '') {
        return;
    }
    $info = htmlescape($info);
    echo <<<HTML
<span class="brandkit-info" title="{$info}" data-info="{$info}" aria-label="{$info}" role="img" tabindex="0">i</span>
HTML;
}

/**
 * URL showing the current version of a logo slot in the admin preview.
 */
function brandkit_get_logo_preview_url(string $file): string
{
    $stored = brandkit_get_stored_logo_path($file);
    if ($stored !== null) {
        return brandkit_get_plugin_web_dir() . '/front/logo_preview.php?name=' . rawurlencode($file)
            . '&v=' . filemtime($stored);
    }

    $native = brandkit_get_native_asset_path($file);
    if ($native !== null && is_file($native)) {
        $path = $file === 'favicon.ico' ? '/favicon.ico' : '/logos/' . rawurlencode($file);
        return brandkit_get_pics_web_base() . $path . '?v=' . filemtime($native);
    }

    return '';
}

/**
 * One logo slot: upload / remove buttons and a small in-context preview.
 */
function brandkit_render_logo_card(string $file, string $label, ?string $info = null): void
{
    $kind = brandkit_get_logo_target_kinds()[$file] ?? 'header';
    $isFavicon = $kind === 'favicon';
    $isCompact = strpos($file, 'logo-G-100') === 0;
    $hasCustom = brandkit_get_stored_logo_path($file) !== null;
    $previewScale = $kind === 'header' ? ($isCompact ? 'reduced' : 'header') : null;
    $uploadId = 'brandkit_logo_' . md5($file);
    $accept = $isFavicon ? '.ico,image/x-icon,image/vnd.microsoft.icon' : 'image/png,image/jpeg,image/webp';
    $logoUrl = brandkit_get_logo_preview_url($file);
    $csrf = htmlescape(brandkit_get_csrf_token());
    $e = static fn($value): string => htmlescape((string) $value);
    ?>
    <div class="brandkit-card brandkit-logo-preview brandkit-logo-card">
        <h4><?php echo $e($label); ?> <?php brandkit_render_info_badge($info); ?></h4>
        <div class="brandkit-logo-actions">
            <form method="post" enctype="multipart/form-data" action="logo_upload.php">
                <input type="hidden" name="brandkit_action" value="upload_logo">
                <input type="hidden" name="logo_target" value="<?php echo $e($file); ?>">
                <input type="hidden" name="_glpi_csrf_token" value="<?php echo $csrf; ?>">
                <input class="brandkit-logo-input" type="file" id="<?php echo $e($uploadId); ?>" name="brandkit_logo" accept="<?php echo $e($accept); ?>">
                <label class="brandkit-logo-action brandkit-logo-upload" for="<?php echo $e($uploadId); ?>" aria-label="<?php echo $e(__('Upload logo', 'brandkit')); ?>">&#x2B06;</label>
            </form>
            <?php if ($hasCustom) : ?>
                <form method="post" action="logo_upload.php">
                    <input type="hidden" name="brandkit_action" value="clear_logo">
                    <input type="hidden" name="logo_target" value="<?php echo $e($file); ?>">
                    <input type="hidden" name="_glpi_csrf_token" value="<?php echo $csrf; ?>">
                    <button type="submit" class="brandkit-logo-action brandkit-logo-remove" aria-label="<?php echo $e(__('Remove logo', 'brandkit')); ?>">×</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="brandkit-logo-stage">
            <?php if ($isFavicon) : ?>
                <div class="brandkit-logo-login-sample" style="gap: 10px;">
                    <?php if ($hasCustom) : ?>
                        <div style="flex: 1; height: 36px; border-radius: 10px; background: rgba(15, 23, 42, 0.12); display: flex; align-items: center; justify-content: space-between; padding: 0 10px; font-size: 0.72rem; color: #0f172a;">
                            <span style="display: inline-flex; align-items: center; gap: 8px;">
                                <span style="width: 14px; height: 14px; border-radius: 4px; background: #22c55e; display: inline-block;"></span>
                                <?php echo $e(__('Favicon active', 'brandkit')); ?>
                            </span>
                            <span style="opacity: 0.7;">● ● ●</span>
                        </div>
                    <?php else : ?>
                        <div style="flex: 1; height: 36px; border-radius: 10px; border: 1px dashed rgba(148, 163, 184, 0.7); background: rgba(248, 250, 252, 0.9); display: flex; align-items: center; justify-content: space-between; padding: 0 10px; font-size: 0.72rem; color: #64748b;">
                            <span><?php echo $e(__('No favicon yet', 'brandkit')); ?></span>
                            <span style="opacity: 0.7;">○ ○ ○</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="<?php echo $kind === 'login' ? 'brandkit-logo-login-sample' : 'brandkit-logo-header-sample'; ?>">
                    <div class="logo-sample">
                        <?php if ($logoUrl !== '') : ?>
                            <img
                                src="<?php echo $e($logoUrl); ?>"
                                alt="<?php echo $e($label); ?>"
                                <?php echo $previewScale ? 'data-brandkit-logo-scale="' . $e($previewScale) . '"' : ''; ?>
                            >
                        <?php endif; ?>
                        <?php if ($previewScale) : ?>
                            <span class="brandkit-logo-preview-value" data-brandkit-logo-value="<?php echo $e($previewScale); ?>"></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($kind === 'login') : ?>
                        <div class="brandkit-logo-login-field"></div>
                        <div class="brandkit-logo-login-field"></div>
                    <?php else : ?>
                        <div class="logo-sample" style="width: <?php echo $isCompact ? '40px' : '70px'; ?>;">
                            <span style="width: 100%; height: 10px; border-radius: 999px; background: rgba(148, 163, 184, 0.7); display: block;"></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if (!$hasCustom) : ?>
                <div class="brandkit-placeholder">
                    <?php echo $e($isFavicon ? __('No custom favicon uploaded yet.', 'brandkit') : __('No custom logo uploaded yet.', 'brandkit')); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * A titled group of logo cards.
 *
 * @param array<string, string> $files    file name => label
 * @param array<string, string> $infoMap  file name => help text
 */
function brandkit_render_logo_section(string $title, ?string $info, array $files, array $infoMap = []): void
{
    ?>
    <div class="brandkit-section">
        <h4><?php echo htmlescape($title); ?> <?php brandkit_render_info_badge($info); ?></h4>
        <div class="brandkit-grid">
            <?php foreach ($files as $file => $label) : ?>
                <?php brandkit_render_logo_card($file, $label, $infoMap[$file] ?? null); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
