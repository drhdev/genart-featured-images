=== GenArt Featured Images ===
Contributors: mou
Tags: featured image, seo, webp, automation, media, ai art
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate abstract WebP featured images for posts with SEO-friendly ALT/attachment metadata and safe bulk automation.

== Description ==

GenArt Featured Images automatically generates abstract featured images for posts that do not already have one, then applies optimized media metadata.

Key features:

* Automatic featured image generation on post save.
* Bulk generation for existing posts without thumbnails.
* Dry run preview before starting batch processing.
* SEO template support using `%title%` and `%sitename%` placeholders.
* Custom HEX color list or predefined palettes.
* WebP output with WordPress media sideload integration.
* Capability, nonce, and settings sanitization hardening.
* Defensive runtime checks for GD/WebP support and media failures.
* Translation-ready with bundled locale files.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install via the WordPress plugin screen.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Go to `Settings > GenArt Featured Images` to configure options.

== Frequently Asked Questions ==

= Does this overwrite existing featured images? =

No. The plugin only generates an image when a post does not already have a featured image.

= What server requirements does this plugin have? =

PHP must have the GD extension with WebP support enabled.

= Which placeholders are available in the SEO template? =

`%title%` and `%sitename%`.

= Which languages are included? =

The plugin ships with translation files for `de_DE`, `es_ES`, `fr_FR`, `it_IT`, and `pt_BR` (plus the source POT template).

== Screenshots ==

1. Settings screen with design options, SEO template, and generation controls.
2. Bulk generation workflow view with queue/progress-oriented layout.

== Changelog ==

= 0.1 =
* Refactored plugin architecture for WordPress coding standards.
* Added strict sanitization and capability checks.
* Added robust error handling for generation and media sideload workflows.
* Replaced inline JavaScript with dedicated admin asset.
* Added uninstall cleanup, WP.org-ready assets, POT template, and bundled locale files.

== Upgrade Notice ==

= 0.1 =
Improved security hardening, reliability, and repository readiness.
