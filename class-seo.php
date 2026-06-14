<?php
/**
 * PlagueDr SEO & Media Optimization Engine Core
 * Handles header sanitization, metadata protection, and cloud media encoding gateways.
 *
 * @package PlagueDr_Toolset
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class PlagueDr_SEO {

    /**
     * Initialize the core SEO and Media filters/actions.
     */
    public static function init() {
        // Core Header Sanitization Hooks
        add_action( 'init', array( __CLASS__, 'purge_header_bloat' ) );
        add_action( 'wp_head', array( __CLASS__, 'inject_clean_meta_tags' ), 1 );
        add_action( 'wp_head', array( __CLASS__, 'deploy_schema_tier' ), 100 );
        
    }

    /**
     * Return core tier initialization status.
     *
     * @return string Engine state token.
     */
    public static function get_status() {
        return 'Active';
    }

    /**
     * Strips generic WordPress tracking tokens, shortlinks, and generator version signatures.
     */
    public static function purge_header_bloat() {
        remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
        remove_action( 'wp_head', 'feed_links', 2 );
        remove_action( 'wp_head', 'feed_links_extra', 3 );
        remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
        remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
        remove_action( 'wp_head', 'wp_oembed_add_host_js' );
        remove_action( 'wp_head', 'wp_generator' );
    }

    /**
     * Injects highly optimized, lightweight standard metadata fallbacks.
     */
    public static function inject_clean_meta_tags() {
        $description = '';
        $title = self::get_page_title();
        $url = esc_url( self::get_current_url() );

        if ( is_singular() ) {
            global $post;
            $description = get_the_excerpt( $post ) ?: wp_strip_all_tags( wp_trim_words( $post->post_content, 30, '...' ) );
        } elseif ( is_home() || is_front_page() ) {
            $description = get_bloginfo( 'description' );
        }

        $description = trim( $description );
        if ( $description ) {
            echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
        }

        echo '<meta name="robots" content="index, follow, max-image-preview:large">' . "\n";

        if ( self::is_premium_active() ) {
            $og_image = self::get_og_image_url();
            echo '<meta property="og:type" content="' . ( is_singular() ? 'article' : 'website' ) . '">' . "\n";
            echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
            echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
            echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
            echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
            if ( $og_image ) {
                echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
            }
            echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
            $twitter = get_option( 'plaguedr_seo_organization_twitter', '' );
            if ( $twitter ) {
                echo '<meta name="twitter:site" content="' . esc_attr( $twitter ) . '">' . "\n";
            }
        }
    }

    /**
     * Handles Automated Premium Schema Injection validation checks.
     */
    public static function deploy_schema_tier() {
        if ( ! self::is_premium_active() || ! get_option( 'plaguedr_seo_enable_advanced_schema', false ) ) {
            return;
        }

        $org_name = get_option( 'plaguedr_seo_organization_name', get_bloginfo( 'name' ) );
        $home_url = esc_url( home_url( '/' ) );
        $logo_url = self::get_og_image_url();

        $schema = array(
            '@context' => 'https://schema.org',
            '@graph'   => array(
                array(
                    '@type' => 'Organization',
                    '@id'   => $home_url . '#organization',
                    'name'  => $org_name,
                    'url'   => $home_url,
                    'logo'  => $logo_url ? $logo_url : '',
                ),
                array(
                    '@type' => 'WebSite',
                    '@id'   => $home_url . '#website',
                    'url'   => $home_url,
                    'name'  => get_bloginfo( 'name' ),
                    'publisher' => array(
                        '@id' => $home_url . '#organization',
                    ),
                ),
            ),
        );

        if ( is_singular() ) {
            global $post;
            $schema['@graph'][] = array(
                '@type' => 'Article',
                '@id' => esc_url( get_permalink( $post ) ) . '#article',
                'headline' => get_the_title( $post ),
                'description' => get_the_excerpt( $post ) ?: wp_strip_all_tags( wp_trim_words( $post->post_content, 30, '...' ) ),
                'datePublished' => get_the_date( 'c', $post ),
                'dateModified' => get_the_modified_date( 'c', $post ),
                'author' => array(
                    '@type' => 'Person',
                    'name' => get_the_author_meta( 'display_name', $post->post_author ),
                ),
                'publisher' => array(
                    '@type' => 'Organization',
                    'name' => $org_name,
                    'logo' => array(
                        '@type' => 'ImageObject',
                        'url' => $logo_url,
                    ),
                ),
                'mainEntityOfPage' => esc_url( get_permalink( $post ) ),
            );

            $breadcrumbs = array(
                '@type' => 'BreadcrumbList',
                'itemListElement' => array(
                    array(
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => $home_url,
                    ),
                    array(
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => get_the_title( $post ),
                        'item' => esc_url( get_permalink( $post ) ),
                    ),
                ),
            );
            $schema['@graph'][] = $breadcrumbs;
        }

        echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
    }

    private static function is_premium_active() {
        return class_exists( 'PlagueDr_Diagnostics' ) && PlagueDr_Diagnostics::is_premium_active();
    }

    private static function get_current_url() {
        return home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) );
    }

    private static function get_page_title() {
        if ( is_singular() ) {
            return single_post_title( '', false );
        }
        return get_bloginfo( 'name' );
    }

    private static function get_og_image_url() {
        $default_image = get_option( 'plaguedr_seo_default_og_image', '' );
        if ( ! empty( $default_image ) ) {
            return esc_url_raw( $default_image );
        }

        if ( is_singular() && has_post_thumbnail() ) {
            return get_the_post_thumbnail_url( get_queried_object_id(), 'full' );
        }

        return ''; 
    }

    /**
     * Zencoder Media Optimization Gateway
     * Catches raw video files during upload and sends a web-optimized transcode job to Brightcove Zencoder.
     *
     * @param array  $upload  Array of upload data.
     * @param string $context The type of upload action.
     * @return array Modified upload metadata framework.
     */
    public static function intercept_media_upload_stream( $upload, $context ) {
        if ( strpos( $upload['type'], 'video/' ) !== false ) {
            if ( class_exists( 'PlagueDr_Diagnostics' ) && PlagueDr_Diagnostics::is_premium_active() ) {
                
                $zencoder_api_key = get_option( 'plaguedr_zencoder_api_token', '' );
                if ( empty( $zencoder_api_key ) ) {
                    return $upload; 
                }

                $api_url = 'https://app.zencoder.com/api/v2/jobs';
                $payload = array(
                    'input'   => $upload['url'],
                    'outputs' => array(
                        array(
                            'label'       => 'web-optimized-fluid-stream',
                            'format'      => 'mp4',
                            'video_codec' => 'h264',
                            'quality'     => 4,
                            'width'       => 1280,
                            'height'      => 720,
                            'aspect_mode' => 'preserve' // Hardlocks aspect ratio to eliminate layout distortion
                        )
                    )
                );

                wp_remote_post( $api_url, array(
                    'headers' => array(
                        'Content-Type'     => 'application/json',
                        'Zencoder-Api-Key' => $zencoder_api_key
                    ),
                    'body'    => json_encode( $payload ),
                    'timeout' => 15
                ));
            }
        }
        return $upload;
    }
}