# Cured Hosting Diagnostics — What this package does

This page explains the components included in the Cured Hosting Diagnostics package and how they improve the reliability, security, performance, and debugging experience for Cured Hosting customers.

**Components:**
- **PlagueDr Diagnostics (`class-diagnostics.php`)**: Core runtime safety engine. Captures preflight snapshots of active plugins, intercepts fatal crashes on shutdown, and can automatically roll back plugin activations when a structural error is detected. Provides memory and safety telemetry.
- **PlagueDr SEO (`class-seo.php`, `class-seo-admin.php`)**: Adds SEO helpers, default Open Graph assets, and optional premium schema. Provides an admin settings UI to configure organization metadata and advanced schema features.
- **Media Pipeline (`class-media-pipeline.php`)**: Integrates with external/transcoding workflows (Zencoder-style) and provides optimized media delivery hooks. Reduces page load by preparing responsive media and pipeline hooks for background processing.
- **Sentinel (`class-sentinel.php`)**: Security module for monitoring suspicious activity and hardened checks for known attack vectors.
- **Copilot (`class-copilot.php` / `code-assistant/`)**: Developer-focused assistant utilities and lightweight code helpers used for diagnostics and developer workflows.
- **Airbag (`class-airbag.php`)**: Runtime protective guard rails for dangerous operations — designed to reduce accidental site-breaking changes.
- **Beak Armor / PlagueDr Core (`beak-armor.php`, `plaguedr-core.php`)**: Core helpers, bootstrapping, and shared utility functions used across modules.
- **Leech Drain Cache (`leech-drain-cache.php`)**: Cache management helpers to safely flush or drain caches during diagnostic runs.
- **Optional Modules (gallery, banner/affiliate, etc.)**: Loaded only when present. The plugin now includes safe guards and stubs so missing optional modules won't fatal-error on activation.

**Why this makes Cured Hosting better**
- **Less downtime**: Automatic detection of fatal errors and preflight snapshot rollback reduce time-to-recovery after a bad plugin activation.
- **Faster debugging**: Telemetry (memory, crash logs, snapshots) gives hosting engineers and site owners immediate context for root-cause analysis.
- **Improved performance**: The media pipeline and cache-drain helpers reduce client payloads and make background optimization feasible without blocking front-end delivery.
- **Stronger safety for production changes**: `Airbag` and conservative activation hooks help prevent destructive operations from running without safeguards.
- **SEO readiness**: Built-in SEO defaults and an admin UI make it easy to ship SEO metadata that works with modern social previews and search schema.
- **Developer ergonomics**: `Copilot` and the code-assistant utilities speed troubleshooting and iterative fixes for developers and support engineers.
- **Modular and safe**: Optional features are conditionally required or stubbed so hosts can deploy the package without needing every optional dependency present.

**Installation & operational notes**
- The package is safe to install as a regular plugin. Do not activate both an MU copy and the plugins copy simultaneously — doing so can double-load code and cause conflicts.
- If a hosted site shows a PHP fatal after activation, check the server PHP error log and the `plaguedr_resolved_clash_log` option (set by the diagnostics engine) for the captured error message.
- Premium features (file integrity scans, advanced schema) are gated by license flags and will report `LOCKED` when not active.

**Where to look next**
- Admin UI: Use the SEO settings page to configure organization and Open Graph defaults.
- Logs & options: Inspect WordPress options `plaguedr_pre_flight_snapshot` and `plaguedr_resolved_clash_log` for snapshots and last captured fatal.
- For troubleshooting: provide PHP/Apache/Nginx error logs alongside the plugin's resolved clash log to speed support.

If you want this converted into a user-facing admin page (WP admin screen) or a prettier HTML readme, tell me and I can scaffold it.
