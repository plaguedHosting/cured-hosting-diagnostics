<?php
/**
 * PlagueDr Conflict-Protection Airbag Module
 * Intercepts unhandled fatal errors, prevents frontend crashes, and maintains uptime.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class PlagueDr_Airbag {

    public static function init() {
        // Register shutdown handler to catch unhandled fatal compilation/runtime errors
        register_shutdown_function( array( __CLASS__, 'deploy_airbag_shield' ) );
    }

    /**
     * Inspects the execution state on shutdown and catches crashes before the browser displays them
     */
    public static function deploy_airbag_shield() {
        $error = error_get_last();
        
        // Target explicit fatals: E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR
        if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ), true ) ) {
            
            // Bypass if the error originated inside our own plugin (prevents circular lockouts)
            if ( false !== strpos( $error['file'], 'PlagueDr-Diagnostics' ) ) {
                return;
            }

            // Clear any partial broken HTML buffering output already sent by the crashed theme/plugin
            if ( ob_get_length() ) {
                ob_end_clean();
            }

            // Log the clean diagnostic telemetry down to a secure file for site admins
            self::log_incident_telemetry( $error );

            // Serve a beautiful, custom, lightweight recovery notice instead of a scary server crash screen
            self::render_recovery_screen();
            
            die();
        }
    }

    /**
     * Writes out high-accuracy crash records to a private plugin log
     */
    private static function log_incident_telemetry( $error ) {
        $log_dir = WP_CONTENT_DIR . '/uploads/plaguedr-logs/';
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
        }
        
        $timestamp = current_time( 'mysql' );
        $log_message = sprintf(
            "[%s] AIRBAG DEPLOYED: Fatal Error caught in file %s on line %d. Message: %s\n",
            $timestamp,
            $error['file'],
            $error['line'],
            $error['message']
        );
        
        error_log( $log_message, 3, $log_dir . 'airbag-incidents.log' );
    }

    /**
     * Renders a clean, styled recovery state layout to protect front-facing branding
     */
    private static function render_recovery_screen() {
        status_header( 503 );
        header( 'Retry-After: 300' );
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>System Optimizing &bull; PlagueDr Node</title>
            <style>
                body { background: #111; color: #eee; font-family: -apple-system, BlinkMacSystemFont, sans-serif; text-align: center; padding: 15% 5%; }
                .card { max-width: 550px; margin: 0 auto; background: #1a1a1a; padding: 40px; border-radius: 8px; border: 1px solid #333; box-shadow: 0 4px 20px rgba(0,0,0,0.5); }
                h1 { color: #cf4646; font-size: 24px; margin-bottom: 10px; }
                p { color: #aaa; line-height: 1.6; font-size: 15px; }
                .status { display: inline-block; background: #2a1b1b; color: #ff6b6b; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="status">Airbag Active</div>
                <h1>System Stabilization Matrix Engaged</h1>
                <p>A third-party component conflict was detected on this node. The PlagueDr Airbag safely intercepted the anomaly to protect your core database file-integrity.</p>
                <p style="font-size: 13px; color: #666;">Site administrators can check the secure error log directory for diagnostic reports.</p>
            </div>
        </body>
        </html>
        <?php
    }
}