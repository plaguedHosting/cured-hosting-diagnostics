# Cured Hosting Diagnostics — Install & Test

This folder contains a minimal header/footer implementation and supporting assets for testing in WordPress.

Installation options

1) Install as a normal plugin (recommended)

- Copy the entire `cured-hosting-diagnostics-package` directory into your WordPress `wp-content/plugins/` folder.
- Activate the plugin from WP Admin -> Plugins, or via WP-CLI:

```powershell
Copy-Item -Path .\mu-plugins\cured-hosting-diagnostics-package -Destination 'C:\path\to\wordpress\wp-content\plugins' -Recurse
wp plugin activate cured-hosting-diagnostics-package
```

2) Install as a Must-Use plugin (mu-plugin)

Note: WordPress only automatically loads PHP files directly inside `wp-content/mu-plugins/` — subdirectories are not auto-loaded. Use one of these approaches:

- Move plugin files directly into `wp-content/mu-plugins/`.
- Or create a small loader file inside `wp-content/mu-plugins/` that requires the main plugin file. Example loader (`cured-hosting-diagnostics-loader.php`):

```php
<?php
require WPMU_PLUGIN_DIR . '/cured-hosting-diagnostics-package/cured-hosting-diagnostics.php';
```

PowerShell to create loader (adjust paths):

```powershell
$loader = "<?php`nrequire WPMU_PLUGIN_DIR . '/cured-hosting-diagnostics-package/cured-hosting-diagnostics.php';`n"
Set-Content -Path 'C:\path\to\wordpress\wp-content\mu-plugins\cured-hosting-diagnostics-loader.php' -Value $loader -Force
```

Post-install steps

- Visit Appearance → Menus and create a menu; assign it to the **Primary Menu** location.
- Set a custom logo at Appearance → Customize → Site Identity, or the header will show the site title and description.
- Open the front-end and confirm the header appears. If installed as a plugin, activate it in Plugins.

Quick test ideas (break it!)

- Remove or rename `header.php` to see PHP warnings (only test on development site).
- Edit `header.css` to force layout regressions.
- Unregister the menu or call `wp_nav_menu()` with invalid args to see graceful fallback.

Support

If paths need adjusting (theme vs plugin), tell me where WordPress is installed and I can give exact PowerShell commands.
# Cured Hosting Diagnostics

A self-contained WordPress utility suite for diagnostics, optimization, SEO, media workflows, and premium monetization tools.

## What this repository contains

This plugin bundle is designed to keep everything in one place while delivering a powerful premium toolset:

- Core diagnostics and premium safety rollback
- Local image optimization + WebP generation
- Premium image resize and watermarking
- Remote video optimization via Zencoder
- Remote image/video gallery builder
- Premium SEO enhancements and JSON-LD schema
- Banner placement and affiliate manager
- Proxy crawler with harvest/test status dashboard
- AI-powered code assistant

## Why it’s valuable

- Built to replace multiple plugins with a single, premium-ready suite
- Self-contained and lightweight with no unnecessary external dependencies
- Premium license unlocks high-value automation, optimization, and monetization features
- Ideal for professionals who want security, performance, and revenue tools in one package

## Installation

1. Copy the folder into your WordPress installation under:
   - `wp-content/plugins/cured-hosting-diagnostics`
2. Activate the plugin from **WordPress Admin > Plugins**
3. Go to **Settings > PlagueDr License** and enter the premium token
4. Configure the modules from their respective settings pages

## Core files and structure

- `cured-hosting-diagnostics.php` - plugin bootstrap and module loader
- `plaguedr-core.php` - premium license gate and shared settings

This plugin is built by curedhosting.com and designed to direct additional site visitors back to the service.
- `class-diagnostics.php` - premium diagnostics and safety features
- `class-seo.php` - metadata, Open Graph, and premium schema injection
- `class-seo-admin.php` - SEO settings UI and premium option controls
- `class-media-pipeline.php` - remote video optimization gateway
- `class-copilot.php` - premium code assistant backend
- `class-gallery-maker.php` - remote gallery builder and shortcode renderer
- `class-banner-affiliate.php` - premium banner placements and affiliate manager
- `pixel-pure/pixel-pure.php` - image optimizer with resize and watermark
- `proxy-crawler/proxy-crawler-core.php` - proxy crawler engine
- `proxy-crawler/proxy-crawler-admin.php` - crawler admin UI
- `class-airbag.php` - supplemental module
- `beak-armor.php` - supplemental module
- `leech-drain-cache.php` - supplemental module
- `login-sentinel/login-sentinel.php` - supplemental module
- `code-assistant/code-assistant.php` - supplemental module
- `miasma-shield/miasma-shield.php` - supplemental module

## Usage notes

- Premium features are gated by the license token stored in the plugin options.
- Galleries and banners use shortcodes for flexible placement.
- SEO settings are managed in the Media Pipeline admin page.
- Image optimization and watermarking work on upload and legacy media via bulk processing.

## Recommended pricing positioning

Suggested tiers for commercial release:

- Basic premium license: `$99–$149` one-time
- Annual support license: `$199–$299/year`
- Agency/enterprise tier: `$399–$599/year`

## Saved documentation

- `PLUGIN_SUITE_OVERVIEW.txt` - feature overview and file checklist
- `INSTALL_INSTRUCTIONS.txt` - install and deployment steps

Modification Note (2026-06-13):
- Added `uninstall.php` to the package to assist with safe cleanup of options, transients, and scheduled events when removing the package.
- `plaguedr-core.php` now exposes an admin-triggered cleanup button on the `Settings > PlagueDr License` page.
- Loader/packaging guidance updated to reference `exclude.list` for runtime exclusions.
