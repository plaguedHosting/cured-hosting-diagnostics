<?php
/**
 * PlagueDr AI Copilot Engine
 * Connects the WP dashboard to an upstream AI API for secure, live code drafting.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class PlagueDr_Copilot {

    // Pulls your secret API key safely into the transaction node
    // No more raw keys stored in your Git history!
    private static $api_key = '';

    public static function init() {
        // Dynamically pull the key from a secure constant or WP option
        self::$api_key = defined( 'OPENAI_API_KEY' ) ? OPENAI_API_KEY : get_option( 'plaguedr_openai_key', '' );

        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'wp_ajax_plaguedr_draft_code', array( __CLASS__, 'handle_code_generation' ) );
    }

    public static function register_settings() {
        register_setting( 'plaguedr_code_assistant_group', 'plaguedr_openai_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
    }

    public static function register_admin_menu() {
        add_options_page(
            'PlagueDr Code Assistant',
            'Code Assistant',
            'manage_options',
            'plaguedr-code-assistant-settings',
            array( __CLASS__, 'render_admin_page' )
        );
    }

    public static function render_admin_page() {
        $api_key = get_option( 'plaguedr_openai_key', '' );
        $status = self::is_premium_active() ? 'Premium license active' : 'Premium license required';
        ?>
        <div class="wrap">
            <h1>PlagueDr Code Assistant</h1>
            <p>Code Assistant is designed to securely generate and audit code using your OpenAI API key.</p>
            <p><strong>Status:</strong> <?php echo esc_html( $status ); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields( 'plaguedr_code_assistant_group' ); ?>
                <?php do_settings_sections( 'plaguedr_code_assistant_group' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">OpenAI API Key</th>
                        <td>
                            <input type="text" name="plaguedr_openai_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
                            <p class="description">Enter your OpenAI API key or define the <code>OPENAI_API_KEY</code> constant in wp-config.php.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Save Code Assistant Settings' ); ?>
            </form>
        </div>
        <?php
    }

    private static function get_api_key() {
        self::$api_key = defined( 'OPENAI_API_KEY' ) ? OPENAI_API_KEY : get_option( 'plaguedr_openai_key', '' );
        return self::$api_key;
    }

    private static function is_premium_active() {
        return class_exists( 'PlagueDr_Diagnostics' ) && PlagueDr_Diagnostics::is_premium_active();
    }
    /**
     * Handles the incoming AJAX request from your "Draft Code" button
     */
    public static function handle_code_generation() {
        // Security check: Verify the request is coming from an authorized administrator
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized node access.' ), 403 );
        }

        // Grab the prompt the user typed into the input field
        $user_prompt = isset( $_POST['prompt'] ) ? sanitize_text_field( $_POST['prompt'] ) : '';

        if ( empty( $user_prompt ) ) {
            wp_send_json_error( array( 'message' => 'The prompt matrix is empty.' ), 400 );
        }

        if ( ! self::is_premium_active() ) {
            wp_send_json_error( array( 'message' => 'Code Assistant requires an active premium license.' ), 403 );
        }

        $api_key = self::get_api_key();
        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => 'OpenAI API key not configured. Please set it in the Code Assistant settings.' ), 400 );
        }

        self::$api_key = $api_key;

        // Call the external AI provider to compile the code snippet
        $generated_code = self::call_ai_api_provider( $user_prompt );

        // Now accurately catches both connection failures AND OpenAI platform errors
        if ( is_wp_error( $generated_code ) ) {
            wp_send_json_error( array( 'message' => 'API Node Error: ' . $generated_code->get_error_message() ), 400 );
        }

        // Return the raw code block straight back to the dashboard text area
        wp_send_json_success( array( 'code' => $generated_code ) );
    }

    /**
     * Executes a secure, server-side POST request to fetch raw code from the AI model
     */
    private static function call_ai_api_provider( $prompt ) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $body = array(
            'model' => 'gpt-4o-mini', // High-accuracy, fast code synthesis model
            'messages' => array(
                array(
                    'role'    => 'system',
                    'content' => 'You are a senior WordPress core engineer. Output ONLY raw, clean PHP/JS code blocks without markdown wrapping or conversational introductions.'
                ),
                array(
                    'role'    => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.2 // Keeps code strictly adhering to formatting standards
        );

        $response = wp_remote_post( $url, array(
            'timeout'   => 30,
            'headers'   => array(
                'Authorization' => 'Bearer ' . self::$api_key,
                'Content-Type'  => 'application/json'
            ),
            'body'      => json_encode( $body )
        ));

        // Catch base layer network failures (e.g., DNS down, timeout)
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $data          = json_decode( $response_body, true );

        // Catch bad HTTP responses or platform level restrictions (like invalid keys)
        if ( $response_code !== 200 ) {
            $error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown platform communication error.';
            return new WP_Error( 'api_response_error', $error_msg );
        }
        
        // Return parsed code cleanly if the matrix array is structurally intact
        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            return $data['choices'][0]['message']['content'];
        }
        
        return new WP_Error( 'json_parse_error', 'Failed to properly decode the response payload structure.' );
    }
}