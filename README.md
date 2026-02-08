# GenArt Featured Images

WordPress plugin that generates abstract WebP featured images for posts and applies SEO-friendly media metadata automatically.

## Highlights

- Generates a featured image when a post has no thumbnail.
- Supports bulk generation for existing posts.
- Includes a dry-run workflow before batch execution.
- Uses customizable SEO template placeholders:
  - `%title%`
  - `%sitename%`
- Supports per-post style/scheme selectors in the editor.
- Supports predefined palettes and custom named color schemes from plugin settings.
- Randomizes defaults for new posts and pages.
- Supports category/tag generation rules with deterministic priority.
- Marks generated media clearly and includes safe cleanup for unused generated images.
- Implements capability checks, nonce validation, and sanitized settings.

## Requirements

- WordPress `6.0+`
- PHP `7.4+`
- PHP GD extension with WebP support enabled

## Installation

1. Copy this plugin folder into `wp-content/plugins/genart-featured-images`.
2. Activate **GenArt Featured Images** in the WordPress admin.
3. Open the `GenArt Featured Images` menu in the WordPress admin sidebar.
4. Configure design and SEO template settings.

## Usage

### Automatic generation on save

When a post or page is saved and has no featured image, the plugin generates one automatically.

### Bulk generation

1. Go to the `GenArt Featured Images` admin menu.
2. Run **Dry Run** to inspect pending posts and batch profile.
3. Start **Bulk Generation**.

The plugin processes posts in memory-aware batches.

## Generation Decision Logic

For each generation event, the plugin resolves style and color scheme in this strict order:

1. Manual editor action payload (highest priority)
2. Saved per-post/per-page preferences
3. Matching taxonomy rule
4. Global defaults

Rule resolution details:

1. Tag rules are checked first.
2. Category rules are checked second.
3. Inside each group, rules are evaluated from top to bottom.
4. Each category/tag can be configured only once to avoid direct conflicts.

Default behavior for new content:

1. Auto-generation runs for posts and pages (if enabled).
2. If random defaults are enabled, algorithm and scheme are randomly selected from available options when no stronger source provides a value.
3. If random defaults are disabled, configured fixed defaults are used.

## Configuration Guide

### Plugin settings

1. Set global default style and scheme.
2. Optionally enable random default mode (recommended if you want variety).
3. Add custom named schemes (name + comma-separated HEX values).
4. Define tag/category rules only where needed.
5. Keep rule count manageable and easy to audit.

### Editor overrides

1. In post/page editor, choose style and scheme in the plugin metabox.
2. Click `Generate Featured Image Now` to force manual generation.
3. Manual selection always has priority over rules/defaults.

### Cleanup behavior

1. Cleanup only targets attachments marked as plugin-generated.
2. Cleanup keeps any plugin-generated attachment still used as featured image by any post type.
3. Cleanup never targets unrelated/manual media.
4. Deletion is permanent and also removes WordPress-generated image sub-sizes.
5. Cleanup requires explicit confirmation in the admin popup before execution.

## In-Plugin Help

The plugin includes a dedicated **Help** page in the WordPress admin menu (`GenArt Featured Images > Help`) that explains:

- Priority and rule resolution
- Recommended rule design
- Cleanup scope, limitations, and safety notes
- Suggested operational workflow

## WordPress.org Assets

Repository includes production PNG assets in `assets/`:

- `banner-772x250.png`
- `banner-1544x500.png`
- `icon-128x128.png`
- `icon-256x256.png`
- `screenshot-1.png`
- `screenshot-2.png`

For WordPress.org SVN, these must be committed to the root-level `/assets` directory (outside `/trunk`).

## Project Structure

```text
.
├── assets/
│   ├── banner-772x250.png
│   ├── banner-1544x500.png
│   ├── icon-128x128.png
│   ├── icon-256x256.png
│   ├── screenshot-1.png
│   ├── screenshot-2.png
│   └── js/admin.js
├── genart-featured-images.php
├── readme.txt
├── todo.txt
└── uninstall.php
```

## Security Notes

- AJAX endpoints require valid nonces and `manage_options` capability.
- Settings are sanitized via `register_setting` callback.
- File handling uses WordPress media APIs and error checks.

## Development

### Quick checks

```bash
php -l genart-featured-images.php
php -l uninstall.php
```


## License

GPL-2.0-or-later. See plugin headers and `readme.txt` for details.
