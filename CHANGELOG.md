# Changelog

## 0.3.2

### Added
- SSO mode: "SSO button colors" option. The SSO button can keep the login
  button colors (default) or get its own background and text colors.

### Fixed
- SSO mode: the SSO button text and icon always follow the button text color
  (other styles on the page could paint them in the page text color).

## 0.3.1

### Fixed
- The GLPI copyright footer of the login page showed up in the middle of the
  screen when the samlSSO plugin was enabled. samlSSO 1.3.x prints one
  `</div>` too many in the login page, which moves the footer out of the login
  container. The footer is now kept under the login area in both cases and, on
  screens at least 768x600, pushed to the bottom of the screen.

## 0.3.0

### Added
- **SSO mode** (Login Page step, "Single sign-on (SSO)" section). When an SSO
  plugin adds a button to the login page (e.g. samlSSO), the user and password
  fields are hidden and the SSO button takes their place, in the login button
  style, with an optional text above it.
  - Local accounts stay reachable at `/?bypass=1&noAUTO=1` (the same bypass
    address as samlSSO). This is decided server side: with that parameter the
    SSO stylesheet is simply not sent.
  - Optional discreet "Sign in with a local account" link under the SSO button.
  - Safety net: the fields are only hidden while an SSO button is actually on
    the page; if the SSO plugin is disabled or fails, the normal form comes back.
  - Hiding the fields is visual only; it does not disable local authentication.

## 0.2.1

### Fixed
- Two-factor authentication screens were broken by the login page styles
  (issue #4): the 6 code inputs were squashed or overflowing, the title was
  misaligned and the Cancel/Verify buttons were stacked and cut off. The same
  rules also broke the GLPI 11 2FA setup page (QR code) and squeezed the error
  page button. These pages, as well as backup codes, password reset and the
  login error page, now keep the branding (wallpaper, panel, logo, colors) with
  GLPI's own layout. Tall pages scroll instead of being cut off at the top.
- GLPI 11: the "Forgotten password?" link is centered under the sign-in button
  again instead of sticking to the left edge.

## 0.2.0

Refactoring and hardening of 0.1.7. The generated login stylesheet is byte-for-byte
identical to 0.1.7 for the same settings (checked on 800 random configurations,
GLPI 10 and 11 layouts); the only intended visual change is the lighter wallpaper.

### Fixed
- Fatal error `Class "Http" not found` on the logo preview and upload endpoints
  (`Http::displayErrorAndDie()` does not exist; GLPI's is `Html::`).
- GLPI 11: `js/brandkit-error.js` returned 404 because GLPI 11 only serves plugin
  static files from `public/`. Static files now live in `public/`.
- The login stylesheet was rewritten on every login page view, changing its
  version parameter each time and defeating browser caching. It is now only
  written when the settings change (atomic write).
- A customized `GLPI_PLUGIN_DOC_DIR` was ignored (files went to `GLPI_VAR_DIR/_plugins`,
  where `pluginimage.send.php` and `css.php` do not look). Existing files are migrated
  on update.
- Logo previews were broken when GLPI is installed in a sub-folder (`/glpi`).
- Header logos were cached by browsers after a new upload (URLs now carry `?v=`).
- Favicon upload field accepted PNG/JPEG, which the server then refused.
- Legacy pre-0.1.6 logo scale settings were never read.
- `plugin_check_prerequisites_brandkit` / `plugin_check_config_brandkit` had the wrong
  names (GLPI never called them); the `curl` requirement they declared was unused.

### Security
- Uploaded logos and wallpapers are always decoded and re-encoded with GD, which
  drops metadata and anything appended to the image (e.g. PHP/HTML polyglots).
- Size limits (logo 5 MB, favicon 1 MB, wallpaper 15 MB) and a pixel limit checked
  before decoding, so a crafted image cannot exhaust PHP memory.
- Favicon is validated by its ICO header instead of the MIME sniffed by `finfo`.
- Every translated string and URL echoed in the admin page is HTML-escaped.
- File names reaching the filesystem are whitelisted.

### Changed
- Works from GLPI 11's marketplace directory outside the GLPI tree (official Docker
  image: `/var/glpi/marketplace`): front scripts no longer include `inc/includes.php`
  on GLPI 11, where the kernel already boots GLPI.
- Uploaded wallpapers keep a master copy in the plugin storage and are republished
  automatically when `pics/` is reset (GLPI upgrade, new Docker container).
- Header logos are served from the plugin storage when GLPI's own file could not be
  replaced, so a reset `pics/` never shows the GLPI logo instead of yours.
- GLPI's own logo/favicon files are backed up (`files/_plugins/brandkit/originals/`)
  before being replaced, and restored from there.
- Custom logos are re-applied on the login page if a GLPI upgrade overwrote them.
- Uninstall now also deletes the plugin settings, its stored files and the
  published wallpapers.
- Wallpapers (uploads and presets) are published as progressive JPEG, resized to
  1920×1080 max: the default preset goes from 1.6 MB to ~150 KB per login view.
  Images with transparency stay PNG.
- The admin page no longer loads Google Fonts (privacy/LGPD, offline installs).
- `Plugin::getWebDir()` (deprecated in GLPI 11) is no longer called on GLPI 11.
- Code layout: shared helpers in `inc/paths.php`, file and image handling in
  `inc/assets.php`, logo cards in `inc/admin_views.php`; the admin page's 1,870
  lines of inline CSS and 920 lines of inline JS moved to `public/css` and `public/js`.
- `logo.png` added so GLPI 11 shows the plugin icon.
- pt_BR translation completed; `locales/brandkit.pot` regenerated from the sources.

### Removed
- Dead code: `css/brandkit.php`, `css/brandkit.css`, `css/brandkit-login.css`,
  `front/brandkit-login.css.php`, `front/brandkit.login.css.php`,
  `front/wallpaper.send.php`, `messages.mo`, unused images (~3.4 MB), the unreachable
  wallpaper actions of `logo_upload.php` and the `save_login_logo_sizes` handler.
