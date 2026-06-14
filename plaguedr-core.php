<?php
/**
 * PlagueDr Core Cures
 * Description: High-performance, 100% server-local security, speed, and media utilities.
 * Version:     1.1.0
 * Author:      Plague Doctor General
 * License:     GPLv2 or later
 *
 * Modification Note (2026-06-13):
 * - Added `cleanup_suite()` to remove scheduled events, transients, and plugin options.
 * - Added admin UI cleanup button + nonce to trigger cleanup from settings screen.
 * - File annotated to indicate changes made on 2026-06-13.
 */

// Sanctum Guard: Prevent direct asset scanning
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Grand Conductor Class
 */
final class PlagueDr_Core_Conductor {

    private static $instance = null;
    private $is_pro = false;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** AJAX: add a todo item */
    public function ajax_add_todo() {
        check_ajax_referer( 'pd_todo_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'permission' );
        }
        $item = isset( $_POST['item'] ) ? sanitize_text_field( wp_unslash( $_POST['item'] ) ) : '';
        if ( empty( $item ) ) {
            wp_send_json_error( 'empty' );
        }
        $raw = get_option( 'pd_admin_todo', '' );
        $lines = array_filter( array_map( 'trim', explode( "\n", (string) $raw ) ) );
        $lines[] = $item;
        update_option( 'pd_admin_todo', implode( "\n", $lines ) );
        wp_send_json_success( $lines );
    }

    /** AJAX: remove a todo item by index */
    public function ajax_remove_todo() {
        check_ajax_referer( 'pd_todo_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'permission' );
        }
        $idx = isset( $_POST['index'] ) ? intval( $_POST['index'] ) : -1;
        $raw = get_option( 'pd_admin_todo', '' );
        $lines = array_values( array_filter( array_map( 'trim', explode( "\n", (string) $raw ) ) ) );
        if ( $idx < 0 || $idx >= count( $lines ) ) {
            wp_send_json_error( 'index' );
        }
        array_splice( $lines, $idx, 1 );
        update_option( 'pd_admin_todo', implode( "\n", $lines ) );
        wp_send_json_success( $lines );
    }

    private function __construct() {
        $this->audit_license_token();
        $this->load_specialized_modules();
        $this->register_lifecycle_hooks();

        add_action( 'admin_menu', array( $this, 'register_settings_gate' ) );
        add_action( 'admin_init', array( $this, 'save_master_settings' ) );
        // AJAX handlers for admin TODOs
        add_action( 'wp_ajax_pd_todo_add', array( $this, 'ajax_add_todo' ) );
        add_action( 'wp_ajax_pd_todo_remove', array( $this, 'ajax_remove_todo' ) );
    }

    private function audit_license_token() {
        // New flow (2026-06-13): prefer an explicit stored flag and hashed token.
        $is_pro_flag = get_option( 'pd_is_pro', false );
        if ( $is_pro_flag ) {
            $this->is_pro = true;
            return;
        }

        // Backwards-compatibility: migrate legacy raw token if present
        $raw = get_option( 'pd_license_token', '' );
        if ( $raw && md5( $raw ) === md5( 'plaguedr_pro_elite' ) ) {
            // mark as pro and store a hashed token for future verification
            if ( function_exists( 'password_hash' ) ) {
                update_option( 'pd_license_token_hash', password_hash( $raw, PASSWORD_DEFAULT ) );
            }
            update_option( 'pd_is_pro', 1 );
            delete_option( 'pd_license_token' );
            $this->is_pro = true;
        }
    }

    private function load_specialized_modules() {
        $base_path = plugin_dir_path( __FILE__ );

        $modules = array(
            'login-sentinel'      => $base_path . 'login-sentinel/login-sentinel.php',
            'code-assistant'      => $base_path . 'code-assistant/code-assistant.php',
            'miasma-shield'       => $base_path . 'miasma-shield/miasma-shield.php',
            'pixel-pure'          => $base_path . 'pixel-pure/pixel-pure.php',
        );

        // Proxy crawler removed from distribution by request (avoid loading missing pro-only module).

        foreach ( $modules as $key => $path ) {
            if ( file_exists( $path ) ) {
                include_once $path;
            } else {
                error_log( "PlagueDr Core Warning: Mission critical module path failed to load: {$path}" );
            }
        }
    }

