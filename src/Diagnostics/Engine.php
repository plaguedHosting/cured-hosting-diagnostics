<?php
namespace Cured\Diagnostics;

use Cured\Contracts\Module_Interface;

final class Engine implements Module_Interface {
    private bool $locked = false;

    public function boot(): void {
        // Future hooks:
        // - preflight snapshots
        // - plugin activation interception
        // - rollback logic
    }

    public function requires_license(): bool {
        return false;
    }

    public function set_locked(bool $locked): void {
        $this->locked = $locked;
    }

    public function handle_fatal_error(array $error): void {
        $payload = [
            'time'    => time(),
            'type'    => $error['type'] ?? null,
            'message' => $error['message'] ?? '',
            'file'    => $error['file'] ?? '',
            'line'    => $error['line'] ?? 0,
        ];

        if (function_exists('update_option')) {
            update_option('cured_resolved_clash_log', $payload, false);
            update_option('plaguedr_resolved_clash_log', $payload, false);
        }

        if (function_exists('get_option') && get_option('cured_activation_in_progress')) {
            \Cured\Core\Activation::rollback($payload);
        }

        $json = function_exists('wp_json_encode') ? wp_json_encode($payload) : json_encode($payload);
        error_log('[Cured Diagnostics] Fatal captured: ' . $json);
    }
}
