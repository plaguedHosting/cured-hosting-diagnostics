<?php
// Sanctum Guard: Prevent direct asset scanning
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cure 04: Miasma Shield (Anti-Scraping Bot Wall)
 * Core Pillar: SPEED, PRIVACY & SERVER RESILIENCE
 */
final class PlagueDr_Miasma_Shield {

    private static $instance = null;
    
    // Master Doctor Whitelist: Your Static IP
    private $doctor_ip = '174.164.40.108';

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Enforce immediate containment check before any data parsing happens
        add_action( 'init', array( $this, 'intercept_malice' ), 1 );

        // Inject the invisible honeypot trap into the site footer
        add_action( 'wp_footer', array( $this, 'plant_honeypot_trap' ) );
    }

    /**
     * Unified Early Interception Stack
     */
    public function intercept_malice() {
        $visitor_ip = $this->get_clean_ip();

        // --- THE DOCTOR'S IMMUNITY ---
        // Failsafe: Never block the Doctor or the server's loopback address
        if ( $visitor_ip === $this->doctor_ip || in_array( $visitor_ip, array( '127.0.0.1', '::1' ) ) ) {
            return;
        }

        // FIX 1: Bypass for WP-CLI, local Cron, and internal server requests
        if ( ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
            return;
        }

        $ip_hash = md5( $visitor_ip );

        // Tier 0: Immediate Local Isolation Chamber Check
        if ( get_transient( 'pd_blocked_' . $ip_hash ) ) {
            $this->vaporize_request();
        }

        // Tier 1: Catch Honeypot Thieves
        if ( isset( $_GET['plaguedr_remedy'] ) && $_GET['plaguedr_remedy'] === 'apothecary_test' ) {
            set_transient( 'pd_blocked_' . $ip_hash, true, DAY_IN_SECONDS );
            $this->vaporize_request();
        }

        // Tier 2: Signature Auditing
        if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            $agent = strtolower( $_SERVER['HTTP_USER_AGENT'] );
            
            $predators = array(
                'bytespider', 'gptbot', 'claudebot', 'anthropic-ai', 
                'imagesifter', 'ccbot', 'cohere-ai', 'omgili', 'diffbot',
                'scrapebox', 'python-requests', 'sqlmap', 'nmap'
            );

            // Check for generic curl/wget
            if ( ( strpos( $agent, 'curl/' ) !== false || strpos( $agent, 'wget/' ) !== false ) && empty( $_POST ) ) {
                set_transient( 'pd_blocked_' . $ip_hash, true, HOUR_IN_SECONDS );
                $this->vaporize_request();
            }

            foreach ( $predators as $predator ) {
                if ( strpos( $agent, $predator ) !== false ) {
                    set_transient( 'pd_blocked_' . $ip_hash, true, DAY_IN_SECONDS );
                    $this->vaporize_request();
                }
            }
        }
    }

    /**
     * Tier 3: The Alchemical Honeypot
     */
    public function plant_honeypot_trap() {
        $honey_url = home_url( '/?plaguedr_remedy=apothecary_test' );
        echo "\n\n";
        echo '<div style="display:none !important; position:absolute; top:-9999px; left:-9999px; width:0; height:0; overflow:hidden;" aria-hidden="true">';
        echo '<a href="' . esc_url( $honey_url ) . '" rel="nofollow">Access Hidden Archive Portal</a>';
        echo '</div>' . "\n";
    }

    /**
     * Vaporize Engine
     */
    private function vaporize_request() {
        status_header( 429 );
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
        header( 'Retry-After: 86400' ); 
        exit( 'Miasma Filtered: Automated requests from this signature are temporarily contained.' );
    }

    /**
     * Helper to get accurate visitor IP addresses cleanly
     */
    private function get_clean_ip() {
        if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        
        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
            $possible_ip = trim( $ips[0] );
            if ( filter_var( $possible_ip, FILTER_VALIDATE_IP ) ) {
                return $possible_ip;
            }
        }
        
        return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    }
}

function plaguedr_miasma_init() {
    return PlagueDr_Miasma_Shield::instance();
}
plaguedr_miasma_init();