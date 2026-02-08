# GenArt Featured Images

WordPress plugin that generates abstract WebP featured images for posts and applies SEO-friendly media metadata automatically.

## Highlights

- Generates a featured image when a post has no thumbnail.
- Supports bulk generation for existing posts.
- Includes a dry-run workflow before batch execution.
- Uses customizable SEO template placeholders:
  - `%title%`
  - `%sitename%`
- Supports predefined palettes and custom HEX color lists.
- Includes localization files (`.pot`, `.po`, `.mo`) for common locales.
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

When a standard post is saved and has no featured image, the plugin generates one automatically.

### Bulk generation

1. Go to the `GenArt Featured Images` admin menu.
2. Run **Dry Run** to inspect pending posts and batch profile.
3. Start **Bulk Generation**.

The plugin processes posts in memory-aware batches.

## Localization

Source template:

- `languages/genart-featured-images.pot`

Included locale packs:

- `de_DE`
- `es_ES`
- `fr_FR`
- `it_IT`
- `pt_BR`

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
├── languages/
│   ├── genart-featured-images.pot
│   ├── genart-featured-images-<locale>.po
│   └── genart-featured-images-<locale>.mo
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

### Regenerate translation template

```bash
xgettext --language=PHP --from-code=UTF-8 \
  --add-comments=translators \
  --keyword=__ --keyword=_e --keyword=_x:1,2 --keyword=_ex:1,2 \
  --keyword=_n:1,2 --keyword=_nx:1,2,4 \
  --keyword=esc_html__ --keyword=esc_html_e --keyword=esc_html_x:1,2 \
  --keyword=esc_attr__ --keyword=esc_attr_e --keyword=esc_attr_x:1,2 \
  --keyword=_n_noop:1,2 --keyword=_nx_noop:1,2,3 \
  -o languages/genart-featured-images.pot genart-featured-images.php
```

## License

GPL-2.0-or-later. See plugin headers and `readme.txt` for details.
