<?php
/**
 * PlagueDr Security Sentinel Module
 * Handles high-efficiency login obfuscation and XML-RPC blocking.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class PlagueDr_Sentinel {

    // Define your secret custom login slug here
    private static $secret_slug = 'enter-the-lab';

    public static function init() {
        // Block XML-RPC hits instantly at the earliest possible moment
        add_action( 'plugins_loaded', array( __CLASS__, 'block_xmlrpc' ), 1 );
        
        // Intercept requests right when the login framework starts to process
        add_action( 'login_init', array( __CLASS__, 'protect_login_gateway' ), 1 );
        add_filter( 'site_url', array( __CLASS__, 'rewrite_login_urls' ), 10, 4 );
    }

    /**
     * Hard-blocks XML-RPC with a lightweight 403 Forbidden exit before processing
     */
    public static function block_xmlrpc() {
        if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
            status_header( 403 );
            header( 'Content-Type: text/plain' );
            die( 'Forbidden: XML-RPC is permanently disabled on this node.' );
        }
    }

    /**
     * Enforces the secret slug gateway and throws a 404 for standard wp-login.php hits
     */
    public static function protect_login_gateway() {
        // If they passed the secret slug as a query parameter (?enter-the-lab), let them pass safely
        if ( isset( $_GET[ self::$secret_slug ] ) ) {
            return;
        }
        
        // Otherwise, reject the bot/attacker with a clean 404 page layout
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        get_template_part( '404' );
        die();
    }

    /**
     * Ensures any native WordPress login link generation routes to the secret path
     */
    public static function rewrite_login_urls( $url, $path, $scheme, $blog_id ) {
        if ( 'wp-login.php' === $path && 'login' === $scheme ) {
            return site_url( 'wp-login.php?' . self::$secret_slug, $scheme );
        }
        return $url;
    }
}