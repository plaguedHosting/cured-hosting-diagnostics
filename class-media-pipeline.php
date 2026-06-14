<?php
/**
 * PlagueDr Zencoder Media Pipeline
 * Intercepts video asset uploads and triggers distortion-free cloud optimization.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class PlagueDr_Media_Pipeline {

    public static function init() {
        add_filter( 'wp_handle_upload', array( __CLASS__, 'process_uploaded_video' ), 10, 2 );
    }

    private static function is_premium_active() {
        return class_exists( 'PlagueDr_Diagnostics' ) && PlagueDr_Diagnostics::is_premium_active();
    }

    private static function get_zencoder_api_key() {
        return get_option( 'plaguedr_zencoder_api_token', '' );
    }

    /**
     * Intercepts media uploads and routes video mime-types out to the cloud pipeline
     */
    public static function process_uploaded_video( $upload, $context ) {
        // Accept non-array context (WP sometimes passes a string like 'upload') and normalize.
        if ( ! self::is_premium_active() ) {
            return $upload;
        }
        if ( ! is_array( $context ) ) {
            $context = array( 'context' => (string) $context );
        }

        // Only target video files (e.g., mp4, mov, avi)
        if ( isset( $upload['type'] ) && strpos( $upload['type'], 'video/' ) !== false ) {
            $file_url  = $upload['url'];
            $file_path = $upload['file'];

            $api_key = self::get_zencoder_api_key();
            if ( empty( $api_key ) ) {
                return $upload;
            }

            // Trigger the cloud offload processing function
            self::dispatch_zencoder_job( $file_url, $file_path, $api_key );
        }

        return $upload;
    }

    /**
     * Assembles the asynchronous JSON instruction payload and sends it to the cloud transcoder API
     */
    private static function dispatch_zencoder_job( string $file_url, string $file_path, string $api_key ): void {
        $api_endpoint = 'https://app.zencoder.com/api/v2/jobs';

        // Prepare instruction data for distortion-free mobile optimization outputs
        $payload = array(
            'input'   => $file_url,
            'outputs' => array(
                array(
                    'label'       => 'web-optimized-mp4',
                    'format'      => 'mp4',
                    'video_codec' => 'h264',
                    'quality'     => 4, // Distortion-free balanced processing scale
                    'speed'       => 3,
                    'public'      => true
                )
            )
        );

        // Make an asynchronous remote post to keep execution speeds blazing fast for the user
        wp_remote_post( $api_endpoint, array(
            'method'    => 'POST',
            'blocking'  => false, // Non-blocking ensures the client dashboard never lags waiting for processing
            'headers'   => array(
                'Content-Type'      => 'application/json',
                'Zencoder-Api-Key'  => $api_key
            ),
            'body'      => json_encode( $payload )
        ));
    }
}