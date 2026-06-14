<?php
/**
 * Beak-Armor Cloud Guard module.
 * Lightweight, file-based security firewall to deflect brute-force attacks and malicious requests.
 */

if (!defined('ABSPATH')) exit; 

class BeakArmorCloudGuard {
    private $jail_dir;
    private $max_attempts = 5;
    private $lockout_time = 900; 

    public function __construct() {
        $this->jail_dir = WP_CONTENT_DIR . '/armor-jail/';
        
        if (!file_exists($this->jail_dir)) {
            mkdir($this->jail_dir, 0755, true);
        }

        add_action('plugins_loaded', array($this, 'inspect_request'));
        add_action('wp_login_failed', array($this, 'log_failed_attempt'));
        
        // Schedule cleanup
        add_action('init', array($this, 'setup_cron_cleanup'));
        add_action('beak_armor_cleanup_event', array($this, 'prune_jail'));
    }

    public function setup_cron_cleanup() {
        if (!wp_next_scheduled('beak_armor_cleanup_event')) {
            wp_schedule_event(time(), 'daily', 'beak_armor_cleanup_event');
        }
    }

    public function prune_jail() {
        if (!file_exists($this->jail_dir)) return;
        $files = glob($this->jail_dir . '*.json');
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (isset($data['lockout_until']) && $data['lockout_until'] <= time()) {
                unlink($file);
            }
        }
    }

    public function inspect_request() {
        // Admin Whitelist: Never block someone who can manage the site
        if (current_user_can('manage_options')) return;

        $ip = $this->get_user_ip();
        
        if ($this->is_banned($ip)) {
            $this->serve_quarantine_page();
        }

        $request_uri = $_SERVER['REQUEST_URI'];
        $bad_patterns = array('eval\(', 'concat', 'UNION SELECT', '<script', 'wp-config.php', '/etc/passwd');
        
        foreach ($bad_patterns as $pattern) {
            if (stripos($request_uri, $pattern) !== false) {
                $this->log_failed_attempt(null, $ip);
                $this->serve_quarantine_page();
            }
        }
    }

    public function log_failed_attempt($username = null, $forced_ip = null) {
        $ip = $forced_ip ? $forced_ip : $this->get_user_ip();
        $ip_file = $this->jail_dir . md5($ip) . '.json';

        $log = array('attempts' => 0, 'lockout_until' => 0);
        if (file_exists($ip_file)) {
            $log = json_decode(file_get_contents($ip_file), true);
        }

        $log['attempts']++;
        
        if ($log['attempts'] >= $this->max_attempts) {
            $log['lockout_until'] = time() + $this->lockout_time;
        }

        file_put_contents($ip_file, json_encode($log));
    }

    private function is_banned($ip) {
        $ip_file = $this->jail_dir . md5($ip) . '.json';
        if (!file_exists($ip_file)) return false;

        $log = json_decode(file_get_contents($file_content = file_get_contents($ip_file)), true);
        
        if ($log['lockout_until'] > time()) {
            return true;
        }

        if ($log['lockout_until'] > 0 && $log['lockout_until'] <= time()) {
            unlink($ip_file);
        }

        return false;
    }

    private function get_user_ip() {
        return isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR'];
    }

    private function serve_quarantine_page() {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=utf-8');
        echo "<html style='background:#0a0a0a;color:#ff3333;font-family:monospace;padding:100px;text-align:center;'>";
        echo "<h2>[ BEAK-ARMOR CLOUD GUARD ]</h2>";
        echo "<p style='color:#cfcfcf;'>DIAGNOSIS: MALICIOUS ACTIVITY FLAGGED.</p>";
        echo "<p style='color:#666;'>Your IP address has been temporarily quarantined in the Sanatorium.</p>";
        echo "</html>";
        exit;
    }
}

new BeakArmorCloudGuard();