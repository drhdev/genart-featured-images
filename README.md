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
- Includes `Tangled sinus` art style with intertwined sine-wave rendering.
- Loads art styles automatically from `includes/styles/` for easier extension.
- Loads color schemes automatically from `includes/schemes/` for easier extension.
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
3. Add or edit style files and scheme files directly in `includes/`.
4. Define tag/category rules only where needed.
5. Keep rule count manageable and easy to audit.

### Adding new art styles

Use starter template: `includes/styles/template-style.php`

1. Place one style per file in `includes/styles/`.
2. Filename must match exactly: `class-genart-style-your-style.php`.
3. Class name must match filename slug exactly: `Genart_Style_Your_Style`.
4. Extend `Genart_Style_Base` and implement:
   - `get_id()`
   - `get_label()`
   - `render( $image, $colors, $width, $height )`
5. `get_id()` must return `your_style` (snake_case and equal to filename slug).
6. `get_label()` must return a non-empty string.
7. Save the file; the plugin auto-discovers styles from that folder.
8. The new style appears automatically in settings, rules, and editor dropdowns.

### Adding new color schemes

Use starter template: `includes/schemes/template-scheme.php`

1. Place one scheme per file in `includes/schemes/`.
2. Filename must match exactly: `class-genart-scheme-your-scheme.php`.
3. Class name must match filename slug exactly: `Genart_Scheme_Your_Scheme`.
4. Extend `Genart_Scheme_Base` and implement:
   - `get_id()`
   - `get_label()`
   - `get_colors()`
5. `get_id()` must return `your_scheme` (snake_case and equal to filename slug).
6. `get_label()` must return a non-empty string.
7. `get_colors()` must return an array with at least 2 colors in strict `#rrggbb` format.
8. Save the file; the plugin auto-discovers schemes from that folder.
9. The new scheme appears automatically in settings, rules, and editor dropdowns.

### Validation and rejection rules

The plugin validates every discovered style and scheme file. Invalid files are rejected and do not appear in dropdowns/rules.

Validation checks:

1. Correct folder and filename pattern.
2. Expected class exists and extends the correct base class.
3. `get_id()` format is valid snake_case and matches the filename slug.
4. `get_label()` is not empty.
5. For schemes, `get_colors()` returns at least two valid `#rrggbb` values.

If validation fails:

1. The module is ignored.
2. A clear admin error notice is displayed on plugin Settings and Help pages.

For strict AI-ready generation instructions and templates, use:

1. `.notes/art-styles-color-schemes-instructions.md`

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
├── includes/
│   ├── schemes/
│   │   ├── class-genart-scheme-base.php
│   │   ├── class-genart-scheme-modern-blue.php
│   │   ├── class-genart-scheme-sunset.php
│   │   ├── class-genart-scheme-nordic.php
│   │   └── class-genart-scheme-cyber.php
│   └── styles/
│       ├── class-genart-style-base.php
│       ├── class-genart-style-mesh-gradient.php
│       ├── class-genart-style-bauhaus-shapes.php
│       ├── class-genart-style-digital-stream.php
│       └── class-genart-style-tangled-sinus.php
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
