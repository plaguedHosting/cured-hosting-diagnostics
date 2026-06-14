<?php
/**
 * Plugin Name: Cured Hosting Diagnostics
 * Plugin URI: https://github.com/plaguedHosting/cured-hosting-diagnostics
 * Description: Runtime Safety Kernel for Managed WordPress.
 * Version: 0.1.0-dev
 * Requires at least: 6.3
 * Requires PHP: 8.1
 * Author: Cured Hosting
 * License: GPL-3.0-or-later
 * Text Domain: cured-hosting-diagnostics
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/src/Core/Activation.php';
require_once __DIR__ . '/src/Core/Bootstrap.php';

register_activation_hook(__FILE__, ['Cured\Core\Activation', 'activate']);
register_deactivation_hook(__FILE__, ['Cured\Core\Activation', 'deactivate']);

\Cured\Core\Bootstrap::init();
