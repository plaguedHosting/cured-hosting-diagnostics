<?php
namespace Cured\Core;

defined('ABSPATH') || exit;

final class Activation {

    public static function activate(): void {
        if (!function_exists('get_option') || !function_exists('update_option')) {
            return;
        }

        $snapshot = [
            'active_plugins'   => get_option('active_plugins', []),
            'sitewide_plugins'  => function_exists('get_site_option') ? get_site_option('active_sitewide_plugins', []) : [],
            'timestamp'        => time(),
            'version'          => defined('CURED_VERSION') ? CURED_VERSION : null,
        ];

        update_option('cured_activation_snapshot', $snapshot, false);
        update_option('cured_activation_in_progress', 1, false);
    }

    public static function deactivate(): void {
        if (function_exists('delete_option')) {
            delete_option('cured_activation_in_progress');
        }
    }

    public static function rollback(array $error = []): void {
        if (!function_exists('get_option') || !function_exists('update_option')) {
            return;
        }

        $snapshot = get_option('cured_activation_snapshot', []);

        if (is_array($snapshot)) {
            if (!empty($snapshot['active_plugins']) && is_array($snapshot['active_plugins'])) {
                update_option('active_plugins', $snapshot['active_plugins'], false);
            }

            if (!empty($snapshot['sitewide_plugins']) && function_exists('update_site_option')) {
                update_site_option('active_sitewide_plugins', $snapshot['sitewide_plugins']);
            }
        }

        delete_option('cured_activation_in_progress');

        update_option('cured_activation_rollback_log', [
            'time'     => time(),
            'error'    => $error,
            'snapshot' => $snapshot,
        ], false);
    }
}
