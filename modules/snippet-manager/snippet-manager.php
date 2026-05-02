<?php
/**
 * Snippet Manager – business logic for snippet CRUD.
 *
 * @package StarterSnippets\Modules\SnippetManager
 */

namespace StarterSnippets\Modules\SnippetManager;

use StarterSnippets\Database\Repository;
use StarterSnippets\Helpers\Logger;
use StarterSnippets\Helpers\Sanitization;
use StarterSnippets\Helpers\Validation;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SnippetManager {

    public function __construct(
        private Repository $repo,
        private Logger $logger,
    ) {}

    /**
     * Create a new snippet from raw form input.
     *
     * @param array<string,mixed> $raw  Raw input data.
     * @return int|array  Snippet ID on success, array of errors on failure.
     */
    public function create( array $raw ): int|array {
        $data   = Sanitization::snippet( $raw );
        $errors = Validation::snippet( $data );

        if ( ! empty( $errors ) ) {
            return $errors;
        }

        /**
         * Filters snippet data before creation.
         *
         * @param array $data Sanitized snippet data.
         */
        $data = apply_filters( 'starter_snippets_before_create', $data );

        $id = $this->repo->create( $data );

        if ( false === $id ) {
            return [ __( 'Failed to save snippet. Database error.', 'starter-snippets' ) ];
        }

        $this->logger->info( $id, sprintf( 'Snippet "%s" created.', $data['title'] ) );

        /**
         * Fires after a snippet is created.
         *
         * @param int   $id   Snippet ID.
         * @param array $data Snippet data.
         */
        do_action( 'starter_snippets_after_create', $id, $data );

        return $id;
    }

    /**
     * Update an existing snippet.
     *
     * @return true|array  True on success, errors array on failure.
     */
    public function update( int $id, array $raw ): true|array {
        $existing = $this->repo->find( $id );

        if ( ! $existing ) {
            return [ __( 'Snippet not found.', 'starter-snippets' ) ];
        }

        $data   = Sanitization::snippet( $raw );
        $errors = Validation::snippet( $data );

        if ( ! empty( $errors ) ) {
            return $errors;
        }

        /** @see starter_snippets_before_update */
        $data = apply_filters( 'starter_snippets_before_update', $data, $id );

        $this->repo->update( $id, $data );

        $this->logger->info( $id, sprintf( 'Snippet "%s" updated.', $data['title'] ) );

        do_action( 'starter_snippets_after_update', $id, $data );

        return true;
    }

    /**
     * Delete a snippet.
     */
    public function delete( int $id ): bool {
        $snippet = $this->repo->find( $id );

        if ( ! $snippet ) {
            return false;
        }

        $this->repo->delete( $id );
        $this->logger->info( $id, sprintf( 'Snippet "%s" deleted.', $snippet->title ) );

        do_action( 'starter_snippets_after_delete', $id );

        return true;
    }

    /**
     * Toggle snippet status.
     */
    public function toggle( int $id ): ?string {
        $snippet = $this->repo->find( $id );

        if ( ! $snippet ) {
            return null;
        }

        $new_status = 'active' === $snippet->status ? 'inactive' : 'active';
        $this->repo->update( $id, [ 'status' => $new_status ] );

        $event = 'active' === $new_status ? 'activated' : 'deactivated';
        $this->logger->log( $id, $event, sprintf( 'Snippet "%s" %s.', $snippet->title, $event ) );

        do_action( 'starter_snippets_status_changed', $id, $new_status );

        return $new_status;
    }

    /**
     * Bulk action on multiple snippet IDs.
     *
     * @param int[]  $ids
     * @param string $action  activate | deactivate | delete
     */
    public function bulk_action( array $ids, string $action ): int {
        $count = 0;

        foreach ( $ids as $id ) {
            $id = absint( $id );
            if ( ! $id ) {
                continue;
            }

            match ( $action ) {
                'activate'   => $this->repo->update( $id, [ 'status' => 'active' ] ),
                'deactivate' => $this->repo->update( $id, [ 'status' => 'inactive' ] ),
                'delete'     => $this->delete( $id ),
                default      => null,
            };

            $count++;
        }

        return $count;
    }

    /**
     * Get a snippet by ID.
     */
    public function get( int $id ): ?object {
        return $this->repo->find( $id );
    }

    /**
     * Get the repository (for list table, etc.).
     */
    public function get_repository(): Repository {
        return $this->repo;
    }
}
