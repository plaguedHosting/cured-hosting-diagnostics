<?php
namespace Cured\Admin;

defined('ABSPATH') || exit;

final class Page {

    public static function register(): void {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_post_cured_clear_logs', [self::class, 'handle_clear_logs']);
    }

    public static function add_menu(): void {
        add_menu_page(
            'Cured Diagnostics',
            'Cured Diagnostics',
            'manage_options',
            'cured-diagnostics',
            [self::class, 'render'],
            'dashicons-shield-alt',
            58
        );
    }

    public static function handle_clear_logs(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized.');
        }

        check_admin_referer('cured_clear_logs');

        delete_option('cured_resolved_clash_log');
        delete_option('plaguedr_resolved_clash_log');
        delete_option('cured_activation_rollback_log');

        wp_safe_redirect(add_query_arg('cured_cleared', '1', admin_url('admin.php?page=cured-diagnostics')));
        exit;
    }

    public static function render(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $crash    = get_option('cured_resolved_clash_log', []);
        $legacy   = get_option('plaguedr_resolved_clash_log', []);
        $rollback = get_option('cured_activation_rollback_log', []);
        $snapshot = get_option('cured_activation_snapshot', []);

        echo '<div class="wrap">';
        echo '<h1>Cured Diagnostics</h1>';

        if (!empty($_GET['cured_cleared'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Logs cleared.</p></div>';
        }

        echo '<h2>System Status</h2>';
        echo '<table class="widefat striped" style="max-width:720px;">';
        echo '<tbody>';
        self::row('Plugin Version', defined('CURED_VERSION') ? CURED_VERSION : 'unknown');
        self::row('PHP Version', PHP_VERSION);
        self::row('WP Version', function_exists('get_bloginfo') ? get_bloginfo('version') : 'unknown');
        self::row('Memory Limit', ini_get('memory_limit'));
        self::row('Is MU Plugin', defined('CURED_IS_MU') && CURED_IS_MU ? 'Yes' : 'No');
        self::row('Activation In Progress', get_option('cured_activation_in_progress') ? 'Yes' : 'No');
        echo '</tbody></table>';

        echo '<h2>Latest Crash Log</h2>';
        self::dump(!empty($crash) ? $crash : $legacy);

        echo '<h2>Rollback History</h2>';
        self::dump($rollback);

        echo '<h2>Activation Snapshot</h2>';
        self::dump($snapshot);

        echo '<h2>Maintenance</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="cured_clear_logs" />';
        wp_nonce_field('cured_clear_logs');
        submit_button('Clear All Cured Logs', 'delete');
        echo '</form>';

        echo '</div>';
    }

    private static function row(string $label, string $value): void {
        echo '<tr><th style="text-align:left;">' . esc_html($label) . '</th><td>' . esc_html($value) . '</td></tr>';
    }

    private static function dump(mixed $data): void {
        echo '<pre style="background:#0b1020;color:#9cf;padding:16px;overflow:auto;border-radius:6px;">';
        echo esc_html(empty($data) ? '-- No data --' : print_r($data, true));
        echo '</pre>';
    }
}
