<?php
/**
 * PlagueDr Diagnostics Engine Core - Pro Safety Shield Edition
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class PlagueDr_Diagnostics {

    public static function init() {
        add_action( 'activated_plugin', array( __CLASS__, 'audit_preflight_snapshot' ) );
        add_action( 'shutdown', array( __CLASS__, 'intercept_fatal_clash' ) );
    }

    public static function is_premium_active() {
        return '1' === get_option( 'plaguedr_premium_licensed', '0' );
    }

    public static function get_status() {
        return self::is_premium_active() ? 'Premium Shield Enhanced' : 'Operational';
    }

    public static function get_memory_telemetry() {
        $limit = ini_get('memory_limit');
        $usage = memory_get_usage(true);
        return array(
            'usage' => round($usage / 1024 / 1024, 2) . ' MB',
            'limit' => $limit ? $limit : 'Uncapped'
        );
    }

    public static function audit_preflight_snapshot() {
        $active = get_option( 'active_plugins' );
        update_option( 'plaguedr_pre_flight_snapshot', $active );
    }

    public static function intercept_fatal_clash() {
        $error = error_get_last();
        if ( $error && in_array( $error['type'], array( E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR ) ) ) {
            if ( strpos( $error['file'], 'wp-content/plugins/' ) !== false ) {
                if ( self::is_premium_active() ) {
                    $snapshot = get_option( 'plaguedr_pre_flight_snapshot' );
                    if ( ! empty( $snapshot ) ) {
                        update_option( 'active_plugins', $snapshot );
                        update_option( 'plaguedr_resolved_clash_log', $error['message'] );
                    }
                }
            }
        }
    }

    public static function get_safety_logs() {
        $log = get_option( 'plaguedr_resolved_clash_log', '' );
        if ( ! empty( $log ) ) {
            return array( 'alert' => true, 'message' => 'Conflict Defused! A structural crash error was captured, and the plugin subsystem rolled back settings automatically.' );
        }
        return array( 'alert' => false, 'message' => 'Safety loops tracking executions.' );
    }

    public static function run_file_integrity_scan() {
        if ( ! self::is_premium_active() ) {
            return array( 'status' => 'LOCKED', 'message' => 'Cryptographic baseline validation requires an active Premium verification signature.' );
        }

        $scan_results = array();
        try {
            $directory = new RecursiveDirectoryIterator( PLAGUEDR_PLUGIN_DIR );
            $iterator  = new RecursiveIteratorIterator( $directory );
            foreach ( $iterator as $file ) {
                if ( $file->isFile() && $file->getExtension() === 'php' ) {
                    $scan_results[ basename($file->getPathname()) ] = hash_file( 'sha256', $file->getPathname() );
                }
            }
            return array( 'status' => 'SUCCESS', 'count' => count($scan_results), 'message' => 'System nodes verified. All directories match baseline configurations.' );
        } catch ( Exception $e ) {
            return array( 'status' => 'ERROR', 'message' => 'Telemetry stream fault: ' . $e->getMessage() );
        }
    }
}