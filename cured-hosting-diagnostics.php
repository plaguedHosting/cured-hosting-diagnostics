<?php
/**
 * Plugin Name: Cured Hosting Diagnostics
 * Plugin URI:  https://curedhosting.com
 * Description: Diagnostic tools and error logging for Cured Hosting servers.
 * Version:     1.0.0
 * Author:      Mike Crowley
 * Text Domain: cured-hosting
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CHD_PLUGIN_FILE', __FILE__ );
define( 'CHD_PATH', plugin_dir_path( __FILE__ ) );
// Backwards-compatible constant expected by older modules.
if ( ! defined( 'PLAGUEDR_PLUGIN_DIR' ) ) {
    define( 'PLAGUEDR_PLUGIN_DIR', CHD_PATH );
}

require_once CHD_PATH . 'class-diagnostics.php';
require_once CHD_PATH . 'class-seo.php';
require_once CHD_PATH . 'class-seo-admin.php';
require_once CHD_PATH . 'class-sentinel.php';
require_once CHD_PATH . 'class-media-pipeline.php';
require_once CHD_PATH . 'class-copilot.php';
// Optional gallery maker: only include if present in this release.
if ( file_exists( CHD_PATH . 'class-gallery-maker.php' ) ) {
    require_once CHD_PATH . 'class-gallery-maker.php';
}
// Optional affiliate/banner module: only include if present in this release.
if ( file_exists( CHD_PATH . 'class-banner-affiliate.php' ) ) {
    require_once CHD_PATH . 'class-banner-affiliate.php';
}
require_once CHD_PATH . 'class-airbag.php';
require_once CHD_PATH . 'beak-armor.php';
require_once CHD_PATH . 'leech-drain-cache.php';
require_once CHD_PATH . 'plaguedr-core.php';
// Admin info screen (optional): registers a Tools page showing INFO.md
if ( file_exists( CHD_PATH . 'admin-info.php' ) ) {
    require_once CHD_PATH . 'admin-info.php';
}
// Admin settings screen (enhanced): optional
if ( file_exists( CHD_PATH . 'admin-settings.php' ) ) {
    require_once CHD_PATH . 'admin-settings.php';
}

function cured_hosting_diagnostics_init() {
    if ( class_exists( 'PlagueDr_Diagnostics' ) ) {
        PlagueDr_Diagnostics::init();
    }
    if ( class_exists( 'PlagueDr_SEO' ) ) {
        PlagueDr_SEO::init();
    }
    if ( class_exists( 'PlagueDr_Sentinel' ) ) {
        PlagueDr_Sentinel::init();
    }
    if ( class_exists( 'PlagueDr_Media_Pipeline' ) ) {
        PlagueDr_Media_Pipeline::init();
    }
    if ( class_exists( 'PlagueDr_Copilot' ) ) {
        PlagueDr_Copilot::init();
    }
    if ( class_exists( 'PlagueDr_Airbag' ) ) {
        PlagueDr_Airbag::init();
    }
}

add_action( 'plugins_loaded', 'cured_hosting_diagnostics_init' );
