<?php
/**
 * Snippets List Page — WP_List_Table implementation.
 *
 * @package StarterSnippets\Admin
 */

namespace StarterSnippets\Admin;

use StarterSnippets\Core\Config;
use StarterSnippets\Modules\SnippetManager\SnippetManager;
use StarterSnippets\Security\Permissions;
use StarterSnippets\Security\NonceHandler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SnippetsPage {

    public function __construct(
        private SnippetManager $manager,
    ) {}

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'add_submenu' ] );
        add_action( 'admin_init', [ $this, 'handle_actions' ] );
        add_action( 'wp_ajax_starter_snippets_toggle', [ $this, 'ajax_toggle' ] );
    }

    public function add_submenu(): void {
        add_submenu_page(
            Config::MENU_SLUG,
            __( 'All Snippets', 'starter-snippets' ),
            __( 'All Snippets', 'starter-snippets' ),
            Config::CAPABILITY,
            Config::MENU_SLUG,  // Same slug as parent to replace the duplicated menu.
            [ new Dashboard( $this->manager->get_repository() ), 'render' ]
        );
    }

    /**
     * Handle bulk actions and single-row actions.
     */
    public function handle_actions(): void {
        // Single delete.
        if ( isset( $_GET['action'], $_GET['snippet_id'], $_GET['_wpnonce'] )
             && 'delete' === $_GET['action']
             && wp_verify_nonce( $_GET['_wpnonce'], 'starter_snippets_delete_' . $_GET['snippet_id'] )
        ) {
            Permissions::check_or_die();
            $this->manager->delete( absint( $_GET['snippet_id'] ) );
            wp_safe_redirect( admin_url( 'admin.php?page=' . Config::MENU_SLUG . '&deleted=1' ) );
            exit;
        }

        // Bulk actions.
        if ( isset( $_POST['bulk_action'], $_POST['snippet_ids'] ) ) {
            NonceHandler::verify_or_die();
            Permissions::check_or_die();

            $action = sanitize_text_field( $_POST['bulk_action'] );
            $ids    = array_map( 'absint', (array) $_POST['snippet_ids'] );

            if ( in_array( $action, [ 'activate', 'deactivate', 'delete' ], true ) && ! empty( $ids ) ) {
                $this->manager->bulk_action( $ids, $action );
            }

            wp_safe_redirect( admin_url( 'admin.php?page=' . Config::MENU_SLUG . '&bulk=1' ) );
            exit;
        }
    }

    /**
     * AJAX toggle snippet status.
     */
    public function ajax_toggle(): void {
        check_ajax_referer( NonceHandler::action(), 'nonce' );
        Permissions::check_or_die();

        $id         = absint( $_POST['snippet_id'] ?? 0 );
        $new_status = $this->manager->toggle( $id );

        if ( null === $new_status ) {
            wp_send_json_error( [ 'message' => __( 'Snippet not found.', 'starter-snippets' ) ] );
        }

        wp_send_json_success( [
            'status' => $new_status,
            'label'  => 'active' === $new_status
                ? __( 'Active', 'starter-snippets' )
                : __( 'Inactive', 'starter-snippets' ),
        ] );
    }
}
