<?php
/**
 * Theme support and asset registration for header
 */

if ( ! function_exists( 'chdp_theme_setup' ) ) :
    function chdp_theme_setup() {
        add_theme_support( 'custom-logo' );

        register_nav_menus( array(
            'menu-1' => __( 'Primary Menu', 'textdomain' ),
        ) );
    }
    add_action( 'after_setup_theme', 'chdp_theme_setup' );
endif;

if ( ! function_exists( 'chdp_enqueue_assets' ) ) :
    function chdp_enqueue_assets() {
        wp_enqueue_style( 'chdp-header', plugin_dir_url( __FILE__ ) . 'header.css', array(), '1.0' );
    }
    add_action( 'wp_enqueue_scripts', 'chdp_enqueue_assets' );
endif;
