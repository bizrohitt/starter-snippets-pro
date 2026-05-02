<?php
/**
 * Plugin activation handler.
 *
 * @package StarterSnippets\Bootstrap
 */

namespace StarterSnippets\Bootstrap;

use StarterSnippets\Core\Config;
use StarterSnippets\Database\Schema;
use StarterSnippets\Database\Repository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Activate {

    /**
     * Run on plugin activation.
     */
    public static function run(): void {
        // WordPress version check.
        if ( version_compare( get_bloginfo( 'version' ), STARTER_SNIPPETS_MIN_WP, '<' ) ) {
            deactivate_plugins( STARTER_SNIPPETS_BASENAME );
            wp_die(
                esc_html(
                    sprintf(
                        /* translators: %s: minimum WordPress version */
                        __( 'Starter Snippets requires WordPress %s or higher.', 'starter-snippets' ),
                        STARTER_SNIPPETS_MIN_WP
                    )
                ),
                'Plugin Activation Error',
                [ 'back_link' => true ]
            );
        }

        // Create / update database tables.
        Schema::create_tables();

        // Store the current DB version.
        update_option( Config::DB_VERSION_OPTION, STARTER_SNIPPETS_DB_VERSION );

        // Store default settings if not present.
        if ( false === get_option( Config::SETTINGS_OPTION ) ) {
            update_option( Config::SETTINGS_OPTION, Config::defaults() );
        }

        // Install built-in templates.
        $repository = new Repository();
        require_once STARTER_SNIPPETS_DIR . 'modules/template-manager/template-installer.php';
        require_once STARTER_SNIPPETS_DIR . 'modules/template-manager/starter-templates.php';
        $installer = new \StarterSnippets\Modules\TemplateManager\TemplateInstaller( $repository );
        $installer->install_builtin_templates();

        // Flush rewrite rules for REST API.
        flush_rewrite_rules();
    }
}
