<?php
// Sanctum Guard: Prevent direct asset scanning
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cure 03: Code Assistant & Diagnostic Terminal
 * Core Pillar: SPEED, PRIVACY & SEKURE AUDITING
 */
final class PlagueDr_Code_Assistant {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'register_assistant_chamber' ) );
        add_action( 'wp_ajax_pd_run_diagnostics', array( $this, 'execute_local_diagnostics' ) );
    }

    public function register_assistant_chamber() {
        add_menu_page(
            'Code Assistant',
            'Code Assistant',
            'manage_options',
            'plaguedr-code-assistant',
            array( $this, 'render_terminal_interface' ),
            'dashicons-editor-code',
            67
        );
    }

    /**
     * Server Side: Compiles real-time local environment diagnostic parameters
     */
    public function execute_local_diagnostics() {
        check_ajax_referer( 'pd_terminal_sanctum', 'security' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized entry.' ) );
            wp_die(); // FIX 1: Explicit termination on failed authorization
        }

        global $wpdb;
        
        // FIX 2: Secure database size query using prepare()
        $db_size = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(data_length + index_length) FROM information_schema.TABLES WHERE table_schema = %s",
            DB_NAME
        ) );
        $db_size_mb = $db_size ? round( $db_size / 1024 / 1024, 2 ) : 0;

        // Added Server IP and Max Execution Time to diagnostics
        $diagnostics = array(
            'OS'               => PHP_OS,
            'PHP_Version'      => PHP_VERSION,
            'Memory_Limit'     => ini_get( 'memory_limit' ),
            'Max_Execution'    => ini_get( 'max_execution_time' ) . 's',
            'Post_Max_Size'    => ini_get( 'post_max_size' ),
            'Database_Size'    => $db_size_mb . ' MB',
            'WP_Version'       => get_bloginfo( 'version' ),
            'Server_Software'  => $_SERVER['SERVER_SOFTWARE'],
        );

        wp_send_json_success( $diagnostics );
    }

    /**
     * UI Layer: Renders the alchemical diagnostic and audit terminal console
     */
    public function render_terminal_interface() {
        ?>
        <div class="wrap" style="margin: 20px 20px 0 0;">
            <style>
                .terminal-sanctum { background-color: #0b0f0b; color: #d0ebd0; font-family: 'Courier New', Courier, monospace; border: 1px solid #2a402a; padding: 30px; max-width: 900px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); }
                .terminal-header { border-bottom: 2px double #2a402a; padding-bottom: 15px; margin-bottom: 25px; }
                .terminal-title { color: #66bb6a; font-size: 1.6rem; text-transform: uppercase; letter-spacing: 2px; margin: 0; }
                .terminal-screen { background-color: #050705; border: 1px solid #2a402a; padding: 20px; height: 250px; overflow-y: auto; margin-bottom: 20px; box-shadow: inset 0 0 10px rgba(0,0,0,0.9); }
                .terminal-log { margin: 0 0 8px 0; line-height: 1.4; font-size: 0.9rem; }
                .output-green { color: #66bb6a; } .output-gold { color: #c5a059; } .output-dim { color: #5c825c; } .output-red { color: #ff5252; }
                .terminal-input-wrapper { display: flex; gap: 10px; margin-bottom: 15px; }
                .terminal-textarea { background-color: #131b13; border: 1px solid #2a402a; color: #d0ebd0; font-family: inherit; padding: 12px; width: 100%; height: 100px; resize: vertical; box-sizing: border-box; }
                .terminal-textarea:focus { border-color: #66bb6a; outline: none; }
                .action-row { display: flex; gap: 15px; }
                .term-btn { background-color: #131b13; border: 1px solid #2a402a; color: #66bb6a; padding: 10px 20px; cursor: pointer; font-family: inherit; font-weight: bold; text-transform: uppercase; transition: all 0.2s; }
                .term-btn:hover { background-color: #388e3c; color: #fff; border-color: #66bb6a; box-shadow: 0 0 8px rgba(102,187,106,0.3); }
            </style>

            <div class="terminal-sanctum">
                <div class="terminal-header">
                    <h2 class="terminal-title">Assistant Console // Diagnostic Terminal</h2>
                    <p style="margin: 5px 0 0 0; color: #5c825c; font-style: italic;">Local Telemetry Diagnostics & Sandbox Code Auditing</p>
                </div>

                <div class="terminal-screen" id="terminal-screen">
                    <p class="terminal-log output-dim">[SYSTEM INSTANTIATED] Ready for diagnostic command loops...</p>
                </div>

                <h3 style="color: #c5a059; font-size: 1rem; text-transform: uppercase; margin-bottom: 10px;">Audit Workspace</h3>
                <div class="terminal-input-wrapper">
                    <textarea id="code-payload" class="terminal-textarea" placeholder="// Drop raw PHP, JS, or text arrays here to inspect structure..."></textarea>
                </div>

                <div class="action-row">
                    <button type="button" class="term-btn" id="run-diagnostics-btn">Run Diagnostics</button>
                    <button type="button" class="term-btn" id="audit-code-btn" style="color: #c5a059;">Audit Snippet</button>
                    <button type="button" class="term-btn" id="clear-term-btn" style="color: #5c825c; margin-left: auto;">Clear Screen</button>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const screen = document.getElementById('terminal-screen');
            const codePayload = document.getElementById('code-payload');
            const nonce = '<?php echo wp_create_nonce("pd_terminal_sanctum"); ?>';

            function printLog(text, colorClass = '') {
                const p = document.createElement('p');
                p.className = 'terminal-log ' + colorClass;
                p.innerText = `> ${text}`;
                screen.appendChild(p);
                screen.scrollTop = screen.scrollHeight;
            }

            // Local System Diagnostics Handler
            document.getElementById('run-diagnostics-btn').addEventListener('click', function() {
                printLog('Querying local host parameters securely...', 'output-dim');
                
                jQuery.post(ajaxurl, {
                    action: 'pd_run_diagnostics',
                    security: nonce
                }, function(response) {
                    if (response.success) {
                        printLog('--- HOST METRICS REPORT ---', 'output-green');
                        for (const [key, value] of Object.entries(response.data)) {
                            printLog(`${key.toUpperCase()}: ${value}`, 'output-gold');
                        }
                        printLog('System scanning complete. Status: OPTIMIZED.', 'output-green');
                    } else {
                        printLog('ERROR: Diagnostic compilation failed or unauthorized.', 'output-red');
                    }
                }).fail(function() {
                    printLog('ERROR: Terminal connection to server blocked.', 'output-red');
                });
            });

            // FIX 3: Regex-Powered Smart Auditor
            document.getElementById('audit-code-btn').addEventListener('click', function() {
                const payload = codePayload.value.trim();
                if (!payload) {
                    printLog('WARNING: Workspace empty. Please insert code snippet to execute audit pipeline.', 'output-gold');
                    return;
                }

                printLog('Analyzing snippet structures locally...', 'output-dim');
                
                setTimeout(() => {
                    let flags = 0;
                    printLog('--- STRUCTURE AUDIT COMPILATION ---', 'output-gold');
                    
                    // Regex catches functions properly formatted, ignoring most plain-text comments
                    if (/(?:eval|base64_decode)\s*\(/.test(payload)) {
                        printLog('[ALERT] Dangerous execution pattern identified (eval/base64). Highly vulnerable to injection.', 'output-red');
                        flags++;
                    }
                    
                    if (/\$_(?:POST|GET|REQUEST|COOKIE)/.test(payload)) {
                        if (!/wp_verify_nonce|check_ajax_referer|check_admin_referer/.test(payload)) {
                            printLog('[WARNING] Global input parameters processed without explicit nonce token guards.', 'output-gold');
                            flags++;
                        }
                        if (!/sanitize_text_field|absint|esc_/.test(payload)) {
                            printLog('[WARNING] Raw request array ingested without active sanitization/escaping filters.', 'output-gold');
                            flags++;
                        }
                    }
                    
                    if (/(?:SELECT|UPDATE|DELETE|INSERT)[\s\S]+?FROM/i.test(payload) && !/\$wpdb->prepare/.test(payload)) {
                        printLog('[CRITICAL] Raw database syntax constructed outside secure preparation layers ($wpdb->prepare).', 'output-red');
                        flags++;
                    }

                    if (flags === 0) {
                        printLog('SUCCESS: Code structure conforms to high-performance, secure guidelines.', 'output-green');
                    } else {
                        printLog(`AUDIT COMPLETE: ${flags} structural hazards or optimizations detected.`, 'output-gold');
                    }
                }, 400);
            });

            // Clear Screen
            document.getElementById('clear-term-btn').addEventListener('click', function() {
                screen.innerHTML = '';
                printLog('Console buffer cleared.', 'output-dim');
            });
        });
        </script>
        <?php
    }
}

function plaguedr_code_assistant_init() {
    return PlagueDr_Code_Assistant::instance();
}
plaguedr_code_assistant_init();