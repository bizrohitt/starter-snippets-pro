<?php
/**
 * Templates Page – manage snippet templates.
 *
 * @package StarterSnippets\Admin
 */

namespace StarterSnippets\Admin;

use StarterSnippets\Core\Config;
use StarterSnippets\Database\Repository;
use StarterSnippets\Modules\TemplateManager\TemplateManager;
use StarterSnippets\Security\Permissions;
use StarterSnippets\Security\NonceHandler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TemplatesPage {

    private const MENU_SLUG = 'starter-snippets-templates';

    public function __construct(
        private TemplateManager $template_manager,
    ) {}

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'add_submenu' ] );
        add_action( 'admin_init', [ $this, 'handle_actions' ] );
    }

    public function add_submenu(): void {
        add_submenu_page(
            Config::MENU_SLUG,
            __( 'Templates', 'starter-snippets' ),
            __( 'Templates', 'starter-snippets' ),
            Config::CAPABILITY,
            self::MENU_SLUG,
            [ $this, 'render' ]
        );
    }

    /**
     * Handle bulk and single actions.
     */
    public function handle_actions(): void {
        if ( ! isset( $_GET['action'] ) && ! isset( $_POST['action'] ) ) {
            return;
        }

        Permissions::check_or_die();

        $action = sanitize_key( $_GET['action'] ?? $_POST['action'] ?? '' );

        if ( 'delete' === $action && isset( $_GET['template_id'] ) ) {
            $this->handle_delete( absint( $_GET['template_id'] ) );
        }

        if ( 'use-template' === $action && isset( $_GET['template_id'] ) ) {
            $this->handle_use_template( absint( $_GET['template_id'] ) );
        }

        if ( 'reinstall' === $action ) {
            $this->handle_reinstall();
        }
    }

    /**
     * Delete a template.
     */
    private function handle_delete( int $template_id ): void {
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'delete_template_' . $template_id ) ) {
            wp_die( __( 'Security check failed.', 'starter-snippets' ) );
        }

        $result = $this->template_manager->delete( $template_id );

        if ( is_array( $result ) ) {
            wp_safe_redirect( add_query_arg( 'error', urlencode( implode( ', ', $result ) ), wp_get_referer() ) );
            exit;
        }

        wp_safe_redirect( add_query_arg( 'message', 'template_deleted', remove_query_arg( [ 'action', 'template_id', '_wpnonce' ], wp_get_referer() ) ) );
        exit;
    }

    /**
     * Use a template to create a new snippet.
     */
    private function handle_use_template( int $template_id ): void {
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'use_template_' . $template_id ) ) {
            wp_die( __( 'Security check failed.', 'starter-snippets' ) );
        }

        $snippet_id = $this->template_manager->create_snippet_from_template( $template_id );

        if ( ! $snippet_id ) {
            wp_safe_redirect( add_query_arg( 'error', urlencode( __( 'Failed to create snippet from template.', 'starter-snippets' ) ), wp_get_referer() ) );
            exit;
        }

        wp_safe_redirect( admin_url( 'admin.php?page=' . Config::MENU_SLUG . '-add&id=' . $snippet_id . '&from_template=1' ) );
        exit;
    }

    /**
     * Reinstall built-in templates.
     */
    private function handle_reinstall(): void {
        NonceHandler::verify_or_die();

        $installer = new \StarterSnippets\Modules\TemplateManager\TemplateInstaller( $this->template_manager->get_repository() );
        $installer->reinstall_builtin_templates();

        wp_safe_redirect( add_query_arg( 'message', 'templates_reinstalled', remove_query_arg( 'action', wp_get_referer() ) ) );
        exit;
    }

    /**
     * Render the templates page.
     */
    public function render(): void {
        Permissions::check_or_die();

        $filter_type  = sanitize_key( $_GET['filter_type'] ?? 'all' );
        $filter_lang  = sanitize_key( $_GET['filter_language'] ?? '' );
        $search       = sanitize_text_field( $_GET['s'] ?? '' );
        $page         = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $per_page     = 20;
        $offset       = ( $page - 1 ) * $per_page;

        $args = [
            'per_page' => $per_page,
            'offset'   => $offset,
        ];

        if ( 'builtin' === $filter_type ) {
            $args['is_builtin'] = 1;
        } elseif ( 'user' === $filter_type ) {
            $args['is_builtin'] = 0;
        }

        if ( ! empty( $filter_lang ) ) {
            $args['language'] = $filter_lang;
        }

        if ( ! empty( $search ) ) {
            $args['search'] = $search;
        }

        $templates = $this->template_manager->get_all( $args );
        $total     = $this->template_manager->count( $args );

        $messages = [];
        if ( ! empty( $_GET['message'] ) ) {
            $msg = sanitize_text_field( $_GET['message'] );
            if ( 'template_deleted' === $msg ) {
                $messages[] = __( 'Template deleted successfully.', 'starter-snippets' );
            } elseif ( 'templates_reinstalled' === $msg ) {
                $messages[] = __( 'Built-in templates reinstalled.', 'starter-snippets' );
            }
        }

        $errors = [];
        if ( ! empty( $_GET['error'] ) ) {
            $errors[] = urldecode( sanitize_text_field( $_GET['error'] ) );
        }

        $builtin_count = $this->template_manager->count( [ 'is_builtin' => 1 ] );
        $user_count    = $this->template_manager->count( [ 'is_builtin' => 0 ] );

        include STARTER_SNIPPETS_DIR . 'templates/admin/templates-page.php';
    }
}