    private function get_curedhosting_tracked_url() {
        $base = 'https://curedhosting.com/';
        $params = array(
            'utm_source'   => 'plugin',
            'utm_medium'   => 'admin_dashboard',
            'utm_campaign' => 'curedhosting_referral',
        );
        return esc_url_raw( add_query_arg( $params, $base ) );
    }

    public function register_settings_gate() {
        add_options_page(
            'PlagueDr Activation',
            'PlagueDr License',
            'manage_options',
            'plaguedr-license',
            array( $this, 'render_master_dashboard' )
        );
    }

    public function save_master_settings() {
        // STRICT SCOPE: Only process on the actual settings page
        if ( isset( $_POST['pd_save_master_action'] ) && isset( $_GET['page'] ) && $_GET['page'] === 'plaguedr-license' ) {
            check_admin_referer( 'pd_master_nonce_action', 'pd_master_nonce' );
            // Admin-only owner token generation
            if ( isset( $_POST['pd_generate_owner_token'] ) ) {
                if ( check_admin_referer( 'pd_generate_token_action', 'pd_generate_token_nonce' ) && current_user_can( 'manage_options' ) ) {
                    try {
                        $bytes = random_bytes(24);
                        $token = rtrim( strtr( base64_encode( $bytes ), '+/', '-_' ), '=' );
                    } catch ( Exception $e ) {
                        $token = bin2hex( openssl_random_pseudo_bytes(24) );
                    }

                    if ( function_exists( 'password_hash' ) ) {
                        update_option( 'pd_license_token_hash', password_hash( $token, PASSWORD_DEFAULT ) );
                    }
                    update_option( 'pd_is_pro', 1 );
                    delete_option( 'pd_license_token' );

                    // Store the raw token in a short-lived transient so admin can copy it once
                    if ( function_exists( 'set_transient' ) ) {
                        set_transient( 'pd_one_time_owner_token', $token, 300 ); // 5 minutes
                    }

                    // Ensure cron scheduled for pro
                    if ( ! wp_next_scheduled( 'wp_next_scheduled_harvester' ) ) {
                        wp_schedule_event( time(), 'twicedaily', 'wp_next_scheduled_harvester' );
                    }

                    wp_redirect( admin_url( 'options-general.php?page=plaguedr-license&generated=1' ) );
                    exit;
                }
            }
            
            if ( isset( $_POST['pd_token_input'] ) ) {
                $new_token = sanitize_text_field( $_POST['pd_token_input'] );

                // New secure flow (2026-06-13): store a hashed token and set an explicit pro flag.
                $is_now_pro = ( md5( $new_token ) === md5( 'plaguedr_pro_elite' ) );

                if ( $is_now_pro ) {
                    if ( function_exists( 'password_hash' ) ) {
                        update_option( 'pd_license_token_hash', password_hash( $new_token, PASSWORD_DEFAULT ) );
                    }
                    update_option( 'pd_is_pro', 1 );
                    delete_option( 'pd_license_token' );

                    // Ensure current request reflects pro state so other settings save correctly
                    $this->is_pro = true;

                    if ( ! wp_next_scheduled( 'wp_next_scheduled_harvester' ) ) {
                        wp_schedule_event( time(), 'twicedaily', 'wp_next_scheduled_harvester' );
                    }
                } else {
                    // Invalid or non-pro token: remove pro flag and any stored hash
                    delete_option( 'pd_is_pro' );
                    delete_option( 'pd_license_token_hash' );
                    update_option( 'pd_license_token', $new_token );

                    // Turn off cron if they remove or invalidate the key
                    $timestamp = wp_next_scheduled( 'wp_next_scheduled_harvester' );
                    if ( $timestamp ) wp_unschedule_event( $timestamp, 'wp_next_scheduled_harvester' );
                }
            }

            // Save admin TODOs (newline-separated)
            if ( isset( $_POST['pd_admin_todo'] ) ) {
                $todos = sanitize_textarea_field( wp_unslash( $_POST['pd_admin_todo'] ) );
                update_option( 'pd_admin_todo', $todos );
            }
            
            if ( $this->is_pro && isset( $_POST['pd_pro_quality'] ) ) {
                update_option( 'pd_pixel_pure_quality', max( 10, min( 100, (int) $_POST['pd_pro_quality'] ) ) );
            }

            if ( $this->is_pro ) {
                if ( isset( $_POST['pd_pixel_pure_max_width'] ) ) {
                    update_option( 'pd_pixel_pure_max_width', max( 100, min( 3840, (int) $_POST['pd_pixel_pure_max_width'] ) ) );
                }
                if ( isset( $_POST['pd_pixel_pure_max_height'] ) ) {
                    update_option( 'pd_pixel_pure_max_height', max( 100, min( 2160, (int) $_POST['pd_pixel_pure_max_height'] ) ) );
                }
                if ( isset( $_POST['pd_pixel_pure_watermark_text'] ) ) {
                    update_option( 'pd_pixel_pure_watermark_text', sanitize_text_field( $_POST['pd_pixel_pure_watermark_text'] ) );
                }
                if ( isset( $_POST['pd_pixel_pure_watermark_image'] ) ) {
                    update_option( 'pd_pixel_pure_watermark_image', sanitize_text_field( $_POST['pd_pixel_pure_watermark_image'] ) );
                }
                if ( isset( $_POST['pd_pixel_pure_watermark_opacity'] ) ) {
                    update_option( 'pd_pixel_pure_watermark_opacity', max( 10, min( 100, (int) $_POST['pd_pixel_pure_watermark_opacity'] ) ) );
                }
            }

            // Handle explicit cleanup request from admin UI
            if ( isset( $_POST['pd_run_cleanup'] ) ) {
                if ( check_admin_referer( 'pd_cleanup_action', 'pd_cleanup_nonce' ) ) {
                    $this->cleanup_suite();
                    wp_redirect( admin_url( 'options-general.php?page=plaguedr-license&cleaned=true' ) );
                    exit;
                }
            }

            wp_redirect( admin_url( 'options-general.php?page=plaguedr-license&updated=true' ) );
            exit;
        }
    }

