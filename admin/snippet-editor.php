<?php
/**
 * Snippet Editor – add / edit form.
 *
 * @package StarterSnippets\Admin
 */

namespace StarterSnippets\Admin;

use StarterSnippets\Core\Config;
use StarterSnippets\Modules\SnippetManager\SnippetManager;
use StarterSnippets\Modules\TemplateManager\TemplateManager;
use StarterSnippets\Security\Permissions;
use StarterSnippets\Security\NonceHandler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SnippetEditor {

    private ?TemplateManager $template_manager = null;

    public function __construct(
        private SnippetManager $manager,
    ) {}

    public function set_template_manager( TemplateManager $tm ): void {
        $this->template_manager = $tm;
    }

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'add_submenu' ] );
        add_action( 'admin_init', [ $this, 'handle_save' ] );
        add_action( 'admin_init', [ $this, 'handle_template_action' ] );
    }

    public function add_submenu(): void {
        add_submenu_page(
            Config::MENU_SLUG,
            __( 'Add Snippet', 'starter-snippets' ),
            __( 'Add New', 'starter-snippets' ),
            Config::CAPABILITY,
            Config::MENU_SLUG . '-add',
            [ $this, 'render' ]
        );
    }

    /**
     * Render the add/edit form.
     */
    public function render(): void {
        Permissions::check_or_die();

        $snippet     = null;
        $conditions  = [];
        $revisions   = [];
        $errors      = [];
        $editing     = false;
        $from_template = false;

        if ( ! empty( $_GET['id'] ) ) {
            $snippet = $this->manager->get( absint( $_GET['id'] ) );
            if ( $snippet ) {
                $editing    = true;
                $repo       = $this->manager->get_repository();
                $conditions = $repo->get_conditions( (int) $snippet->id );
                $revisions  = $repo->get_revisions( (int) $snippet->id );
            }
        }

        if ( ! empty( $_GET['from_template'] ) ) {
            $from_template = true;
        }

        // Show any errors/success messages from save.
        if ( ! empty( $_GET['saved'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Snippet saved successfully.', 'starter-snippets' ) . '</p></div>';
        }

        if ( $from_template ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Snippet created from template! Customize it and save when ready.', 'starter-snippets' ) . '</p></div>';
        }

        if ( ! empty( $_GET['errors'] ) ) {
            $errors = array_map( 'sanitize_text_field', explode( '|', urldecode( $_GET['errors'] ) ) );
        }

        // Get templates for the selector
        $templates = [];
        if ( ! $editing && $this->template_manager ) {
            $templates = $this->template_manager->get_all( [ 'per_page' => 50 ] );
        }

        include STARTER_SNIPPETS_DIR . 'templates/admin/snippet-form.php';
    }

    /**
     * Handle form submission.
     */
    public function handle_save(): void {
        if ( ! isset( $_POST['starter_snippets_action'] ) || 'save_snippet' !== $_POST['starter_snippets_action'] ) {
            return;
        }

        Permissions::check_or_die();
        NonceHandler::verify_or_die();

        $id  = absint( $_POST['snippet_id'] ?? 0 );
        $raw = $_POST;

        if ( $id > 0 ) {
            $result = $this->manager->update( $id, $raw );
        } else {
            $result = $this->manager->create( $raw );
        }

        $base_url = admin_url( 'admin.php?page=' . Config::MENU_SLUG . '-add' );

        if ( is_array( $result ) ) {
            // Errors.
            $error_str = urlencode( implode( '|', $result ) );
            wp_safe_redirect( add_query_arg( [
                'id'     => $id ?: '',
                'errors' => $error_str,
            ], $base_url ) );
            exit;
        }

        // Success — $result is either true (update) or int (new ID).
        $snippet_id = is_int( $result ) ? $result : $id;
        wp_safe_redirect( add_query_arg( [
            'id'    => $snippet_id,
            'saved' => 1,
        ], $base_url ) );
        exit;
    }

    /**
     * Handle template-related actions.
     */
    public function handle_template_action(): void {
        if ( ! isset( $_GET['template_action'] ) ) {
            return;
        }

        Permissions::check_or_die();

        $action = sanitize_key( $_GET['template_action'] );

        if ( 'save_as_template' === $action && isset( $_GET['snippet_id'] ) ) {
            $this->handle_save_as_template( absint( $_GET['snippet_id'] ) );
        }
    }

    /**
     * Save an existing snippet as a template.
     */
    private function handle_save_as_template( int $snippet_id ): void {
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'save_as_template_' . $snippet_id ) ) {
            wp_die( __( 'Security check failed.', 'starter-snippets' ) );
        }

        if ( ! $this->template_manager ) {
            wp_die( __( 'Template manager not available.', 'starter-snippets' ) );
        }

        $result = $this->template_manager->duplicate_from_snippet( $snippet_id );

        $base_url = admin_url( 'admin.php?page=' . Config::MENU_SLUG . '-add&id=' . $snippet_id );

        if ( is_array( $result ) ) {
            wp_safe_redirect( add_query_arg( [
                'error' => urlencode( implode( ', ', $result ) ),
            ], $base_url ) );
            exit;
        }

        wp_safe_redirect( add_query_arg( [
            'message' => 'template_saved',
        ], admin_url( 'admin.php?page=starter-snippets-templates' ) ) );
        exit;
    }
}
