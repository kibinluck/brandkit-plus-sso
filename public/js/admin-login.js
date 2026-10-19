/*
 * Brandkit Plugin (Community Edition) - AGPL-3.0-or-later
 * Login page step of the configuration page: live preview and dependent fields.
 */

document.addEventListener('DOMContentLoaded', function () {
    var layoutSelect = document.getElementById('brandkit_login_layout');
    var panelTransparent = document.getElementById('brandkit_login_transparent');
    var logoPosition = document.getElementById('brandkit_login_logo_position');
    var colorModeSelect = document.getElementById('brandkit_login_button_color_mode');
    var colorPicker = document.getElementById('brandkit_login_button_color_picker');
    var fieldStyleSelect = document.getElementById('brandkit_login_field_style');
    var customSection = document.getElementById('brandkit_button_color_custom');
    var hoverEffectSelect = document.getElementById('brandkit_login_button_hover_effect');
    var hoverOptions = document.getElementById('brandkit_button_hover_options');
    var hoverFillModeInput = document.getElementById('brandkit_login_button_hover_fill_mode');
    var hoverFillColorInput = document.getElementById('brandkit_login_button_hover_fill_color');
    var hoverTextColorInput = document.getElementById('brandkit_login_button_hover_text_color');
    var panelSplitSection = document.getElementById('brandkit_panel_split_section');
    var panelSplitPreview = document.getElementById('brandkit_panel_split_preview');
    var splitControlsWrap = document.getElementById('brandkit_split_controls');
    var iconColor = document.getElementById('brandkit_login_icon_color');
    var splitControls = document.querySelectorAll('.brandkit-split-controls input, .brandkit-split-controls select');
    var panelSizeFields = document.querySelectorAll('.brandkit-panel-size');
    var panelStyleFields = document.querySelectorAll('.brandkit-panel-style-controls');
    var logoOffsetFields = document.querySelectorAll('.brandkit-logo-offset-field');
    var fieldFocusFields = document.querySelectorAll('.brandkit-field-focus');
    var wallpaperInput = document.getElementById('brandkit_login_wallpaper');
    var wallpaperRemove = document.getElementById('brandkit_login_wallpaper_remove');
    var wallpaperDelete = document.getElementById('brandkit_login_wallpaper_delete');
    var wallpaperPreview = document.querySelector('[data-brandkit-wallpaper-preview]');
    var wallpaperSourceInput = document.getElementById('brandkit_wallpaper_source');
    var wallpaperPresetInput = document.getElementById('brandkit_wallpaper_preset');
    var wallpaperPresetWrap = document.getElementById('brandkit_wallpaper_preset_wrap');
    var wallpaperUploadAction = document.querySelector('[data-brandkit-wallpaper-upload]');
    var wallpaperSizeInput = document.getElementById('brandkit_wallpaper_size');
    var wallpaperPositionInput = document.getElementById('brandkit_wallpaper_position');
    var wallpaperAttachmentInput = document.getElementById('brandkit_wallpaper_attachment');
    var wallpaperOverlayColorInput = document.getElementById('brandkit_wallpaper_overlay_color');
    var wallpaperOverlayOpacityInput = document.getElementById('brandkit_wallpaper_overlay_opacity');
    var form = document.getElementById('brandkit-login-settings-form')
        || document.querySelector('form[action*="step=2"]')
        || document.querySelector('form');

    var isLightColor = function (hex) {
        if (!hex) {
            return false;
        }
        var value = hex.replace('#', '');
        if (value.length === 3) {
            value = value[0] + value[0] + value[1] + value[1] + value[2] + value[2];
        }
        var r = parseInt(value.substring(0, 2), 16) || 0;
        var g = parseInt(value.substring(2, 4), 16) || 0;
        var b = parseInt(value.substring(4, 6), 16) || 0;
        var luma = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
        return luma > 0.75;
    };

    var normalizeHex = function (value) {
        if (!value) {
            return '';
        }
        value = value.trim().toUpperCase();
        if (value[0] !== '#') {
            value = '#' + value;
        }
        if (/^#[0-9A-F]{3}$/.test(value)) {
            value = '#' + value[1] + value[1] + value[2] + value[2] + value[3] + value[3];
        }
        if (!/^#[0-9A-F]{6}$/.test(value)) {
            return '';
        }
        return value;
    };

    var toggleColorMode = function () {
        var isCustom = colorModeSelect && colorModeSelect.value === 'custom';
        if (customSection) {
            customSection.classList.toggle('brandkit-toggle-hidden', !isCustom);
        }
    };

    var toggleHoverOptions = function () {
        var isFill = hoverEffectSelect && hoverEffectSelect.value === 'fill';
        if (hoverOptions) {
            hoverOptions.classList.toggle('brandkit-toggle-hidden', !isFill);
            hoverOptions.querySelectorAll('input').forEach(function (input) {
                if (isFill) {
                    input.removeAttribute('disabled');
                } else {
                    input.setAttribute('disabled', 'disabled');
                }
            });
        }
    };

    var toggleSplitControls = function () {
        var isClassic = layoutSelect && layoutSelect.value === 'classic';
        if (splitControlsWrap) {
            splitControlsWrap.classList.toggle('brandkit-toggle-hidden', isClassic);
        }
        splitControls.forEach(function (control) {
            control.disabled = isClassic;
        });
    };

    var togglePanelSizeControls = function () {
        var isTransparent = panelTransparent && panelTransparent.value === '1';
        panelSizeFields.forEach(function (field) {
            field.classList.toggle('brandkit-toggle-hidden', isTransparent);
            var input = field.querySelector('input');
            if (input) {
                if (isTransparent) {
                    input.setAttribute('disabled', 'disabled');
                } else {
                    input.removeAttribute('disabled');
                }
            }
        });
        panelStyleFields.forEach(function (field) {
            field.classList.toggle('brandkit-toggle-hidden', isTransparent);
            field.querySelectorAll('input, select').forEach(function (input) {
                if (isTransparent) {
                    input.setAttribute('disabled', 'disabled');
                } else {
                    input.removeAttribute('disabled');
                }
            });
        });
        if (logoPosition) {
            var logoPositionField = logoPosition.closest('div');
            if (isTransparent) {
                logoPosition.setAttribute('disabled', 'disabled');
                if (logoPositionField) {
                    logoPositionField.classList.add('brandkit-toggle-hidden');
                }
            } else {
                logoPosition.removeAttribute('disabled');
                if (logoPositionField) {
                    logoPositionField.classList.remove('brandkit-toggle-hidden');
                }
            }
        }

        var isClassic = layoutSelect && layoutSelect.value === 'classic';
        var hidePanelSplit = isTransparent && isClassic;
        if (panelSplitSection) {
            panelSplitSection.classList.toggle('brandkit-toggle-hidden', hidePanelSplit);
        }
        if (panelSplitPreview) {
            panelSplitPreview.classList.toggle('brandkit-toggle-hidden', hidePanelSplit);
        }
    };

    var toggleLogoOffsetControls = function () {
        var isInside = logoPosition && logoPosition.value === 'inside';
        logoOffsetFields.forEach(function (field) {
            field.classList.toggle('brandkit-toggle-hidden', isInside);
            var input = field.querySelector('input');
            if (input) {
                if (isInside) {
                    input.setAttribute('disabled', 'disabled');
                } else {
                    input.removeAttribute('disabled');
                }
            }
        });
    };

    var toggleFieldFocusControls = function () {
        var isNeon = fieldStyleSelect && fieldStyleSelect.value === 'neon';
        fieldFocusFields.forEach(function (field) {
            field.classList.toggle('brandkit-toggle-hidden', !isNeon);
        });
    };

    var hexToRgba = function (hex, opacity) {
        var value = normalizeHex(hex);
        if (!value) {
            return '';
        }
        var r = parseInt(value.substring(1, 3), 16) || 0;
        var g = parseInt(value.substring(3, 5), 16) || 0;
        var b = parseInt(value.substring(5, 7), 16) || 0;
        var alpha = typeof opacity === 'number' ? opacity : 1;
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    };

    var scaleRange = function (value, min, max, outMin, outMax) {
        if (max === min) {
            return outMin;
        }
        var clamped = Math.min(max, Math.max(min, value));
        var ratio = (clamped - min) / (max - min);
        return outMin + (outMax - outMin) * ratio;
    };

    var previewLayout = document.querySelector('[data-brandkit-preview="layout"]');
    var previewPanel = document.querySelector('[data-brandkit-preview="panel"]');
    var previewButtonStage = document.querySelector('[data-brandkit-preview="button"]');
    var previewButton = previewButtonStage ? previewButtonStage.querySelector('[data-brandkit-preview-button]') : null;
    var previewFields = document.querySelector('[data-brandkit-preview="fields"]');
    var previewIcons = document.querySelector('[data-brandkit-preview="icons"]');
    var isPreviewButtonHovered = false;

    var panelColorInput = document.getElementById('brandkit_login_color');
    var panelOpacityInput = document.getElementById('brandkit_login_opacity');
    var panelWidthInput = document.getElementById('brandkit_login_panel_width');
    var panelHeightInput = document.getElementById('brandkit_login_panel_height');
    var logoScaleInput = document.getElementById('brandkit_login_logo_scale');
    var splitWidthInput = document.getElementById('brandkit_split_width');
    var splitColorInput = document.getElementById('brandkit_split_color');
    var splitOpacityInput = document.getElementById('brandkit_split_opacity');
    var splitBlurInput = document.getElementById('brandkit_split_blur');
    var accentColorInput = document.getElementById('brandkit_login_accent_color');
    var loginTextColorInput = document.getElementById('brandkit_login_text_color');
    var buttonShapeInput = document.getElementById('brandkit_login_button_shape');
    var buttonStyleInput = document.getElementById('brandkit_login_button_custom_style');
    var buttonTextSizeInput = document.getElementById('brandkit_login_button_text_size');
    var fieldBgInput = document.getElementById('brandkit_login_field_bg');
    var fieldBorderInput = document.getElementById('brandkit_login_field_border');
    var fieldBorderWidthInput = document.getElementById('brandkit_login_field_border_width');
    var fieldTextInput = document.getElementById('brandkit_login_field_text');
    var fieldTextSizeInput = document.getElementById('brandkit_login_field_text_size');
    var fieldRadiusInput = document.getElementById('brandkit_login_field_radius');
    var fieldHeightInput = document.getElementById('brandkit_login_field_height');
    var fieldWidthInput = document.getElementById('brandkit_login_field_width');
    var fieldFocusInput = document.getElementById('brandkit_login_field_focus');
    var logoOffsetInput = document.getElementById('brandkit_login_logo_offset');
    var loginHideLoginTextInput = document.getElementById('brandkit_hide_login_text');
    var iconPositionInput = document.getElementById('brandkit_login_icon_position');
    var iconUserInputs = document.querySelectorAll('input[name="login_icon_user"]');

    var updateLayoutPreview = function (preview) {
        if (!preview) {
            return;
        }
        var layoutValue = layoutSelect ? layoutSelect.value : 'classic';
        preview.setAttribute('data-layout', layoutValue);
        preview.style.setProperty('--brandkit-preview-panel-align', layoutValue === 'split-left' ? 'flex-start' : layoutValue === 'split-right' ? 'flex-end' : 'center');

        var isLayoutPreview = preview.getAttribute('data-brandkit-preview') === 'layout';
        var splitWidth = splitWidthInput ? parseInt(splitWidthInput.value || '50', 10) : 50;
        preview.style.setProperty('--brandkit-preview-split-width', splitWidth + '%');

        var splitOpacity = splitOpacityInput ? parseInt(splitOpacityInput.value || '55', 10) / 100 : 0.55;
        var splitColor = isLayoutPreview ? '#cbd5e1' : (splitColorInput ? normalizeHex(splitColorInput.value) : '#cbd5e1');
        var splitRgba = splitColor ? hexToRgba(splitColor, splitOpacity) : '';
        if (splitRgba) {
            preview.style.setProperty('--brandkit-preview-split-color', splitRgba);
        }
        if (!isLayoutPreview) {
            preview.style.setProperty('--brandkit-preview-split-blur', splitBlurInput ? parseInt(splitBlurInput.value || '10', 10) + 'px' : '10px');
        }

        var panelOpacity = panelOpacityInput ? parseInt(panelOpacityInput.value || '65', 10) / 100 : 0.65;
        if (isLayoutPreview && panelTransparent && panelTransparent.value === '0') {
            panelOpacity = 0.9;
        }
        var panelHex = panelColorInput ? normalizeHex(panelColorInput.value) : '#111827';
        if (isLayoutPreview && panelTransparent && panelTransparent.value === '0') {
            panelHex = '#e2e8f0';
        }
        var panelRgba = panelHex ? hexToRgba(panelHex, panelOpacity) : '';
        if (panelRgba) {
            preview.style.setProperty('--brandkit-preview-panel-color', panelRgba);
        }
        if (panelTransparent && panelTransparent.value === '1') {
            preview.style.setProperty('--brandkit-preview-panel-opacity', '1');
            preview.style.setProperty('--brandkit-preview-panel-border', 'none');
            if (!isLayoutPreview) {
                preview.style.setProperty('--brandkit-preview-panel-color', 'transparent');
                preview.style.setProperty('--brandkit-preview-panel-shadow', 'none');
            }
        } else {
            preview.style.setProperty('--brandkit-preview-panel-opacity', '1');
            preview.style.setProperty('--brandkit-preview-panel-border', '1px solid rgba(148, 163, 184, 0.6)');
            if (!isLayoutPreview) {
                preview.style.setProperty('--brandkit-preview-panel-shadow', '0 10px 24px rgba(15, 23, 42, 0.25)');
            }
        }

        if (isLayoutPreview && (layoutValue === 'split-left' || layoutValue === 'split-right')) {
            preview.style.setProperty('--brandkit-preview-panel-color', 'transparent');
        }

        var accentColor = accentColorInput ? normalizeHex(accentColorInput.value) : '';
        if (accentColor) {
            preview.style.setProperty('--brandkit-preview-accent', accentColor);
        }
        var textColor = loginTextColorInput ? normalizeHex(loginTextColorInput.value) : '';
        if (textColor) {
            preview.style.setProperty('--brandkit-preview-text', textColor);
        }

        var panelWidth = panelWidthInput ? parseInt(panelWidthInput.value || '520', 10) : 520;
        var panelHeight = panelHeightInput ? parseInt(panelHeightInput.value || '560', 10) : 560;
        var widthPct = scaleRange(panelWidth, 280, 720, 55, 85);
        var heightPct = scaleRange(panelHeight, 360, 780, 55, 80);
        if (isLayoutPreview && (layoutValue === 'split-left' || layoutValue === 'split-right')) {
            widthPct = Math.min(widthPct, Math.max(45, splitWidth - 6));
        }
        preview.style.setProperty('--brandkit-preview-panel-width', widthPct + '%');
        preview.style.setProperty('--brandkit-preview-panel-height', heightPct + '%');

        if (!isLayoutPreview) {
            var showSplit = layoutValue === 'split-left' || layoutValue === 'split-right';
            preview.style.setProperty('--brandkit-preview-split-visibility', showSplit ? '1' : '0');
        }

        var logoScale = logoScaleInput ? parseInt(logoScaleInput.value || '200', 10) : 200;
        var logoWidth = scaleRange(logoScale, 80, 480, 45, 85);
        preview.style.setProperty('--brandkit-preview-logo-width', logoWidth + '%');

        var logoOffset = logoOffsetInput ? parseInt(logoOffsetInput.value || '0', 10) : 0;
        var logoOffsetPx = Math.min(4, Math.max(-12, logoOffset / 6));
        var logoOutside = logoPosition ? logoPosition.value === 'inside' : true;
        if (!logoOutside) {
            logoOffsetPx = 0;
        }
        preview.style.setProperty('--brandkit-preview-logo-offset', logoOffsetPx + 'px');

        if (logoPosition) {
            preview.setAttribute('data-logo-position', logoOutside ? 'outside' : 'inside');
        }
        preview.setAttribute('data-login-text-hidden', loginHideLoginTextInput && loginHideLoginTextInput.value === '1' ? '1' : '0');

        var previewButtonInline = preview.querySelector('[data-brandkit-preview-button]');
        if (previewButtonInline) {
            var buttonColor = accentColor || '#0ea5e9';
            previewButtonInline.style.background = buttonColor;
            previewButtonInline.style.color = isLightColor(buttonColor) ? '#0f172a' : '#ffffff';
        }

        preview.style.setProperty('--brandkit-preview-field-bg', 'rgba(255, 255, 255, 0.85)');
        preview.style.setProperty('--brandkit-preview-field-border', 'rgba(148, 163, 184, 0.7)');
        preview.style.setProperty('--brandkit-preview-field-border-width', '1px');
    };

    var applyButtonHoverState = function () {
        if (!previewButton) {
            return;
        }
        var effect = hoverEffectSelect ? hoverEffectSelect.value : 'press';
        var baseBg = previewButton.dataset.brandkitBaseBg || '';
        var baseBorder = previewButton.dataset.brandkitBaseBorder || '';
        var baseText = previewButton.dataset.brandkitBaseText || '';
        var baseShadow = previewButton.dataset.brandkitBaseShadow || '';

        if (!isPreviewButtonHovered || !effect) {
            previewButton.style.background = baseBg;
            previewButton.style.border = '2px solid ' + baseBorder;
            previewButton.style.color = baseText;
            previewButton.style.transform = '';
            if (baseShadow) {
                previewButton.style.boxShadow = baseShadow;
            }
            return;
        }

        if (effect === 'press') {
            previewButton.style.background = baseBg;
            previewButton.style.border = '2px solid ' + baseBorder;
            previewButton.style.color = baseText;
            previewButton.style.transform = 'translateY(2px)';
            previewButton.style.boxShadow = '0 2px 6px rgba(15, 23, 42, 0.35)';
            return;
        }

        if (effect === 'fill') {
            var hoverFill = previewButton.dataset.brandkitHoverFill || baseBorder;
            var hoverText = previewButton.dataset.brandkitHoverText || baseText;
            previewButton.style.background = hoverFill || baseBg;
            previewButton.style.border = '2px solid ' + (hoverFill || baseBorder);
            previewButton.style.color = hoverText;
            previewButton.style.transform = '';
            if (baseShadow) {
                previewButton.style.boxShadow = baseShadow;
            }
        }
    };

    var updateButtonPreview = function () {
        if (!previewButton) {
            return;
        }
        var shape = buttonShapeInput ? buttonShapeInput.value : 'rounded';
        previewButton.classList.remove(
            'brandkit-button-demo--square',
            'brandkit-button-demo--rounded',
            'brandkit-button-demo--pill',
            'brandkit-button-demo--cut',
            'brandkit-button-demo--soft'
        );
        previewButton.classList.add('brandkit-button-demo--' + shape);

        var color = '#0ea5e9';
        var customColor = colorPicker ? normalizeHex(colorPicker.value) : '';
        var accentColor = accentColorInput ? normalizeHex(accentColorInput.value) : '';
        if (colorModeSelect && colorModeSelect.value === 'custom' && customColor) {
            color = customColor;
        } else if (accentColor) {
            color = accentColor;
        }

        var isOutline = buttonStyleInput && buttonStyleInput.value === 'outline';
        var baseBg = isOutline ? 'transparent' : color;
        var baseBorder = color;
        var baseText = isOutline ? color : (isLightColor(color) ? '#0f172a' : '#ffffff');
        previewButton.style.background = baseBg;
        previewButton.style.border = '2px solid ' + baseBorder;
        previewButton.style.color = baseText;
        previewButton.dataset.brandkitBaseBg = baseBg;
        previewButton.dataset.brandkitBaseBorder = baseBorder;
        previewButton.dataset.brandkitBaseText = baseText;

        var hoverFillMode = hoverFillModeInput ? hoverFillModeInput.value : 'custom';
        var hoverFillCustom = hoverFillColorInput ? normalizeHex(hoverFillColorInput.value) : '';
        var hoverFillColor = hoverFillMode === 'button' ? color : (hoverFillCustom || color);
        var hoverTextColor = hoverTextColorInput ? normalizeHex(hoverTextColorInput.value) : '';
        if (!hoverTextColor) {
            hoverTextColor = isLightColor(hoverFillColor) ? '#0f172a' : '#ffffff';
        }
        previewButton.dataset.brandkitHoverFill = hoverFillColor;
        previewButton.dataset.brandkitHoverText = hoverTextColor;

        var textSize = buttonTextSizeInput ? parseInt(buttonTextSizeInput.value || '14', 10) : 14;
        previewButton.style.fontSize = Math.max(10, Math.min(18, textSize)) + 'px';

        previewButton.style.transform = '';
        previewButton.style.boxShadow = '';
        previewButton.dataset.brandkitBaseShadow = window.getComputedStyle(previewButton).boxShadow;
        applyButtonHoverState();
    };

    var applyFieldStyles = function (container) {
        if (!container) {
            return;
        }
        var fields = container.querySelectorAll('.brandkit-preview-field');
        if (!fields.length) {
            return;
        }
        var bg = fieldBgInput ? normalizeHex(fieldBgInput.value) : '#ffffff';
        var border = fieldBorderInput ? normalizeHex(fieldBorderInput.value) : '#cbd5f5';
        var focus = fieldFocusInput ? normalizeHex(fieldFocusInput.value) : border;
        var borderWidth = fieldBorderWidthInput ? parseInt(fieldBorderWidthInput.value || '1', 10) : 1;
        var radius = fieldRadiusInput ? parseInt(fieldRadiusInput.value || '10', 10) : 10;
        var height = fieldHeightInput ? parseInt(fieldHeightInput.value || '36', 10) : 36;
        var width = fieldWidthInput ? parseInt(fieldWidthInput.value || '520', 10) : 520;
        var style = fieldStyleSelect ? fieldStyleSelect.value : 'solid';
        var textColor = fieldTextInput ? normalizeHex(fieldTextInput.value) : '#111827';
        var textSize = fieldTextSizeInput ? parseInt(fieldTextSizeInput.value || '14', 10) : 14;

        fields.forEach(function (field) {
            field.style.height = height + 'px';
            field.style.width = 'min(100%, ' + width + 'px)';
            field.style.maxWidth = width + 'px';
            field.style.borderRadius = radius + 'px';
            field.style.borderWidth = borderWidth + 'px';
            field.style.borderStyle = 'solid';
            field.style.boxShadow = 'none';
            field.style.borderColor = border || '#cbd5f5';
            field.style.background = bg || '#ffffff';

            if (style === 'glass') {
                field.style.background = hexToRgba(bg, 0.35);
                field.style.borderColor = hexToRgba(border, 0.6);
                field.style.boxShadow = '0 8px 16px rgba(15, 23, 42, 0.12)';
            } else if (style === 'soft') {
                field.style.boxShadow = '0 10px 20px rgba(15, 23, 42, 0.15)';
            } else if (style === 'outline') {
                field.style.background = 'transparent';
                field.style.boxShadow = 'none';
            } else if (style === 'underline') {
                field.style.background = 'transparent';
                field.style.border = 'none';
                field.style.borderBottom = borderWidth + 'px solid ' + (border || '#cbd5f5');
                field.style.borderRadius = '0';
            } else if (style === 'neon') {
                field.style.boxShadow = '0 0 0 2px ' + hexToRgba(focus, 0.45) + ', 0 0 16px ' + hexToRgba(focus, 0.6);
            }

            var textNode = field.querySelector('.brandkit-preview-field-text');
            if (textNode) {
                textNode.style.color = textColor || '#111827';
                textNode.style.fontSize = Math.max(10, Math.min(22, textSize)) + 'px';
            }
        });
    };

    var updateFieldPreview = function () {
        if (previewFields) {
            var style = fieldStyleSelect ? fieldStyleSelect.value : 'solid';
            var bg = fieldBgInput ? normalizeHex(fieldBgInput.value) : '#ffffff';
            var border = fieldBorderInput ? normalizeHex(fieldBorderInput.value) : '#cbd5f5';
            var artTone = bg || '#ffffff';
            if (style === 'outline' || style === 'underline') {
                artTone = border || bg || '#ffffff';
            }
            var surface = isLightColor(artTone) ? '#0f172a' : '#ffffff';
            previewFields.style.setProperty('--brandkit-preview-field-surface', surface);
            previewFields.style.setProperty('--brandkit-preview-field-text-size', (fieldTextSizeInput ? fieldTextSizeInput.value : 14) + 'px');
            previewFields.style.setProperty('--brandkit-preview-field-text-color', fieldTextInput ? fieldTextInput.value : '#111827');
            if (fieldWidthInput) {
                previewFields.style.setProperty('--brandkit-preview-field-width', fieldWidthInput.value + 'px');
            }
        }
        applyFieldStyles(previewFields);
    };

    var pulseFieldPreview = function () {
        if (!previewFields) {
            return;
        }
        previewFields.classList.remove('brandkit-field-pulse');
        void previewFields.offsetWidth;
        previewFields.classList.add('brandkit-field-pulse');
    };

    var updateIconPreview = function () {
        if (!previewIcons) {
            return;
        }
        var selectedLogin = document.querySelector('input[name="login_icon_user"]:checked');
        var selectedPassword = document.querySelector('input[name="login_icon_password"]:checked');
        var loginIconValue = selectedLogin ? selectedLogin.value : 'none';
        var passwordIconValue = selectedPassword ? selectedPassword.value : 'none';
        previewIcons.setAttribute('data-login-icon', loginIconValue);
        previewIcons.setAttribute('data-password-icon', passwordIconValue);
        if (iconPositionInput && iconPositionInput.value === 'right') {
            previewIcons.classList.add('icon-right');
        } else {
            previewIcons.classList.remove('icon-right');
        }
        if (iconColor) {
            previewIcons.style.setProperty('--brandkit-preview-icon-color', iconColor.value);
        }
        var style = fieldStyleSelect ? fieldStyleSelect.value : 'solid';
        var bg = fieldBgInput ? normalizeHex(fieldBgInput.value) : '#ffffff';
        var border = fieldBorderInput ? normalizeHex(fieldBorderInput.value) : '#cbd5f5';
        var artTone = bg || '#ffffff';
        if (style === 'outline' || style === 'underline') {
            artTone = border || bg || '#ffffff';
        }
        var iconTone = iconColor ? normalizeHex(iconColor.value) : '';
        var surfaceSource = iconTone || artTone;
        var surface = isLightColor(surfaceSource) ? '#0f172a' : '#ffffff';
        previewIcons.style.setProperty('--brandkit-preview-field-surface', surface);
        previewIcons.style.setProperty('--brandkit-preview-field-text-size', (fieldTextSizeInput ? fieldTextSizeInput.value : 14) + 'px');
        previewIcons.style.setProperty('--brandkit-preview-field-text-color', fieldTextInput ? fieldTextInput.value : '#111827');
        if (fieldWidthInput) {
            previewIcons.style.setProperty('--brandkit-preview-field-width', fieldWidthInput.value + 'px');
        }
        applyFieldStyles(previewIcons);
    };

    var updateWallpaperPreview = function () {
        if (!wallpaperPreview) {
            return;
        }
        var imageUrl = wallpaperPreview.getAttribute('data-wallpaper-image') || '';
        var imageWebpUrl = wallpaperPreview.getAttribute('data-wallpaper-image-webp') || '';
        var overlayColor = wallpaperOverlayColorInput ? normalizeHex(wallpaperOverlayColorInput.value) : '';
        if (!overlayColor) {
            overlayColor = '#000000';
        }
        var overlayOpacity = wallpaperOverlayOpacityInput ? parseInt(wallpaperOverlayOpacityInput.value || '0', 10) / 100 : 0;
        var overlayRgba = hexToRgba(overlayColor, isNaN(overlayOpacity) ? 0.35 : overlayOpacity);
        var backgroundLayers = 'linear-gradient(' + overlayRgba + ', ' + overlayRgba + ')';
        if (imageUrl) {
            var fallbackMime = 'image/jpeg';
            var match = imageUrl.toLowerCase().match(/\.(jpe?g|png|webp)(?:\?|#|$)/);
            if (match && match[1] === 'png') {
                fallbackMime = 'image/png';
            } else if (match && match[1] === 'webp') {
                fallbackMime = 'image/webp';
            }
            if (imageWebpUrl) {
                backgroundLayers += ', image-set(url(\"' + imageWebpUrl + '\") type(\"image/webp\"), url(\"' + imageUrl + '\") type(\"' + fallbackMime + '\"))';
            } else {
                backgroundLayers += ', url(\"' + imageUrl + '\")';
            }
        }
        wallpaperPreview.style.setProperty('--brandkit-wallpaper-background', backgroundLayers);

        var sizeValue = wallpaperSizeInput ? wallpaperSizeInput.value : 'cover';
        var previewSizeMap = {
            cover: 'contain',
            contain: 'contain',
            stretch: 'contain',
            original: 'contain'
        };
        wallpaperPreview.style.setProperty('--brandkit-wallpaper-preview-size', previewSizeMap[sizeValue] || 'contain');

        if (wallpaperPreview.style.getPropertyValue('--brandkit-wallpaper-position')) {
            wallpaperPreview.style.removeProperty('--brandkit-wallpaper-position');
        }

        wallpaperPreview.style.setProperty('background-attachment', 'scroll');

        var placeholder = wallpaperPreview.querySelector('.brandkit-wallpaper-placeholder');
        if (placeholder) {
            placeholder.style.display = imageUrl ? 'none' : 'flex';
        }
    };

    var getPresetPreviewUrl = function () {
        if (!wallpaperPresetInput) {
            return '';
        }
        var selected = wallpaperPresetInput.options[wallpaperPresetInput.selectedIndex];
        return selected ? (selected.getAttribute('data-preview-url') || '') : '';
    };

    var getPresetPreviewWebpUrl = function () {
        if (!wallpaperPresetInput) {
            return '';
        }
        var selected = wallpaperPresetInput.options[wallpaperPresetInput.selectedIndex];
        return selected ? (selected.getAttribute('data-preview-webp-url') || '') : '';
    };

    var toggleWallpaperSource = function () {
        var source = wallpaperSourceInput ? wallpaperSourceInput.value : 'preset';
        var isCustom = source === 'custom';
        if (wallpaperPresetWrap) {
            wallpaperPresetWrap.classList.toggle('brandkit-toggle-hidden', isCustom);
        }
        if (wallpaperPresetInput) {
            wallpaperPresetInput.disabled = isCustom;
        }
        if (wallpaperInput) {
            if (isCustom) {
                wallpaperInput.removeAttribute('disabled');
            } else {
                wallpaperInput.setAttribute('disabled', 'disabled');
            }
        }
        if (wallpaperUploadAction) {
            wallpaperUploadAction.classList.toggle('brandkit-toggle-hidden', !isCustom);
        }
        if (wallpaperPreview) {
            var customUrl = wallpaperPreview.getAttribute('data-wallpaper-custom') || '';
            var customWebp = wallpaperPreview.getAttribute('data-wallpaper-custom-webp') || '';
            var presetUrl = getPresetPreviewUrl();
            var presetWebp = getPresetPreviewWebpUrl();
            wallpaperPreview.setAttribute('data-wallpaper-image', isCustom ? customUrl : presetUrl);
            wallpaperPreview.setAttribute('data-wallpaper-image-webp', isCustom ? customWebp : presetWebp);
            updateWallpaperPreview();
        }
    };

    if (previewButton) {
        previewButton.addEventListener('mouseenter', function () {
            isPreviewButtonHovered = true;
            applyButtonHoverState();
        });
        previewButton.addEventListener('mouseleave', function () {
            isPreviewButtonHovered = false;
            applyButtonHoverState();
        });
    }

    var updateAllPreviews = function () {
        updateLayoutPreview(previewLayout);
        updateLayoutPreview(previewPanel);
        updateButtonPreview();
        updateFieldPreview();
        updateIconPreview();
        updateWallpaperPreview();
    };
    if (colorPicker) {
        colorPicker.addEventListener('input', function (event) {
            var value = normalizeHex(event.target.value);
            if (value) {
                if (colorModeSelect) {
                    colorModeSelect.value = 'custom';
                }
                toggleColorMode();
            }
        });
    }

    if (wallpaperInput && form) {
        wallpaperInput.addEventListener('change', function () {
            if (wallpaperInput.files && wallpaperInput.files.length) {
                if (wallpaperPreview) {
                    var fileUrl = URL.createObjectURL(wallpaperInput.files[0]);
                    wallpaperPreview.setAttribute('data-wallpaper-custom', fileUrl);
                    wallpaperPreview.setAttribute('data-wallpaper-image', fileUrl);
                    wallpaperPreview.setAttribute('data-wallpaper-custom-webp', '');
                    wallpaperPreview.setAttribute('data-wallpaper-image-webp', '');
                }
                if (wallpaperSourceInput) {
                    wallpaperSourceInput.value = 'custom';
                }
                updateWallpaperPreview();
                if (wallpaperRemove) {
                    wallpaperRemove.checked = false;
                }
            }
        });
    }

    if (wallpaperDelete && wallpaperRemove && form) {
        wallpaperDelete.addEventListener('click', function () {
            wallpaperRemove.checked = true;
            if (wallpaperSourceInput) {
                wallpaperSourceInput.value = 'custom';
            }
            var saveActionButton = form.querySelector('button[name="brandkit_action"][value="save_login_settings"]');
            if (typeof form.requestSubmit === 'function' && saveActionButton) {
                form.requestSubmit(saveActionButton);
                return;
            }
            var actionInput = form.querySelector('input[name="brandkit_action"]');
            if (!actionInput) {
                actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'brandkit_action';
                form.appendChild(actionInput);
            }
            actionInput.value = 'save_login_settings';
            form.submit();
        });
    }

    [
        wallpaperSizeInput,
        wallpaperPositionInput,
        wallpaperAttachmentInput,
        wallpaperOverlayColorInput,
        wallpaperOverlayOpacityInput
    ].forEach(function (input) {
        if (!input) {
            return;
        }
        input.addEventListener('input', updateWallpaperPreview);
        input.addEventListener('change', updateWallpaperPreview);
    });

    if (wallpaperSourceInput) {
        wallpaperSourceInput.addEventListener('change', toggleWallpaperSource);
    }

    if (wallpaperPresetInput) {
        wallpaperPresetInput.addEventListener('change', function () {
            if (wallpaperSourceInput) {
                wallpaperSourceInput.value = 'preset';
            }
            toggleWallpaperSource();
        });
    }

    if (form) {
        form.addEventListener('submit', function () {
            if (!wallpaperInput || !wallpaperInput.files || !wallpaperInput.files.length) {
                return;
            }
            if (wallpaperSourceInput) {
                wallpaperSourceInput.value = 'custom';
            }
            wallpaperInput.removeAttribute('disabled');
        });
    }

    if (layoutSelect) {
        layoutSelect.addEventListener('change', function () {
            toggleSplitControls();
            togglePanelSizeControls();
        });
    }

    if (panelTransparent) {
        panelTransparent.addEventListener('change', function () {
            togglePanelSizeControls();
        });
    }

    if (logoPosition) {
        logoPosition.addEventListener('change', function () {
            toggleLogoOffsetControls();
        });
    }

    if (fieldStyleSelect) {
        fieldStyleSelect.addEventListener('change', function () {
            toggleFieldFocusControls();
        });
    }

    if (fieldBorderWidthInput) {
        fieldBorderWidthInput.addEventListener('input', pulseFieldPreview);
    }

    if (fieldTextSizeInput) {
        fieldTextSizeInput.addEventListener('input', pulseFieldPreview);
    }

    if (fieldWidthInput) {
        fieldWidthInput.addEventListener('input', pulseFieldPreview);
    }

    if (colorModeSelect) {
        colorModeSelect.addEventListener('change', function () {
            toggleColorMode();
        });
    }

    if (hoverEffectSelect) {
        hoverEffectSelect.addEventListener('change', function () {
            toggleHoverOptions();
        });
    }

    if (iconColor) {
        iconColor.addEventListener('input', function () {
            var iconScopes = document.querySelectorAll('.brandkit-icon-grid-scope');
            var isLight = isLightColor(iconColor.value);
            var bgColor = isLight ? '#0f172a' : '#ffffff';
            iconScopes.forEach(function (scope) {
                scope.style.setProperty('--brandkit-icon-color', iconColor.value);
                scope.style.setProperty('--brandkit-icon-bg', bgColor);
            });
        });
    }

    if (form) {
        form.addEventListener('input', updateAllPreviews);
        form.addEventListener('change', updateAllPreviews);
    }

    toggleColorMode();
    toggleHoverOptions();
    toggleSplitControls();
    togglePanelSizeControls();
    toggleLogoOffsetControls();
    toggleFieldFocusControls();
    toggleWallpaperSource();
    updateAllPreviews();
    document.querySelectorAll('input[type="range"]').forEach(function (input) {
        var output = document.getElementById(input.id + '_value');
        if (!output) {
            return;
        }
        var unitMatch = output.textContent.match(/[^0-9.]+$/);
        var unit = unitMatch ? unitMatch[0] : '';
        var updateValue = function () {
            output.textContent = input.value + unit;
        };
        input.addEventListener('input', updateValue, { passive: true });
        input.addEventListener('change', updateValue, { passive: true });
        input.addEventListener('pointermove', updateValue, { passive: true });
        input.addEventListener('mousemove', updateValue, { passive: true });
        updateValue();
    });
});
