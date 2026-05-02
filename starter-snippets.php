<?php
/**
 * Plugin Name:       Starter Snippets
 * Plugin URI:        https://developer.wordpress.org/plugins/
 * Description:       A production-ready code snippets manager for WordPress. Create, manage, and conditionally execute PHP, JS, CSS, and HTML snippets.
 * Version:           1.2.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Rohit Saha
 * Author URI:        https://rohitdev.com/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       starter-snippets
 * Domain Path:       /languages
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
define( 'STARTER_SNIPPETS_VERSION', '1.2.0' );
define( 'STARTER_SNIPPETS_DB_VERSION', '1.2.0' );
define( 'STARTER_SNIPPETS_FILE', __FILE__ );
define( 'STARTER_SNIPPETS_DIR', plugin_dir_path( __FILE__ ) );
define( 'STARTER_SNIPPETS_URL', plugin_dir_url( __FILE__ ) );
define( 'STARTER_SNIPPETS_BASENAME', plugin_basename( __FILE__ ) );
define( 'STARTER_SNIPPETS_MIN_WP', '6.0' );
define( 'STARTER_SNIPPETS_MIN_PHP', '8.0' );

// PHP version check.
if ( version_compare( PHP_VERSION, STARTER_SNIPPETS_MIN_PHP, '<' ) ) {
    add_action( 'admin_notices', function () {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: %s: minimum PHP version */
                    __( 'Starter Snippets requires PHP %s or higher. Please update your PHP version.', 'starter-snippets' ),
                    STARTER_SNIPPETS_MIN_PHP
                )
            )
        );
    } );
    return;
}

// Autoloader.
require_once STARTER_SNIPPETS_DIR . 'core/autoloader.php';

// Activation / Deactivation hooks.
register_activation_hook( __FILE__, [ 'StarterSnippets\\Bootstrap\\Activate', 'run' ] );
register_deactivation_hook( __FILE__, [ 'StarterSnippets\\Bootstrap\\Deactivate', 'run' ] );

// Boot the plugin.
add_action( 'plugins_loaded', function () {
    $loader = new StarterSnippets\Core\PluginLoader();
    $loader->init();
} );
