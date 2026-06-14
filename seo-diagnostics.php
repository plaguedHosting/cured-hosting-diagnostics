<?php
/**
 * Cured SEO & Diagnostic Utility Module.
 * Lightweight server diagnostics and automated SEO metadata generation without database bloat.
 */

if (!defined('ABSPATH')) exit;

class CuredSeoDiagnostics {

    public function __construct() {
        // --- SEO & Performance Hooks ---
        add_action('wp_head', array($this, 'inject_clean_seo_meta'), 1);
        add_action('init', array($this, 'purge_wordpress_bloat'));

        // --- Diagnostic Hooks ---
        add_action('admin_footer_text', array($this, 'render_dashboard_diagnostics'));
    }

    public function purge_wordpress_bloat() {
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
    }

    public function inject_clean_seo_meta() {
        global $post;
        
        $title = get_bloginfo('name');
        $description = get_bloginfo('description');
        // Safety: sanitize request URI before building URL to avoid trusting raw server values
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
        $url = esc_url_raw( home_url( $request_uri ) );

        if (is_singular()) {
            $title = get_the_title() . ' | ' . $title;
            if (!empty($post->post_excerpt)) {
                $description = esc_attr(strip_tags($post->post_excerpt));
            } elseif (!empty($post->post_content)) {
                $description = wp_html_excerpt($post->post_content, 155) . '...';
            }
        }

        echo "\n\n";
        echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
        
        // Open Graph Meta
        echo '<meta property="og:type" content="' . (is_singular() ? 'article' : 'website') . '" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";

        // Optional: Open Graph Image
        if (is_singular() && has_post_thumbnail($post->ID)) {
            echo '<meta property="og:image" content="' . esc_url(get_the_post_thumbnail_url($post->ID, 'full')) . '" />' . "\n";
        }
        echo "\n\n";
    }

    public function render_dashboard_diagnostics() {
        $memory_usage = size_format(memory_get_usage(true));
        $memory_limit = ini_get('memory_limit');
        $php_version = phpversion();
        
        $load = 'N/A';
        if (function_exists('sys_getloadavg')) {
            $load_array = sys_getloadavg();
            $load = isset($load_array[0]) ? round($load_array[0], 2) : 'N/A';
        }

        echo '<span style="font-family: monospace; color: #888;">';
        echo '[ SANATORIUM HEALTH: PHP ' . esc_html($php_version) . ' | RAM: ' . esc_html($memory_usage) . ' / ' . esc_html($memory_limit) . ' | Load: ' . esc_html($load) . ' ]';
        echo '</span>';
    }
}

new CuredSeoDiagnostics();