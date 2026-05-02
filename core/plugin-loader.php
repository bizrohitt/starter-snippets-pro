<?php
/**
 * Plugin Loader – wires everything together.
 *
 * @package StarterSnippets\Core
 */

namespace StarterSnippets\Core;

use StarterSnippets\Admin\Dashboard;
use StarterSnippets\Admin\SnippetsPage;
use StarterSnippets\Admin\SnippetEditor;
use StarterSnippets\Admin\SettingsPage;
use StarterSnippets\Api\RestController;
use StarterSnippets\Database\Repository;
use StarterSnippets\Database\Migrations;
use StarterSnippets\Frontend\SnippetRunner;
use StarterSnippets\Helpers\Logger;
use StarterSnippets\Modules\SnippetManager\SnippetManager;
use StarterSnippets\Modules\ConditionEngine\ConditionEngine;
use StarterSnippets\Modules\ImportExport\ImportExport;
use StarterSnippets\Modules\TemplateManager\TemplateManager;
use StarterSnippets\Modules\TemplateManager\TemplateInstaller;
use StarterSnippets\Modules\CloudLibrary\CloudLibrary;
use StarterSnippets\Modules\CloudLibrary\LibraryClient;
use StarterSnippets\Admin\TemplatesPage;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure License Manager is loaded early
require_once STARTER_SNIPPETS_DIR . 'core/license-manager.php';

final class PluginLoader {

    public function init(): void {
        // Run DB migrations if needed.
        Migrations::maybe_upgrade();

        // Load text domain.
        load_plugin_textdomain(
            'starter-snippets',
            false,
            dirname( STARTER_SNIPPETS_BASENAME ) . '/languages'
        );

        // Shared services.
        $repository       = new Repository();
        $logger           = new Logger();
        $condition_engine = new ConditionEngine();
        $snippet_manager  = new SnippetManager( $repository, $logger );
        $import_export    = new ImportExport( $repository, $logger );
        $template_manager = new TemplateManager( $repository );

        // ── Admin ──────────────────────────────────────────
        if ( is_admin() ) {
            $dashboard = new Dashboard( $repository );
            $dashboard->register();

            $snippets_page = new SnippetsPage( $snippet_manager );
            $snippets_page->register();

            $editor = new SnippetEditor( $snippet_manager );
            $editor->set_template_manager( $template_manager );
            $editor->register();

            $settings = new SettingsPage();
            $settings->register();

            $templates_page = new TemplatesPage( $template_manager );
            $templates_page->register();

            $library_client = new LibraryClient();
            $cloud_library  = new CloudLibrary( $repository, $library_client );
            $cloud_library->register();

            $import_export->register_admin_hooks();

            $license_manager = new LicenseManager();
            $license_manager->register();
        }

        // ── Frontend snippet execution ─────────────────────
        $runner = new SnippetRunner( $repository, $condition_engine, $logger );
        $runner->register();

        // ── REST API ───────────────────────────────────────
        $rest = new RestController( $snippet_manager );
        add_action( 'rest_api_init', [ $rest, 'register_routes' ] );

        // ── Admin assets ───────────────────────────────────
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * Enqueue admin CSS / JS on plugin pages only.
     */
    public function enqueue_admin_assets( string $hook ): void {
        $plugin_pages = [
            'toplevel_page_' . Config::MENU_SLUG,
            'starter-snippets_page_starter-snippets-add',
            'starter-snippets_page_starter-snippets-settings',
            'starter-snippets_page_starter-snippets-import-export',
            'starter-snippets_page_starter-snippets-license',
            'starter-snippets_page_starter-snippets-templates',
            'starter-snippets_page_starter-snippets-cloud',
        ];

        if ( ! in_array( $hook, $plugin_pages, true ) ) {
            return;
        }

        wp_enqueue_style(
            'starter-snippets-admin',
            STARTER_SNIPPETS_URL . 'assets/css/admin-style.css',
            [],
            STARTER_SNIPPETS_VERSION
        );

        wp_enqueue_script(
            'starter-snippets-admin',
            STARTER_SNIPPETS_URL . 'assets/js/admin-script.js',
            [ 'jquery', 'wp-util' ],
            STARTER_SNIPPETS_VERSION,
            true
        );

        wp_localize_script( 'starter-snippets-admin', 'StarterSnippets', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'restUrl' => rest_url( Config::REST_NAMESPACE ),
            'nonce'   => wp_create_nonce( 'starter_snippets_nonce' ),
            'i18n'    => [
                'confirmDelete'     => __( 'Are you sure you want to delete this snippet?', 'starter-snippets' ),
                'confirmBulk'       => __( 'Are you sure you want to perform this bulk action?', 'starter-snippets' ),
                'snippetActivated'  => __( 'Snippet activated.', 'starter-snippets' ),
                'snippetDeactivated'=> __( 'Snippet deactivated.', 'starter-snippets' ),
                'error'             => __( 'An error occurred. Please try again.', 'starter-snippets' ),
            ],
        ] );

        // CodeMirror (shipped with WP core).
        $editor_page = 'starter-snippets_page_starter-snippets-add';
        if ( $hook === $editor_page ) {
            $cm_settings = wp_enqueue_code_editor( [ 'type' => 'text/x-php' ] );
            if ( false !== $cm_settings ) {
                wp_localize_script( 'starter-snippets-admin', 'StarterSnippetsCM', $cm_settings );
            }
        }
    }
}
