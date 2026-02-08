=== GenArt Featured Images ===
Contributors: drhdev
Tags: featured image, seo, webp, automation, media, ai art
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.2
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
* Added "Tangled sinus" art style with intertwined sine-wave generation.
* File-based color schemes loaded automatically from `includes/schemes/` (one scheme per file).
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

= 0.2 =
* Version bump for release packaging and distribution.

= 0.1.9 =
* Removed numeric prefixes from settings section headings.
* Increased editor metabox spacing between art style selector and color scheme heading.
* Added new art style: `Broken hectagons`.
* Added three new popular muted color schemes: `Solarized soft`, `Gruvbox soft`, and `Catppuccin mocha`.

= 0.1.8 =
* Version bump for release packaging and distribution.

= 0.1.7 =
* Improved Settings and Help page width usage to avoid cramped column rendering.
* Removed dependency on WordPress core `.card` class to prevent narrow layout constraints.
* Fixed default color scheme selector rendering in Settings section 1.
* Updated Category/Tag rule removal UX to button-based removal with double confirmation.
* Improved alignment of bulk generation and cleanup action buttons.
* Improved editor metabox spacing between style selector, color scheme heading, and generate button.
* Updated overwrite helper text to: "Click the button to replace the existing featured image."

= 0.1.6 =
* Version bump for release packaging and distribution.

= 0.1.5 =
* Improved admin Settings and Help layout to use available page width more effectively.
* Fixed default color scheme control rendering in Settings section 1.
* Replaced rule removal checkbox with a dedicated remove button and double confirmation flow.
* Improved bulk generation and cleanup button alignment and section usability.
* Added clearer bulk execution explanation for dry run vs generation behavior.
* Improved spacing in editor metabox between style/scheme controls and generate button.

= 0.1.4 =
* Refactored color schemes into auto-discovered modular files in `includes/schemes/` (one scheme per file).
* Replaced in-admin custom color scheme editing with file-based scheme editing for simpler maintenance.
* Added file-based scheme loader and safety normalization for scheme IDs.
* Updated settings/help guidance for one-style-per-file and one-scheme-per-file workflows.
* Removed legacy numeric style ID support.
* Added strict validation for style/scheme file naming, class naming, IDs, and scheme color formats with admin rejection notices.

= 0.1.3 =
* Switched Settings and Help pages to a single-column layout for clearer top-to-bottom flow.
* Expanded admin content width usage to avoid cramped card columns.
* Rebuilt editor metabox style/scheme inputs as single-select dropdowns with improved spacing and usability.
* Improved metabox field labeling and button spacing for better accessibility and clarity.
* Refactored art styles into auto-discovered modular files in `includes/styles/` for easier future expansion.
* Added new built-in style: `Tangled sinus`.

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

= 0.2 =
Maintenance release version bump.

= 0.1.9 =
Added `Broken hectagons` style, three new muted schemes, and settings/editor layout refinements.

= 0.1.8 =
Maintenance release version bump.

= 0.1.7 =
UI/UX update for admin Settings/Help layout width, rules removal flow, and editor metabox spacing.

= 0.1.6 =
Maintenance release version bump.

= 0.1.5 =
Improved admin layout/spacing, fixed default color scheme selector, and updated rule removal to confirmed button-based workflow.

= 0.1.4 =
Color schemes now use one-file-per-scheme auto-discovery in `includes/schemes/`, matching the style module system.

= 0.1.3 =
Improved admin/settings usability with full-width single-column layout and rebuilt editor dropdown controls.

= 0.1.2 =
Added per-post style/scheme controls, custom color scheme management, and English-only runtime.
