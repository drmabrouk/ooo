<?php
/**
 * Plugin Name: Syndicate Management (إدارة النقابة)
 * Description: نظام شامل لإدارة الأعضاء، التراخيص، والتقارير النقابية والمالية.
 * Version: 97.2.0
 * Author: AHMED MABROUK
 * Language: ar
 * Text Domain: syndicate-management
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SM_VERSION', '97.2.0');
define('SM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SM_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_syndicate_management() {
    require_once SM_PLUGIN_DIR . 'includes/class-sm-activator.php';
    SM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_syndicate_management() {
    require_once SM_PLUGIN_DIR . 'includes/class-sm-deactivator.php';
    SM_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_syndicate_management');
register_deactivation_hook(__FILE__, 'deactivate_syndicate_management');

/**
 * Core class used to maintain the plugin.
 */
require_once SM_PLUGIN_DIR . 'includes/class-syndicate-management.php';

function run_syndicate_management() {
    $plugin = new Syndicate_Management();
    $plugin->run();
}

run_syndicate_management();
