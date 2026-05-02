<?php
/**
 * Cloud Library – browse and share community snippets.
 *
 * @package StarterSnippets\Modules\CloudLibrary
 */

namespace StarterSnippets\Modules\CloudLibrary;

use StarterSnippets\Core\Config;
use StarterSnippets\Database\Repository;
use StarterSnippets\Security\Permissions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CloudLibrary {

    private const MENU_SLUG = 'starter-snippets-cloud';

    public function __construct(
        private Repository $repository,
        private LibraryClient $client,
    ) {}

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'add_submenu' ] );
        add_action( 'admin_init', [ $this, 'handle_actions' ] );
        add_action( 'wp_ajax_starter_snippets_fetch_library', [ $this, 'ajax_fetch_library' ] );
        add_action( 'wp_ajax_starter_snippets_import_library_snippet', [ $this, 'ajax_import_snippet' ] );
    }

    public function add_submenu(): void {
        add_submenu_page(
            Config::MENU_SLUG,
            __( 'Cloud Library', 'starter-snippets' ),
            __( 'Cloud Library', 'starter-snippets' ),
            Config::CAPABILITY,
            self::MENU_SLUG,
            [ $this, 'render' ]
        );
    }

    public function handle_actions(): void {
        if ( ! isset( $_GET['action'] ) ) {
            return;
        }

        Permissions::check_or_die();

        $action = sanitize_key( $_GET['action'] );

        if ( 'import' === $action && isset( $_GET['snippet_id'] ) ) {
            $this->handle_import( absint( $_GET['snippet_id'] ) );
        }
    }

    private function handle_import( int $snippet_id ): void {
        check_admin_referer( 'cloud_import_' . $snippet_id );

        $result = $this->client->fetch_snippet( $snippet_id );

        if ( ! $result['success'] || ! $result['snippet'] ) {
            wp_safe_redirect( add_query_arg( 'error', urlencode( $result['message'] ?? 'Failed to fetch snippet' ), wp_get_referer() ) );
            exit;
        }

        $snippet_data = $result['snippet'];

        $this->repository->create( [
            'title'       => sanitize_text_field( $snippet_data['title'] ?? 'Imported Snippet' ),
            'description' => sanitize_textarea_field( $snippet_data['description'] ?? '' ),
            'code'        => $snippet_data['code'] ?? '',
            'language'    => sanitize_key( $snippet_data['language'] ?? 'php' ),
            'location'    => sanitize_key( $snippet_data['location'] ?? 'everywhere' ),
            'tags'        => sanitize_text_field( $snippet_data['tags'] ?? '' ),
            'status'      => 'inactive',
        ] );

        wp_safe_redirect( add_query_arg( 'message', 'snippet_imported', admin_url( 'admin.php?page=' . Config::MENU_SLUG ) ) );
        exit;
    }

    public function ajax_fetch_library(): void {
        check_ajax_referer( 'starter_snippets_nonce' );

        if ( ! current_user_can( Config::CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'starter-snippets' ) ] );
        }

        $args = [
            'category' => sanitize_text_field( $_POST['category'] ?? '' ),
            'language' => sanitize_key( $_POST['language'] ?? '' ),
            'search'   => sanitize_text_field( $_POST['search'] ?? '' ),
            'page'     => absint( $_POST['page'] ?? 1 ),
        ];

        $result = $this->client->fetch_snippets( $args );

        wp_send_json( $result );
    }

    public function ajax_import_snippet(): void {
        check_ajax_referer( 'starter_snippets_nonce' );

        if ( ! current_user_can( Config::CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'starter-snippets' ) ] );
        }

        $snippet_id = absint( $_POST['snippet_id'] ?? 0 );

        if ( ! $snippet_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid snippet ID.', 'starter-snippets' ) ] );
        }

        $result = $this->client->fetch_snippet( $snippet_id );

        if ( ! $result['success'] || ! $result['snippet'] ) {
            wp_send_json_error( [ 'message' => $result['message'] ?? 'Failed to fetch snippet' ] );
        }

        $snippet_data = $result['snippet'];

        $new_id = $this->repository->create( [
            'title'       => sanitize_text_field( $snippet_data['title'] ?? 'Imported Snippet' ),
            'description' => sanitize_textarea_field( $snippet_data['description'] ?? '' ),
            'code'        => $snippet_data['code'] ?? '',
            'language'    => sanitize_key( $snippet_data['language'] ?? 'php' ),
            'location'    => sanitize_key( $snippet_data['location'] ?? 'everywhere' ),
            'tags'        => sanitize_text_field( $snippet_data['tags'] ?? '' ),
            'status'      => 'inactive',
        ] );

        if ( $new_id ) {
            wp_send_json_success( [
                'message'    => __( 'Snippet imported successfully!', 'starter-snippets' ),
                'snippet_id' => $new_id,
            ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Failed to save snippet.', 'starter-snippets' ) ] );
        }
    }

    public function render(): void {
        Permissions::check_or_die();

        $messages = [];
        if ( ! empty( $_GET['message'] ) ) {
            if ( 'snippet_imported' === $_GET['message'] ) {
                $messages[] = __( 'Snippet imported successfully! You can find it in your snippets list.', 'starter-snippets' );
            }
        }

        $errors = [];
        if ( ! empty( $_GET['error'] ) ) {
            $errors[] = urldecode( sanitize_text_field( $_GET['error'] ) );
        }

        include STARTER_SNIPPETS_DIR . 'templates/admin/cloud-library.php';
    }
}
