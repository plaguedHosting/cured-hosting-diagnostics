<?php
/**
 * PlagueDr SEO Options Form and Setting Controller
 * Handles option definitions and core configurations.
 *
 * @package PlagueDr_Toolset
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class PlagueDr_SEO_Admin {

    /**
     * Register configuration management fields within the system option table.
     */
    public static function init_settings() {
        register_setting( 'plaguedr_seo_settings_group', 'plaguedr_zencoder_api_token', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );

        register_setting( 'plaguedr_seo_settings_group', 'plaguedr_seo_default_og_image', array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => '',
        ) );

        register_setting( 'plaguedr_seo_settings_group', 'plaguedr_seo_organization_name', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );

        register_setting( 'plaguedr_seo_settings_group', 'plaguedr_seo_organization_twitter', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );

        register_setting( 'plaguedr_seo_settings_group', 'plaguedr_seo_enable_advanced_schema', array(
            'type'              => 'boolean',
            'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
            'default'           => false,
        ) );
    }

    public static function sanitize_checkbox( $value ) {
        return filter_var( $value, FILTER_VALIDATE_BOOLEAN ) ? true : false;
    }

    public static function register_admin_menu() {
        add_options_page(
            'PlagueDr Media Pipeline',
            'Media Pipeline',
            'manage_options',
            'plaguedr-media-pipeline',
            array( __CLASS__, 'render_admin_page' )
        );
    }

    public static function render_admin_page() {
        $api_key              = get_option( 'plaguedr_zencoder_api_token', '' );
        $default_og_image     = get_option( 'plaguedr_seo_default_og_image', '' );
        $organization_name    = get_option( 'plaguedr_seo_organization_name', get_bloginfo( 'name' ) );
        $organization_twitter = get_option( 'plaguedr_seo_organization_twitter', '' );
        $enable_advanced_schema = get_option( 'plaguedr_seo_enable_advanced_schema', false );
        $premium = class_exists( 'PlagueDr_Diagnostics' ) && PlagueDr_Diagnostics::is_premium_active();
        ?>
        <div class="wrap">
            <h1>PlagueDr Media Pipeline Settings</h1>
            <p>Configure your premium video optimization pipeline.</p>
            <p><strong>Premium status:</strong> <?php echo $premium ? 'Active' : 'Inactive - premium required'; ?></p>
            <form method="post" action="options.php">
                <?php settings_fields( 'plaguedr_seo_settings_group' ); ?>
                <?php do_settings_sections( 'plaguedr_seo_settings_group' ); ?>
                <?php wp_nonce_field( 'plaguedr_save_seo_tokens', 'plaguedr_zencoder_token_nonce' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Zencoder API Token</th>
                        <td>
                            <input type="text" name="plaguedr_zencoder_api_token" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
                            <p class="description">Enter your Zencoder API token to enable remote video optimization.</p>
                        </td>
                    </tr>
                </table>

                <?php if ( $premium ) : ?>
                    <h2>Premium SEO Enhancements</h2>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Default Open Graph Image</th>
                            <td>
                                <input type="url" name="plaguedr_seo_default_og_image" value="<?php echo esc_attr( $default_og_image ); ?>" class="regular-text" />
                                <p class="description">Fallback image for social previews when a page does not provide its own featured image.</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Organization Name</th>
                            <td>
                                <input type="text" name="plaguedr_seo_organization_name" value="<?php echo esc_attr( $organization_name ); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Organization Twitter</th>
                            <td>
                                <input type="text" name="plaguedr_seo_organization_twitter" value="<?php echo esc_attr( $organization_twitter ); ?>" class="regular-text" placeholder="@YourBrand" />
                                <p class="description">Optional Twitter handle for Twitter card metadata.</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Enable Advanced Schema</th>
                            <td>
                                <label><input type="checkbox" name="plaguedr_seo_enable_advanced_schema" value="1" <?php checked( $enable_advanced_schema ); ?> /> Yes, deploy premium JSON-LD schema on the site.</label>
                                <p class="description">Adds Organization, WebSite, breadcrumb, and Article schema for premium search presentation.</p>
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>

                <?php submit_button( 'Save Media Pipeline Settings' ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Safely process administrative settings layout configurations.
     */
    public static function save_seo_configurations() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! isset( $_POST['plaguedr_zencoder_token_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['plaguedr_zencoder_token_nonce'] ) ), 'plaguedr_save_seo_tokens' ) ) {
            return;
        }

        if ( isset( $_POST['plaguedr_zencoder_api_token'] ) ) {
            update_option( 'plaguedr_zencoder_api_token', sanitize_text_field( wp_unslash( $_POST['plaguedr_zencoder_api_token'] ) ) );
        }

        if ( isset( $_POST['plaguedr_seo_default_og_image'] ) ) {
            update_option( 'plaguedr_seo_default_og_image', esc_url_raw( wp_unslash( $_POST['plaguedr_seo_default_og_image'] ) ) );
        }

        if ( isset( $_POST['plaguedr_seo_organization_name'] ) ) {
            update_option( 'plaguedr_seo_organization_name', sanitize_text_field( wp_unslash( $_POST['plaguedr_seo_organization_name'] ) ) );
        }

        if ( isset( $_POST['plaguedr_seo_organization_twitter'] ) ) {
            update_option( 'plaguedr_seo_organization_twitter', sanitize_text_field( wp_unslash( $_POST['plaguedr_seo_organization_twitter'] ) ) );
        }

        $enable_schema = isset( $_POST['plaguedr_seo_enable_advanced_schema'] ) ? true : false;
        update_option( 'plaguedr_seo_enable_advanced_schema', $enable_schema );
    }
}

// Hook options into administration engine structures natively
add_action( 'admin_init', array( 'PlagueDr_SEO_Admin', 'init_settings' ) );
add_action( 'admin_init', array( 'PlagueDr_SEO_Admin', 'save_seo_configurations' ) );
add_action( 'admin_menu', array( 'PlagueDr_SEO_Admin', 'register_admin_menu' ) );