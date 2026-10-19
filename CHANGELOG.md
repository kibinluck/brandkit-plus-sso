# Changelog

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
