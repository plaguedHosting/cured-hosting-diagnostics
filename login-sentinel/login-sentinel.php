<?php
// Sanctum Guard: Prevent direct asset scanning
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cure 01: Login Sentinel Firewall
 * Core Pillar: SEKURE, SPEED & PRIVACY
 */
final class PlagueDr_Login_Sentinel {

    private static $instance = null;
    private $secret_key   = 'apothecary'; // The custom URL parameter key
    private $secret_value = 'open';       // The custom URL parameter value
    
    // Master Doctor Whitelist: Your Static IP
    private $doctor_ip    = '174.164.40.108'; 

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Intercept XML-RPC brute force vectors before core processing runs
        add_action( 'init', array( $this, 'vaporize_xmlrpc' ), 1 );

        // Protect the traditional login threshold
        add_action( 'init', array( $this, 'audit_login_access' ), 2 );
        
        // Ensure core WP links (password resets, emails) naturally include the secret key
        add_filter( 'site_url', array( $this, 'inject_secret_key' ), 10, 4 );
        add_filter( 'network_site_url', array( $this, 'inject_secret_key' ), 10, 3 );
        add_filter( 'wp_redirect', array( $this, 'secure_redirects' ), 10, 2 );
    }

    /**
     * Tier 1: XML-RPC Demolition
     */
    public function vaporize_xmlrpc() {
        // Master Bypass for the Doctor
        if ( $this->get_visitor_ip() === $this->doctor_ip ) {
            return;
        }

        if ( defined( 'XMLRPC_REQUEST' ) || ( isset( $_SERVER['SCRIPT_NAME'] ) && strpos( $_SERVER['SCRIPT_NAME'], 'xmlrpc.php' ) !== false ) ) {
            $this->isolate_and_vaporize();
        }
    }

    /**
     * Tier 2: Threshold Auditing
     */
    public function audit_login_access() {
        global $pagenow;

        if ( isset( $pagenow ) && $pagenow === 'wp-login.php' ) {
            
            $visitor_ip = $this->get_visitor_ip();

            // BYPASS 1: MASTER DOCTOR WHITELIST
            if ( $visitor_ip === $this->doctor_ip ) {
                return; 
            }

            // BYPASS 2: MY.20I HOST PANEL REFERER
            $referer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
            if ( strpos( $referer, 'my.20i' ) !== false ) {
                return; 
            }

            // FIREWALL CHECK: The secret key must be present for everyone else.
            if ( ! isset( $_GET[ $this->secret_key ] ) || $_GET[ $this->secret_key ] !== $this->secret_value ) {
                
                $ip_hash = md5( $visitor_ip );
                
                // If they are already in the 24-hour master lock, vaporize instantly.
                if ( get_transient( 'pd_blocked_' . $ip_hash ) ) {
                    $this->isolate_and_vaporize();
                }

                $strikes = (int) get_transient( 'pd_sentinel_strike_' . $ip_hash );
                $strikes++;
                
                if ( $strikes >= 3 ) {
                    set_transient( 'pd_blocked_' . $ip_hash, true, DAY_IN_SECONDS );
                } else {
                    set_transient( 'pd_sentinel_strike_' . $ip_hash, $strikes, HOUR_IN_SECONDS );
                }

                $this->serve_clean_404();
            }
        }
    }

    /**
     * Seamless Integration: Injects the secret key into core WordPress login URLs.
     */
    public function inject_secret_key( $url, $path, $scheme, $blog_id = null ) {
        if ( strpos( $url, 'wp-login.php' ) !== false && ! is_user_logged_in() ) {
            return add_query_arg( $this->secret_key, $this->secret_value, $url );
        }
        return $url;
    }

    /**
     * Ensures native WP redirects maintain the sanctum key
     */
    public function secure_redirects( $location, $status ) {
        if ( strpos( $location, 'wp-login.php' ) !== false ) {
            $location = add_query_arg( $this->secret_key, $this->secret_value, $location );
        }
        return $location;
    }

    /**
     * Emits a standard structural 404 page screen
     */
    private function serve_clean_404() {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        include( get_query_template( '404' ) );
        exit;
    }

    /**
     * Immediate Connection Dropper
     */
    private function isolate_and_vaporize() {
        status_header( 403 );
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        exit( 'Sentinel Triggered: Resource vector explicitly disabled on this ecosystem.' );
    }

    /**
     * Clean IP Grabber
     */
    private function get_visitor_ip() {
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
        return $_SERVER['REMOTE_ADDR'];
    }
}

function plaguedr_sentinel_init() {
    return PlagueDr_Login_Sentinel::instance();
}
plaguedr_sentinel_init();