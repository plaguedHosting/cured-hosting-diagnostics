<?php
/**
 * Uninstall handler for PlagueDr Core (MU-friendly)
 * Created: 2026-06-13
 * Purpose: Attempt safe cleanup of scheduled events, transients, and options.
 */

// Typical uninstall entrypoint check. If not present, continue but run safely.
if ( defined( 'WP_UNINSTALL_PLUGIN' ) || ( defined( 'WP_UNINSTALL_PLUGIN' ) === false ) ) {
    // Try to include the core conductor to leverage its cleanup method.
    $core = __DIR__ . '/plaguedr-core.php';
    if ( file_exists( $core ) ) {
        include_once $core;
        if ( class_exists( 'PlagueDr_Core_Conductor' ) ) {
            try {
                $inst = PlagueDr_Core_Conductor::instance();
                if ( is_object( $inst ) && method_exists( $inst, 'cleanup_suite' ) ) {
                    $inst->cleanup_suite();
                }
            } catch ( Exception $e ) {
                // Fail silently; continue with fallback cleanup
            }
        }
    }

    // Fallback: attempt to delete known options and transients directly.
    if ( function_exists( 'delete_option' ) ) {
        $options = array(
            'pd_license_token',
            'pd_license_token_hash',
            'pd_is_pro',
            'pd_pixel_pure_quality',
            'pd_pixel_pure_max_width',
            'pd_pixel_pure_max_height',
            'pd_pixel_pure_watermark_text',
            'pd_pixel_pure_watermark_image',
            'pd_pixel_pure_watermark_opacity',
        );

        foreach ( $options as $opt ) {
            delete_option( $opt );
            if ( function_exists( 'is_multisite' ) && is_multisite() && function_exists( 'delete_site_option' ) ) {
                delete_site_option( $opt );
            }
        }
    }

    if ( function_exists( 'delete_transient' ) ) {
        delete_transient( 'pd_elite_proxy_pool' );
    }

    if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_unschedule_event' ) ) {
        $ts = wp_next_scheduled( 'wp_next_scheduled_harvester' );
        if ( $ts ) {
            wp_unschedule_event( $ts, 'wp_next_scheduled_harvester' );
        }
    }
}
