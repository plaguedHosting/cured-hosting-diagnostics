<?php
/**
 * Leech-Drain Cache module.
 * Ultra-lightweight static page caching to bleed off load times.
 */

if (!defined('ABSPATH')) exit;

class LeechDrainCache {
    private $cache_dir;
    private $cache_time = 3600;

    public function __construct() {
        $this->cache_dir = WP_CONTENT_DIR . '/leech-cache/';
        
        if (!file_exists($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }

        add_action('init', array($this, 'start_buffer'));
        add_action('shutdown', array($this, 'end_buffer'));
        
        // Add hooks to purge cache on post updates
        add_action('publish_post', array($this, 'purge_cache'));
        add_action('post_updated', array($this, 'purge_cache'));
    }

    // Purge all files in the cache directory
    public function purge_cache() {
        if (file_exists($this->cache_dir)) {
            $files = glob($this->cache_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    public function start_buffer() {
        if ($this->is_cacheable()) {
            $cache_file = $this->get_cache_filename();
            
            if (file_exists($cache_file) && (time() - filemtime($cache_file) < $this->cache_time)) {
                header('X-Cache-Engine: Leech-Drain Active');
                echo file_get_contents($cache_file);
                exit;
            }
            ob_start();
        }
    }

    public function end_buffer() {
        if ($this->is_cacheable() && ob_get_length()) {
            $cache_file = $this->get_cache_filename();
            $buffer_content = ob_get_contents();
            file_put_contents($cache_file, $buffer_content);
            ob_end_flush();
        }
    }

    private function is_cacheable() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_COOKIE)) {
            foreach ($_COOKIE as $key => $value) {
                if (preg_match('/^wp-postpass_|^wordpress_logged_in_|^comment_author_/', $key)) {
                    return false;
                }
            }
        }
        return is_admin() ? false : true;
    }

    private function get_cache_filename() {
        $url_hash = md5($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        return $this->cache_dir . $url_hash . '.html';
    }
}

new LeechDrainCache();