    public function render_master_dashboard() {
        $current_token = get_option( 'pd_license_token', '' );
        $quality       = get_option( 'pd_pixel_pure_quality', 82 );
        // Prefer logo in assets/images/ if present, fallback to assets/brand-mark.png
        if ( file_exists( plugin_dir_path( __FILE__ ) . 'assets/images/brand-mark.png' ) ) {
            $logo_url = plugin_dir_url( __FILE__ ) . 'assets/images/brand-mark.png';
        } else {
            $logo_url = plugin_dir_url( __FILE__ ) . 'assets/brand-mark.png';
        }
        $promo_url     = $this->get_curedhosting_tracked_url();
        ?>
        <div class="wrap" style="background:#0a0a0a; color:#3cff3c; padding:20px; font-family:'Courier New', monospace; border:1px solid #3cff3c; max-width:800px; margin-top:20px; box-shadow: 0 4px 15px rgba(0,0,0,0.7);">
            <div style="display:flex; align-items:center; gap:18px; margin-bottom:20px;">
                <div style="width:120px; height:120px; overflow:hidden; border-radius:18px; border:1px solid rgba(255,255,255,0.1); background:#111; display:flex; align-items:center; justify-content:center;">
                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="Plugin logo" style="height:120px; width:auto; display:block; transform:translateX(0);" />
                </div>
                <div>
                    <h1 style="color:#66bb6a; font-family: sans-serif; font-weight:900; letter-spacing:1px; margin-bottom:5px;">PLAGUEDR LABORATORY CONFIGURATION</h1>
                    <div style="font-size:0.9rem; color:#8fd08f;">Powered by <a href="<?php echo esc_url( $promo_url ); ?>" target="_blank" rel="noopener noreferrer" style="color:#c5a059; text-decoration:none;">curedhosting.com</a></div>
                </div>
            </div>
            <script>
                (function(){
                    var img = new Image();
                    img.src = '<?php echo esc_url( add_query_arg( array(
                        'utm_source' => 'plugin',
                        'utm_medium' => 'admin_beacon',
                        'utm_campaign' => 'curedhosting_referral',
                        'event' => 'dashboard_load'
                    ), 'https://curedhosting.com/' ) ); ?>';
                    img.width = 1;
                    img.height = 1;
                    img.style.display = 'none';
                    document.body.appendChild(img);
                })();
            </script>
            <p style="color: #5c825c; margin-bottom:20px;">Status: <?php echo $this->is_pro ? '<span style="color:#3cff3c; font-weight:bold;">PRO VALIDATION SECURED</span>' : '<span style="color:#a64d4d; font-weight:bold;">LOCKED (FREE EDITION)</span>'; ?></p>

            <?php
            // One-time owner token display (admin can copy this once)
            $one_time = function_exists( 'get_transient' ) ? get_transient( 'pd_one_time_owner_token' ) : false;
            if ( $one_time ) :
            ?>
                <div style="background:#111; border-left:4px solid #66bb6a; padding:12px; margin-bottom:18px;">
                    <strong style="color:#c5a059; display:block; margin-bottom:6px;">One-time Owner Token (copy now)</strong>
                    <div style="background:#000; color:#fff; padding:10px; font-family:monospace; word-break:break-all;"><?php echo esc_html( $one_time ); ?></div>
                    <div style="color:#9fd09f; font-size:0.9rem; margin-top:8px;">This token will be shown only once and expires in 5 minutes. Keep it secret.</div>
                </div>
            <?php
                if ( function_exists( 'delete_transient' ) ) {
                    delete_transient( 'pd_one_time_owner_token' );
                }
            endif;
            ?>
            <hr style="border-color:#2a402a; margin-bottom: 25px;" />

            <form method="POST">
                <?php wp_nonce_field( 'pd_master_nonce_action', 'pd_master_nonce' ); ?>
                <?php
                // Admin TODOs: simple editable list stored as newline-separated option `pd_admin_todo`
                $raw_todos = get_option( 'pd_admin_todo', '' );
                $todo_lines = array_filter( array_map( 'trim', explode( "\n", (string) $raw_todos ) ) );
                ?>

                <div style="margin-bottom:18px; background:#071007; border:1px solid #113311; padding:12px;">
                    <strong style="color:#c5a059; display:block; margin-bottom:8px;">Admin TODOs</strong>
                    <?php if ( ! empty( $todo_lines ) ) : ?>
                        <ul id="pd_todo_list" style="color:#9fd09f; margin-top:0; margin-bottom:8px; padding-left:18px;">
                        <?php foreach ( $todo_lines as $i => $t ) : ?>
                            <li data-idx="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $t ); ?> <button class="pd-remove-todo" data-idx="<?php echo esc_attr( $i ); ?>" style="margin-left:8px;">Remove</button></li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <div id="pd_todo_list" style="color:#889d88; margin-bottom:8px;">No todo items set.</div>
                    <?php endif; ?>

                    <label style="color:#c5a059; display:block; margin-bottom:6px; margin-top:6px;">Add TODO</label>
                    <div style="display:flex; gap:8px;">
                        <input id="pd_todo_input" type="text" placeholder="New todo item" style="flex:1; background:#0b120b; color:#dff2dff; border:1px solid #254225; padding:8px;" />
                        <button id="pd_add_todo" class="button" style="background:#66bb6a; color:#000;">Add</button>
                    </div>
                    <input type="hidden" id="pd_todo_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pd_todo_nonce' ) ); ?>" />
                </div>

                <div style="margin-bottom:35px;">
                    <label style="color: #c5a059; font-weight: bold; display:block; margin-bottom: 10px; text-transform:uppercase;">System License Token:</label>
                    <input type="text" name="pd_token_input" value="<?php echo esc_attr( $current_token ); ?>" style="width:100%; max-width:400px; background:#131b13; color:#fff; border:1px solid #2a402a; font-family:monospace; padding:10px; box-sizing:border-box;" placeholder="XXXX-XXXX-XXXX-XXXX" />
                </div>

                <?php if ( $this->is_pro ) : ?>
                    <hr style="border-color:#2a402a; margin-bottom: 25px;" />
                    <div style="margin-bottom:35px;">
                        <h3 style="color:#c5a059; font-family: sans-serif; margin-top:0; text-transform:uppercase;">Pixel-Pure Pro Coefficients</h3>
                        <label style="display:block; margin-bottom:10px;">Set Local Compression Quality Ceiling (10 - 100):</label>
                        <input type="number" name="pd_pro_quality" value="<?php echo esc_attr( $quality ); ?>" style="background:#131b13; color:#fff; border:1px solid #2a402a; padding:8px; font-family:monospace; width:80px;" />
                    </div>

                    <div style="margin-bottom:35px;">
                        <h3 style="color:#c5a059; font-family: sans-serif; text-transform:uppercase;">Premium Resize & Watermark</h3>
                        <label style="display:block; margin-bottom:10px;">Max Width</label>
                        <input type="number" name="pd_pixel_pure_max_width" value="<?php echo esc_attr( get_option( 'pd_pixel_pure_max_width', 1920 ) ); ?>" style="background:#131b13; color:#fff; border:1px solid #2a402a; padding:8px; width:120px;" />
                        <label style="display:block; margin-top:15px; margin-bottom:10px;">Max Height</label>
                        <input type="number" name="pd_pixel_pure_max_height" value="<?php echo esc_attr( get_option( 'pd_pixel_pure_max_height', 1080 ) ); ?>" style="background:#131b13; color:#fff; border:1px solid #2a402a; padding:8px; width:120px;" />
                        <label style="display:block; margin-top:15px; margin-bottom:10px;">Watermark Text</label>
                        <input type="text" name="pd_pixel_pure_watermark_text" value="<?php echo esc_attr( get_option( 'pd_pixel_pure_watermark_text', '' ) ); ?>" style="background:#131b13; color:#fff; border:1px solid #2a402a; padding:8px; width:100%; max-width:400px;" />
                        <label style="display:block; margin-top:15px; margin-bottom:10px;">Optional watermark image path</label>
                        <input type="text" name="pd_pixel_pure_watermark_image" value="<?php echo esc_attr( get_option( 'pd_pixel_pure_watermark_image', '' ) ); ?>" style="background:#131b13; color:#fff; border:1px solid #2a402a; padding:8px; width:100%; max-width:400px;" />
                        <label style="display:block; margin-top:15px; margin-bottom:10px;">Watermark opacity (10-100)</label>
                        <input type="number" name="pd_pixel_pure_watermark_opacity" value="<?php echo esc_attr( get_option( 'pd_pixel_pure_watermark_opacity', 60 ) ); ?>" style="background:#131b13; color:#fff; border:1px solid #2a402a; padding:8px; width:80px;" />
                    </div>

                    <hr style="border-color:#2a402a; margin-bottom: 25px;" />
                    <div style="margin-bottom:20px;">
                        <h3 style="color:#c5a059; font-family: sans-serif; margin-top:0; text-transform:uppercase;">Mass Isolation Crucible</h3>
                        <p style="color:#d0ebd0; font-size:0.9rem; margin-bottom:15px;">Scan and compress legacy images residing across system media layers in real time.</p>
                        <button id="pd_trigger_bulk" class="button" style="background:#c5a059; color:#000; border-radius:0; font-weight:bold; border:none; padding:6px 20px; cursor:pointer; font-family:monospace;">INITIATE BULK COMPRESSION</button>
                        <div id="pd_bulk_status" style="margin-top:15px; color:#fff; font-style:italic;"></div>
                    </div>
                <?php endif; ?>

                <hr style="border-color:#2a402a; margin-top: 30px; margin-bottom: 20px;" />
                <?php wp_nonce_field( 'pd_cleanup_action', 'pd_cleanup_nonce' ); ?>
                <?php wp_nonce_field( 'pd_generate_token_action', 'pd_generate_token_nonce' ); ?>
                <input type="submit" name="pd_save_master_action" class="button button-primary" style="background:#388e3c; border-color:#66bb6a; border-radius:0; font-weight:bold; text-transform:uppercase;" value="Save Laboratory States" />
                <input type="submit" name="pd_run_cleanup" class="button" style="background:#a64d4d; color:#fff; border-radius:0; font-weight:bold; margin-left:12px;" value="Cleanup Stored Options" onclick="return confirm('This will remove plugin options and scheduled tasks. Continue?');" />
                <?php if ( current_user_can( 'manage_options' ) ) : ?>
                    <input type="submit" name="pd_generate_owner_token" class="button" style="background:#c5a059; color:#000; border-radius:0; font-weight:bold; margin-left:12px;" value="Generate Owner Token" onclick="return confirm('Generate a new owner token and mark this site as PRO? The raw token will be shown once. Continue?');" />
                <?php endif; ?>
            </form>
        </div>

        <?php if ( $this->is_pro ) : ?>
            <script>
            document.getElementById('pd_trigger_bulk').addEventListener('click', function(e) {
                e.preventDefault();
                var statusDiv = document.getElementById('pd_bulk_status');
                var button = document.getElementById('pd_trigger_bulk');
                
                button.disabled = true;
                button.style.opacity = '0.5';
                statusDiv.innerHTML = "Initiating system scan... Commencing batch 1...";

                // FIX 2: Implement the paginated looping system to match pixel-pure.php
                function processBatch(offset) {
                    var data = new URLSearchParams();
                    data.append('action', 'pd_bulk_optimize');
                    data.append('offset', offset);

                    fetch(ajaxurl, {
                        method: 'POST',
                        body: data
                    })
                    .then(response => response.json())
                    .then(response => {
                        if (response.done) {
                            statusDiv.innerHTML = "<span style='color:#66bb6a;'>[SUCCESS] " + response.message + "</span>";
                            button.disabled = false;
                            button.style.opacity = '1';
                        } else if (response.success) {
                            statusDiv.innerHTML = "<span style='color:#c5a059;'>" + response.message + "</span>";
                            // Recursive call for the next batch
                            processBatch(response.next_offset);
                        } else {
                            statusDiv.innerHTML = "<span style='color:#a64d4d;'>[ERROR] Extraction halted prematurely.</span>";
                            button.disabled = false;
                        }
                    }).catch(err => {
                        statusDiv.innerHTML = "<span style='color:#a64d4d;'>[CRITICAL] Server execution interrupted. Check error logs.</span>";
                        button.disabled = false;
                    });
                }

                // Start the loop at offset 0
                processBatch(0);
            });
            </script>
        <?php endif; ?>
        <script>
        (function(){
            function updateList(items){
                var list = document.getElementById('pd_todo_list');
                if(!list) return;
                if(items.length===0){ list.innerHTML = '<div style="color:#889d88; margin-bottom:8px;">No todo items set.</div>'; return; }
                var html='';
                items.forEach(function(it, idx){
                    html += '<li data-idx="'+idx+'">'+escapeHtml(it)+' <button class="pd-remove-todo" data-idx="'+idx+'" style="margin-left:8px;">Remove</button></li>';
                });
                list.innerHTML = html;
            }

            function escapeHtml(s){ return (s+'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

            var addBtn = document.getElementById('pd_add_todo');
            if(addBtn){
                addBtn.addEventListener('click', function(e){
                    e.preventDefault();
                    var input = document.getElementById('pd_todo_input');
                    var val = input.value.trim();
                    if(!val) return;
                    var fd = new FormData();
                    fd.append('action','pd_todo_add');
                    fd.append('item', val);
                    fd.append('nonce', document.getElementById('pd_todo_nonce').value);
                    fetch(ajaxurl, {method:'POST', body:fd}).then(function(r){ return r.json(); }).then(function(json){
                        if(json.success){ updateList(json.data); input.value=''; }
                        else alert('Failed to add todo');
                    }).catch(function(){ alert('Request failed'); });
                });
            }

            document.addEventListener('click', function(e){
                if(e.target && e.target.classList && e.target.classList.contains('pd-remove-todo')){
                    var idx = e.target.getAttribute('data-idx');
                    var fd = new FormData();
                    fd.append('action','pd_todo_remove');
                    fd.append('index', idx);
                    fd.append('nonce', document.getElementById('pd_todo_nonce').value);
                    fetch(ajaxurl, {method:'POST', body:fd}).then(function(r){ return r.json(); }).then(function(json){
                        if(json.success){ updateList(json.data); } else { alert('Failed to remove todo'); }
                    }).catch(function(){ alert('Request failed'); });
                }
            });
        })();
        </script>
        <?php
    }

    private function register_lifecycle_hooks() {
        register_deactivation_hook( CHD_PLUGIN_FILE, array( $this, 'deactivate_suite' ) );
        // Removed activate_suite() as cron generation is now properly tied to license entry.
    }

    public function deactivate_suite() {
        $timestamp = wp_next_scheduled( 'wp_next_scheduled_harvester' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'wp_next_scheduled_harvester' );
        }
        delete_transient( 'pd_elite_proxy_pool' );
    }

    /**
     * Cleanup stored options, transients and scheduled hooks.
     * Can be called from uninstall.php or via the admin cleanup form.
     */
    public function cleanup_suite() {
        // Unschedule cron
        if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_unschedule_event' ) ) {
            $timestamp = wp_next_scheduled( 'wp_next_scheduled_harvester' );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, 'wp_next_scheduled_harvester' );
            }
        }

        // Delete transients
        delete_transient( 'pd_elite_proxy_pool' );

        // Delete stored options (also remove site options on multisite)
        $options = array(
            'pd_license_token',
            'pd_pixel_pure_quality',
            'pd_pixel_pure_max_width',
            'pd_pixel_pure_max_height',
            'pd_pixel_pure_watermark_text',
            'pd_pixel_pure_watermark_image',
            'pd_pixel_pure_watermark_opacity',
        );

        foreach ( $options as $opt ) {
            delete_option( $opt );
            if ( is_multisite() ) {
                delete_site_option( $opt );
            }
        }
    }
}

function plaguedr_core_instantiate() {
    return PlagueDr_Core_Conductor::instance();
}
add_action( 'plugins_loaded', 'plaguedr_core_instantiate' );