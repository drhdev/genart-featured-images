=== GenArt Featured Images ===
Contributors: drhdev
Tags: featured image, seo, webp, automation, media, ai art
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate abstract WebP featured images for posts and pages with SEO-friendly ALT/attachment metadata and safe bulk automation.

== Description ==

GenArt Featured Images automatically generates abstract featured images for posts and pages that do not already have one, then applies optimized media metadata.

Key features:

* Automatic featured image generation on post/page save.
* Bulk generation for existing posts without thumbnails.
* Dry run preview before starting batch processing.
* SEO template support using `%title%` and `%sitename%` placeholders.
* Per-post art style and color scheme selectors in the post editor.
* Custom named color schemes that can be added/removed in plugin settings.
* Randomized default algorithm and color scheme for new posts and pages.
* Optional category/tag rules with deterministic priority (tag rules first, then category rules).
* Manual editor selection always has priority over rules and global defaults.
* Plugin-generated media is clearly marked and can be filtered by name in Media Library.
* Built-in cleanup deletes only unused plugin-generated images, never unrelated media.
* Cleanup requires an explicit confirmation popup before execution.
* WebP output with WordPress media sideload integration.
* Capability, nonce, and settings sanitization hardening.
* Defensive runtime checks for GD/WebP support and media failures.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install via the WordPress plugin screen.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Open the `GenArt Featured Images` menu in the WordPress admin sidebar to configure options.

== Frequently Asked Questions ==

= Does this overwrite existing featured images? =

Automatic generation never overwrites an existing featured image. Manual generation can optionally overwrite when enabled in plugin settings.

= What server requirements does this plugin have? =

PHP must have the GD extension with WebP support enabled.

= Which placeholders are available in the SEO template? =

`%title%` and `%sitename%`.

== Screenshots ==

1. Settings screen with design options, SEO template, and generation controls.
2. Bulk generation workflow view with queue/progress-oriented layout.

== Changelog ==

= 0.1.2 =
* Removed translation packs and switched plugin runtime to English-only.
* Added per-post style and color scheme selectors in the post editor.
* Added named custom color scheme management (add/remove) in plugin settings.
* Added randomized defaults for new posts/pages and category/tag rule-based generation logic.
* Manual editor selections now always override rule/default generation choices.
* Added safe cleanup tool for unused plugin-generated media only.

= 0.1.1 =
* Refactored plugin architecture for WordPress coding standards.
* Added strict sanitization and capability checks.
* Added robust error handling for generation and media sideload workflows.
* Replaced inline JavaScript with dedicated admin asset.
* Added uninstall cleanup, WP.org-ready assets, custom color schemes and per-post style/scheme controls.

== Upgrade Notice ==

= 0.1.2 =
Added per-post style/scheme controls, custom color scheme management, and English-only runtime.
