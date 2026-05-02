<?php
/**
 * Template Manager – handles template CRUD operations.
 *
 * @package StarterSnippets\Modules\TemplateManager
 */

namespace StarterSnippets\Modules\TemplateManager;

use StarterSnippets\Database\Repository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TemplateManager {

    private Repository $repository;

    public function __construct( Repository $repository ) {
        $this->repository = $repository;
    }

    /**
     * Get repository instance.
     */
    public function get_repository(): Repository {
        return $this->repository;
    }

    /**
     * Get a single template.
     */
    public function get( int $id ): ?object {
        return $this->repository->find_template( $id );
    }

    /**
     * Get all templates with filters.
     *
     * @param array<string,mixed> $args
     * @return array<object>
     */
    public function get_all( array $args = [] ): array {
        return $this->repository->find_all_templates( $args );
    }

    /**
     * Count templates.
     */
    public function count( array $args = [] ): int {
        return $this->repository->count_templates( $args );
    }

    /**
     * Create a template from raw data.
     *
     * @param array<string,mixed> $data
     * @return int|array<string> Returns template ID on success, or array of errors on failure.
     */
    public function create( array $data ): int|array {
        $errors = $this->validate( $data );

        if ( ! empty( $errors ) ) {
            return $errors;
        }

        $template_id = $this->repository->create_template( [
            'title'       => sanitize_text_field( $data['title'] ?? '' ),
            'description' => sanitize_textarea_field( $data['description'] ?? '' ),
            'code'        => $data['code'] ?? '',
            'language'    => sanitize_key( $data['language'] ?? 'php' ),
            'location'    => sanitize_key( $data['location'] ?? 'everywhere' ),
            'tags'        => sanitize_text_field( $data['tags'] ?? '' ),
            'is_builtin'  => 0,
        ] );

        if ( false === $template_id ) {
            return [ __( 'Failed to create template.', 'starter-snippets' ) ];
        }

        return $template_id;
    }

    /**
     * Update a template.
     *
     * @param array<string,mixed> $data
     * @return bool|array<string> Returns true on success, or array of errors on failure.
     */
    public function update( int $id, array $data ): bool|array {
        $template = $this->repository->find_template( $id );

        if ( ! $template ) {
            return [ __( 'Template not found.', 'starter-snippets' ) ];
        }

        if ( $template->is_builtin ) {
            return [ __( 'Cannot modify built-in templates.', 'starter-snippets' ) ];
        }

        $errors = $this->validate( $data, true );

        if ( ! empty( $errors ) ) {
            return $errors;
        }

        return $this->repository->update_template( $id, [
            'title'       => sanitize_text_field( $data['title'] ?? '' ),
            'description' => sanitize_textarea_field( $data['description'] ?? '' ),
            'code'        => $data['code'] ?? '',
            'language'    => sanitize_key( $data['language'] ?? 'php' ),
            'location'    => sanitize_key( $data['location'] ?? 'everywhere' ),
            'tags'        => sanitize_text_field( $data['tags'] ?? '' ),
        ] );
    }

    /**
     * Delete a template.
     */
    public function delete( int $id ): bool|array {
        $template = $this->repository->find_template( $id );

        if ( ! $template ) {
            return [ __( 'Template not found.', 'starter-snippets' ) ];
        }

        if ( $template->is_builtin ) {
            return [ __( 'Cannot delete built-in templates.', 'starter-snippets' ) ];
        }

        return $this->repository->delete_template( $id );
    }

    /**
     * Create a snippet from a template.
     *
     * @param int $template_id
     * @return int|false
     */
    public function create_snippet_from_template( int $template_id ): int|false {
        $template = $this->repository->find_template( $template_id );

        if ( ! $template ) {
            return false;
        }

        $this->repository->increment_template_usage( $template_id );

        return $this->repository->create( [
            'title'       => $template->title,
            'description' => $template->description,
            'code'        => $template->code,
            'language'    => $template->language,
            'location'    => $template->location,
            'tags'        => $template->tags,
            'status'      => 'inactive',
        ] );
    }

    /**
     * Duplicate a snippet as a user template.
     *
     * @param int $snippet_id
     * @return int|array<string>
     */
    public function duplicate_from_snippet( int $snippet_id ): int|array {
        $snippet = $this->repository->find( $snippet_id );

        if ( ! $snippet ) {
            return [ __( 'Snippet not found.', 'starter-snippets' ) ];
        }

        return $this->create( [
            'title'       => $snippet->title . ' (Template)',
            'description' => $snippet->description,
            'code'        => $snippet->code,
            'language'    => $snippet->language,
            'location'    => $snippet->location,
            'tags'        => $snippet->tags,
        ] );
    }

    /**
     * Validate template data.
     *
     * @param array<string,mixed> $data
     * @param bool $is_update
     * @return array<string>
     */
    private function validate( array $data, bool $is_update = false ): array {
        $errors = [];

        if ( empty( $data['title'] ) ) {
            $errors[] = __( 'Template title is required.', 'starter-snippets' );
        } elseif ( strlen( $data['title'] ) > 255 ) {
            $errors[] = __( 'Template title must be 255 characters or less.', 'starter-snippets' );
        }

        if ( empty( $data['code'] ) ) {
            $errors[] = __( 'Template code is required.', 'starter-snippets' );
        }

        $allowed_languages = [ 'php', 'js', 'css', 'html' ];
        if ( ! empty( $data['language'] ) && ! in_array( $data['language'], $allowed_languages, true ) ) {
            $errors[] = __( 'Invalid language selected.', 'starter-snippets' );
        }

        return $errors;
    }
}
