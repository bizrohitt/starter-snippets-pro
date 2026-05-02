<?php
/**
 * REST API Controller.
 *
 * @package StarterSnippets\Api
 */

namespace StarterSnippets\Api;

use StarterSnippets\Core\Config;
use StarterSnippets\Modules\SnippetManager\SnippetManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RestController {

    public function __construct(
        private SnippetManager $manager,
    ) {}

    /**
     * Register REST routes.
     */
    public function register_routes(): void {
        $ns = Config::REST_NAMESPACE;

        // GET /snippets
        register_rest_route( $ns, '/snippets', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'list_snippets' ],
            'permission_callback' => [ $this, 'check_permissions' ],
            'args'                => [
                'status'   => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
                'language' => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
                'per_page' => [ 'type' => 'integer', 'default' => 20, 'sanitize_callback' => 'absint' ],
                'page'     => [ 'type' => 'integer', 'default' => 1, 'sanitize_callback' => 'absint' ],
            ],
        ] );

        // GET /snippets/{id}
        register_rest_route( $ns, '/snippets/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_snippet' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // POST /snippets
        register_rest_route( $ns, '/snippets', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'create_snippet' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // PUT /snippets/{id}
        register_rest_route( $ns, '/snippets/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::EDITABLE,
            'callback'            => [ $this, 'update_snippet' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // DELETE /snippets/{id}
        register_rest_route( $ns, '/snippets/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::DELETABLE,
            'callback'            => [ $this, 'delete_snippet' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // POST /snippets/{id}/toggle
        register_rest_route( $ns, '/snippets/(?P<id>\d+)/toggle', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'toggle_snippet' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );
    }

    /**
     * Permission check for all REST endpoints.
     */
    public function check_permissions(): bool {
        return current_user_can( Config::CAPABILITY );
    }

    /**
     * List snippets.
     */
    public function list_snippets( \WP_REST_Request $request ): \WP_REST_Response {
        $repo     = $this->manager->get_repository();
        $per_page = $request->get_param( 'per_page' ) ?: 20;
        $page     = $request->get_param( 'page' ) ?: 1;

        $args = [
            'status'   => $request->get_param( 'status' ),
            'language' => $request->get_param( 'language' ),
            'per_page' => $per_page,
            'offset'   => ( $page - 1 ) * $per_page,
        ];

        $snippets = $repo->find_all( $args );
        $total    = $repo->count( $args );

        $response = new \WP_REST_Response( $snippets );
        $response->header( 'X-WP-Total', $total );
        $response->header( 'X-WP-TotalPages', (int) ceil( $total / $per_page ) );

        return $response;
    }

    /**
     * Get a single snippet.
     */
    public function get_snippet( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $snippet = $this->manager->get( (int) $request->get_param( 'id' ) );

        if ( ! $snippet ) {
            return new \WP_Error( 'not_found', __( 'Snippet not found.', 'starter-snippets' ), [ 'status' => 404 ] );
        }

        $repo                 = $this->manager->get_repository();
        $snippet->conditions  = $repo->get_conditions( (int) $snippet->id );

        return new \WP_REST_Response( $snippet );
    }

    /**
     * Create a snippet.
     */
    public function create_snippet( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $result = $this->manager->create( $request->get_params() );

        if ( is_array( $result ) ) {
            return new \WP_Error( 'validation_error', implode( ' ', $result ), [ 'status' => 400 ] );
        }

        $snippet = $this->manager->get( $result );
        return new \WP_REST_Response( $snippet, 201 );
    }

    /**
     * Update a snippet.
     */
    public function update_snippet( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $id     = (int) $request->get_param( 'id' );
        $result = $this->manager->update( $id, $request->get_params() );

        if ( is_array( $result ) ) {
            return new \WP_Error( 'validation_error', implode( ' ', $result ), [ 'status' => 400 ] );
        }

        $snippet = $this->manager->get( $id );
        return new \WP_REST_Response( $snippet );
    }

    /**
     * Delete a snippet.
     */
    public function delete_snippet( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $id = (int) $request->get_param( 'id' );

        if ( ! $this->manager->delete( $id ) ) {
            return new \WP_Error( 'not_found', __( 'Snippet not found.', 'starter-snippets' ), [ 'status' => 404 ] );
        }

        return new \WP_REST_Response( [ 'deleted' => true ] );
    }

    /**
     * Toggle snippet status.
     */
    public function toggle_snippet( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $id         = (int) $request->get_param( 'id' );
        $new_status = $this->manager->toggle( $id );

        if ( null === $new_status ) {
            return new \WP_Error( 'not_found', __( 'Snippet not found.', 'starter-snippets' ), [ 'status' => 404 ] );
        }

        return new \WP_REST_Response( [ 'id' => $id, 'status' => $new_status ] );
    }
}
