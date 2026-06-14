<?php
namespace Cured\Core;

use Cured\Contracts\Module_Interface;

defined('ABSPATH') || exit;

final class Bootstrap {
    private static ?self $instance = null;

    private array $modules = [];
    private bool $booted = false;

    public const VERSION = '0.1.0-dev';

    public static function init(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->register_autoloader();
        $this->define_constants();
        $this->register_modules();
        $this->register_hooks();
    }

    private function register_autoloader(): void {
        $vendor = dirname(__DIR__, 2) . '/vendor/autoload.php';

        if (file_exists($vendor)) {
            require_once $vendor;
            return;
        }

        spl_autoload_register(
            function (string $class): void {
                $prefix  = 'Cured\\';
                $baseDir = dirname(__DIR__, 2) . '/src/';

                if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                    return;
                }

                $relative = substr($class, strlen($prefix));
                $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

                if (file_exists($file)) {
                    require_once $file;
                }
            },
            true,
            true
        );
    }

    private function define_constants(): void {
        $root = dirname(__DIR__, 2);

        if (!defined('CURED_PATH')) {
            define('CURED_PATH', trailingslashit($root));
        }

        if (!defined('CURED_SRC')) {
            define('CURED_SRC', trailingslashit($root . '/src'));
        }

        if (!defined('CURED_VERSION')) {
            define('CURED_VERSION', self::VERSION);
        }
    }

    private function register_modules(): void {
        $map = [
            'core'       => \Cured\Core\Module::class,
            'diagnostics'=> \Cured\Diagnostics\Engine::class,
            'sentinel'   => \Cured\Sentinel\Guard::class,
            'airbag'     => \Cured\Airbag\Rails::class,
            'media'      => \Cured\Media\Pipeline::class,
            'seo'        => \Cured\SEO\Manager::class,
            'copilot'    => \Cured\Copilot\Assistant::class,
            'cache'      => \Cured\Cache\Drain::class,
        ];

        foreach ($map as $key => $class) {
            if (class_exists($class)) {
                $this->modules[$key] = new $class();
            } else {
                $this->modules[$key] = new \Cured\Core\Stub\Module_Stub();
            }
        }
    }

    private function register_hooks(): void {
        add_action('plugins_loaded', [$this, 'boot_modules'], 0);
        add_action('shutdown', [$this, 'shutdown_capture'], 0);

        if (is_admin()) {
            add_action('admin_menu', [$this, 'register_admin_menu']);
        }
    }

    public function boot_modules(): void {
        if ($this->booted) {
            return;
        }

        foreach ($this->modules as $module) {
            if ($module instanceof Module_Interface) {
                $module->boot();
            }
        }

        $this->booted = true;
    }

    public function shutdown_capture(): void {
        $error = error_get_last();

        if (!$error) {
            return;
        }

        $fatal_types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

        if (in_array($error['type'], $fatal_types, true)) {
            if (isset($this->modules['diagnostics']) && method_exists($this->modules['diagnostics'], 'handle_fatal_error')) {
                $this->modules['diagnostics']->handle_fatal_error($error);
            }
        }
    }

    public function register_admin_menu(): void {
        add_menu_page(
            'Cured Diagnostics',
            'Cured Diagnostics',
            'manage_options',
            'cured-diagnostics',
            [$this, 'render_admin_page'],
            'dashicons-shield-alt',
            58
        );
    }

    public function render_admin_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $payload = get_option('cured_resolved_clash_log', []);
        $legacy  = get_option('plaguedr_resolved_clash_log', []);

        echo '<div class="wrap">';
        echo '<h1>Cured Diagnostics</h1>';
        echo '<p>Crash capture, rollback telemetry, and safety events appear here.</p>';

        echo '<h2>Latest Crash Log</h2>';
        echo '<pre style="background:#111;color:#0f0;padding:16px;overflow:auto;">';
        echo esc_html(print_r(!empty($payload) ? $payload : $legacy, true));
        echo '</pre>';

        echo '</div>';
    }

    public function get(string $key): mixed {
        return $this->modules[$key] ?? null;
    }
}
