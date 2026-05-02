<?php
/**
 * Import / Export module.
 *
 * @package StarterSnippets\Modules\ImportExport
 */

namespace StarterSnippets\Modules\ImportExport;

use StarterSnippets\Core\Config;
use StarterSnippets\Database\Repository;
use StarterSnippets\Helpers\Logger;
use StarterSnippets\Helpers\Sanitization;
use StarterSnippets\Helpers\Validation;
use StarterSnippets\Security\Permissions;
use StarterSnippets\Security\NonceHandler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ImportExport {

    public function __construct(
        private Repository $repo,
        private Logger $logger,
    ) {}

    /**
     * Register admin hooks for import/export page and handlers.
     */
    public function register_admin_hooks(): void {
        add_action( 'admin_menu', [ $this, 'add_submenu' ] );
        add_action( 'admin_init', [ $this, 'handle_export' ] );
        add_action( 'admin_init', [ $this, 'handle_import' ] );
    }

    /**
     * Add the import/export submenu page.
     */
    public function add_submenu(): void {
        add_submenu_page(
            Config::MENU_SLUG,
            __( 'Import / Export', 'starter-snippets' ),
            __( 'Import / Export', 'starter-snippets' ),
            Config::CAPABILITY,
            Config::MENU_SLUG . '-import-export',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Render the import/export page.
     */
    public function render_page(): void {
        Permissions::check_or_die();
        ?>
        <div class="wrap starter-snippets-wrap">
            <h1><?php esc_html_e( 'Import / Export Snippets', 'starter-snippets' ); ?></h1>

            <?php if ( isset( $_GET['import'] ) && 'success' === $_GET['import'] ) : ?>
                <div class="notice notice-success"><p><?php esc_html_e( 'Snippets imported successfully.', 'starter-snippets' ); ?></p></div>
            <?php elseif ( isset( $_GET['import'] ) && 'error' === $_GET['import'] ) : ?>
                <div class="notice notice-error"><p><?php echo esc_html( $_GET['message'] ?? __( 'Import failed.', 'starter-snippets' ) ); ?></p></div>
            <?php endif; ?>

            <div class="starter-snippets-cards">
                <!-- Export -->
                <div class="starter-snippets-card">
                    <h2><?php esc_html_e( 'Export Snippets', 'starter-snippets' ); ?></h2>
                    <p><?php esc_html_e( 'Download all snippets as a JSON file.', 'starter-snippets' ); ?></p>
                    <form method="post">
                        <?php NonceHandler::field(); ?>
                        <input type="hidden" name="starter_snippets_action" value="export">
                        <?php submit_button( __( 'Export All Snippets', 'starter-snippets' ), 'primary', 'submit', false ); ?>
                    </form>
                </div>

                <!-- Import -->
                <div class="starter-snippets-card">
                    <h2><?php esc_html_e( 'Import Snippets', 'starter-snippets' ); ?></h2>
                    <p><?php esc_html_e( 'Upload a JSON file previously exported from this plugin.', 'starter-snippets' ); ?></p>
                    <form method="post" enctype="multipart/form-data">
                        <?php NonceHandler::field(); ?>
                        <input type="hidden" name="starter_snippets_action" value="import">
                        <input type="file" name="import_file" accept=".json" required>
                        <p>
                            <?php submit_button( __( 'Import Snippets', 'starter-snippets' ), 'primary', 'submit', false ); ?>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle export request.
     */
    public function handle_export(): void {
        if ( ! isset( $_POST['starter_snippets_action'] ) || 'export' !== $_POST['starter_snippets_action'] ) {
            return;
        }

        Permissions::check_or_die();
        NonceHandler::verify_or_die();

        $snippets = $this->repo->find_all( [ 'per_page' => 9999 ] );
        $export   = [];

        foreach ( $snippets as $snippet ) {
            $conditions = $this->repo->get_conditions( (int) $snippet->id );

            $export[] = [
                'title'       => $snippet->title,
                'description' => $snippet->description,
                'code'        => $snippet->code,
                'language'    => $snippet->language,
                'location'    => $snippet->location,
                'priority'    => (int) $snippet->priority,
                'status'      => $snippet->status,
                'tags'        => $snippet->tags,
                'conditions'  => array_map( function ( $c ) {
                    return [
                        'condition_type'     => $c->condition_type,
                        'condition_value'    => $c->condition_value,
                        'condition_operator' => $c->condition_operator,
                    ];
                }, $conditions ),
            ];
        }

        $filename = 'starter-snippets-export-' . gmdate( 'Y-m-d' ) . '.json';

        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );

        echo wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
        exit;
    }

    /**
     * Handle import request.
     */
    public function handle_import(): void {
        if ( ! isset( $_POST['starter_snippets_action'] ) || 'import' !== $_POST['starter_snippets_action'] ) {
            return;
        }

        Permissions::check_or_die();
        NonceHandler::verify_or_die();

        $redirect_base = admin_url( 'admin.php?page=' . Config::MENU_SLUG . '-import-export' );

        if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
            wp_safe_redirect( add_query_arg( [ 'import' => 'error', 'message' => 'No file uploaded.' ], $redirect_base ) );
            exit;
        }

        $content = file_get_contents( $_FILES['import_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $data    = json_decode( $content, true );

        if ( ! Validation::import_data( $data ) ) {
            wp_safe_redirect( add_query_arg( [ 'import' => 'error', 'message' => 'Invalid import file format.' ], $redirect_base ) );
            exit;
        }

        $imported = 0;
        foreach ( $data as $item ) {
            $sanitized = Sanitization::snippet( $item );
            // Always import as inactive for safety.
            $sanitized['status'] = 'inactive';

            $id = $this->repo->create( $sanitized );
            if ( false !== $id ) {
                $imported++;
            }
        }

        $this->logger->info( 0, sprintf( 'Imported %d snippets.', $imported ) );

        wp_safe_redirect( add_query_arg( 'import', 'success', $redirect_base ) );
        exit;
    }
}
