<?php
/**
 * Snippet repository – data access layer.
 *
 * @package StarterSnippets\Database
 */

namespace StarterSnippets\Database;

use StarterSnippets\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Repository {

    private string $table;
    private string $cond_table;
    private string $rev_table;
    private string $tmpl_table;

    public function __construct() {
        $this->table      = Config::table( Config::TABLE_SNIPPETS );
        $this->cond_table = Config::table( Config::TABLE_CONDITIONS );
        $this->rev_table  = Config::table( Config::TABLE_REVISIONS );
        $this->tmpl_table = Config::table( Config::TABLE_TEMPLATES );
    }

    /* ───────────────────────────────────────────────
     *  SNIPPETS CRUD
     * ─────────────────────────────────────────────── */

    /**
     * Get a single snippet by ID.
     *
     * @return object|null
     */
    public function find( int $id ): ?object {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ) );
    }

    /**
     * Get all snippets with optional filters.
     *
     * @param array<string,mixed> $args  Accepted keys: status, language, search, orderby, order, per_page, offset.
     * @return array<object>
     */
    public function find_all( array $args = [] ): array {
        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $params[] = $args['status'];
        }

        if ( ! empty( $args['language'] ) ) {
            $where   .= ' AND language = %s';
            $params[] = $args['language'];
        }

        if ( ! empty( $args['search'] ) ) {
            $where   .= ' AND (title LIKE %s OR description LIKE %s)';
            $like      = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $orderby  = $args['orderby'] ?? 'id';
        $order    = strtoupper( $args['order'] ?? 'DESC' );
        $order    = in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : 'DESC';
        $per_page = (int) ( $args['per_page'] ?? 20 );
        $offset   = (int) ( $args['offset'] ?? 0 );

        $allowed_orderby = [ 'id', 'title', 'language', 'status', 'priority', 'created_at', 'updated_at' ];
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'id';
        }

        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        if ( ! empty( $params ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql = $wpdb->prepare( $sql, ...$params );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_results( $sql );
    }

    /**
     * Count snippets matching filters.
     */
    public function count( array $args = [] ): int {
        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $params[] = $args['status'];
        }

        if ( ! empty( $args['language'] ) ) {
            $where   .= ' AND language = %s';
            $params[] = $args['language'];
        }

        if ( ! empty( $args['search'] ) ) {
            $where   .= ' AND (title LIKE %s OR description LIKE %s)';
            $like      = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$where}";

        if ( ! empty( $params ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql = $wpdb->prepare( $sql, ...$params );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Count snippets by status.
     *
     * @return array<string,int>
     */
    public function count_by_status(): array {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results( "SELECT status, COUNT(*) as cnt FROM {$this->table} GROUP BY status" );

        $counts = [ 'active' => 0, 'inactive' => 0 ];
        foreach ( $rows as $row ) {
            $counts[ $row->status ] = (int) $row->cnt;
        }
        return $counts;
    }

    /**
     * Create a new snippet.
     *
     * @param array<string,mixed> $data
     */
    public function create( array $data ): int|false {
        global $wpdb;

        $result = $wpdb->insert( $this->table, [
            'title'       => $data['title']       ?? '',
            'description' => $data['description'] ?? '',
            'code'        => $data['code']        ?? '',
            'language'    => $data['language']     ?? 'php',
            'location'    => $data['location']     ?? 'everywhere',
            'priority'    => $data['priority']     ?? 10,
            'status'      => $data['status']       ?? 'inactive',
            'tags'        => $data['tags']         ?? '',
            'created_by'  => get_current_user_id(),
        ], [ '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d' ] );

        if ( false === $result ) {
            return false;
        }

        $snippet_id = (int) $wpdb->insert_id;

        // Save conditions.
        if ( ! empty( $data['conditions'] ) && is_array( $data['conditions'] ) ) {
            $this->save_conditions( $snippet_id, $data['conditions'] );
        }

        $this->bust_cache();
        return $snippet_id;
    }

    /**
     * Update an existing snippet.
     *
     * @param array<string,mixed> $data
     */
    public function update( int $id, array $data ): bool {
        global $wpdb;

        $fields = [];
        $format = [];

        $allowed = [ 'title', 'description', 'code', 'language', 'location', 'priority', 'status', 'tags' ];
        foreach ( $allowed as $key ) {
            if ( array_key_exists( $key, $data ) ) {
                $fields[ $key ] = $data[ $key ];
                $format[]       = $key === 'priority' ? '%d' : '%s';
            }
        }

        if ( empty( $fields ) ) {
            return false;
        }

        // Fetch the existing snippet before updating to save the revision.
        $existing = $this->find( $id );
        if ( $existing && isset( $data['code'] ) && $data['code'] !== $existing->code ) {
            $this->save_revision( $id, $existing );
        }

        $result = $wpdb->update( $this->table, $fields, [ 'id' => $id ], $format, [ '%d' ] );

        // Update conditions if provided.
        if ( isset( $data['conditions'] ) && is_array( $data['conditions'] ) ) {
            $this->delete_conditions( $id );
            $this->save_conditions( $id, $data['conditions'] );
        }

        $this->bust_cache();
        return false !== $result;
    }

    /**
     * Delete a snippet and its conditions.
     */
    public function delete( int $id ): bool {
        global $wpdb;

        $this->delete_conditions( $id );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table} WHERE id = %d", $id ) );

        $this->bust_cache();
        return true;
    }

    /**
     * Get all active snippets (cached).
     *
     * @return array<object>
     */
    public function get_active_snippets(): array {
        $cached = wp_cache_get( 'active_snippets', 'starter_snippets' );

        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $snippets = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE status = %s ORDER BY priority ASC, id ASC",
                'active'
            )
        );

        wp_cache_set( 'active_snippets', $snippets, 'starter_snippets', 300 );
        return $snippets;
    }

    /* ───────────────────────────────────────────────
     *  REVISIONS
     * ─────────────────────────────────────────────── */

    /**
     * Save a version of the code snippet as a revision.
     */
    private function save_revision( int $snippet_id, object $snippet ): void {
        global $wpdb;

        $wpdb->insert( $this->rev_table, [
            'snippet_id' => $snippet_id,
            'code'       => $snippet->code,
            'language'   => $snippet->language,
            'created_by' => get_current_user_id()
        ], [ '%d', '%s', '%s', '%d' ] );
    }

    /**
     * Get all revisions for a snippet.
     *
     * @return array<object>
     */
    public function get_revisions( int $snippet_id ): array {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->rev_table} WHERE snippet_id = %d ORDER BY created_at DESC",
                $snippet_id
            )
        );
    }

    /* ───────────────────────────────────────────────
     *  CONDITIONS
     * ─────────────────────────────────────────────── */

    /**
     * Get conditions for a snippet.
     *
     * @return array<object>
     */
    public function get_conditions( int $snippet_id ): array {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->cond_table} WHERE snippet_id = %d",
                $snippet_id
            )
        );
    }

    /**
     * Save conditions for a snippet.
     *
     * @param array<array<string,string>> $conditions
     */
    public function save_conditions( int $snippet_id, array $conditions ): void {
        global $wpdb;

        foreach ( $conditions as $cond ) {
            if ( empty( $cond['condition_type'] ) ) {
                continue;
            }

            $wpdb->insert( $this->cond_table, [
                'snippet_id'         => $snippet_id,
                'condition_type'     => sanitize_text_field( $cond['condition_type'] ),
                'condition_value'    => sanitize_text_field( $cond['condition_value'] ?? '' ),
                'condition_operator' => sanitize_text_field( $cond['condition_operator'] ?? 'include' ),
            ], [ '%d', '%s', '%s', '%s' ] );
        }
    }

    /**
     * Delete all conditions for a snippet.
     */
    public function delete_conditions( int $snippet_id ): void {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->cond_table} WHERE snippet_id = %d", $snippet_id ) );
    }

    /* ───────────────────────────────────────────────
     *  CACHE HELPERS
     * ─────────────────────────────────────────────── */

    /* ───────────────────────────────────────────────
     *  TEMPLATES CRUD
     * ─────────────────────────────────────────────── */

    /**
     * Get a single template by ID.
     *
     * @return object|null
     */
    public function find_template( int $id ): ?object {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tmpl_table} WHERE id = %d", $id ) );
    }

    /**
     * Get all templates with optional filters.
     *
     * @param array<string,mixed> $args  Accepted keys: is_builtin, language, search, orderby, order, per_page, offset.
     * @return array<object>
     */
    public function find_all_templates( array $args = [] ): array {
        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( isset( $args['is_builtin'] ) ) {
            $where   .= ' AND is_builtin = %d';
            $params[] = (int) $args['is_builtin'];
        }

        if ( ! empty( $args['language'] ) ) {
            $where   .= ' AND language = %s';
            $params[] = $args['language'];
        }

        if ( ! empty( $args['search'] ) ) {
            $where   .= ' AND (title LIKE %s OR description LIKE %s)';
            $like      = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $orderby  = $args['orderby'] ?? 'usage_count';
        $order    = strtoupper( $args['order'] ?? 'DESC' );
        $order    = in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : 'DESC';
        $per_page = (int) ( $args['per_page'] ?? 20 );
        $offset   = (int) ( $args['offset'] ?? 0 );

        $allowed_orderby = [ 'id', 'title', 'language', 'created_at', 'usage_count' ];
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'usage_count';
        }

        $sql = "SELECT * FROM {$this->tmpl_table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        if ( ! empty( $params ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql = $wpdb->prepare( $sql, ...$params );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_results( $sql );
    }

    /**
     * Count templates matching filters.
     */
    public function count_templates( array $args = [] ): int {
        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( isset( $args['is_builtin'] ) ) {
            $where   .= ' AND is_builtin = %d';
            $params[] = (int) $args['is_builtin'];
        }

        if ( ! empty( $args['language'] ) ) {
            $where   .= ' AND language = %s';
            $params[] = $args['language'];
        }

        if ( ! empty( $args['search'] ) ) {
            $where   .= ' AND (title LIKE %s OR description LIKE %s)';
            $like      = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT COUNT(*) FROM {$this->tmpl_table} WHERE {$where}";

        if ( ! empty( $params ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql = $wpdb->prepare( $sql, ...$params );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Create a new template.
     *
     * @param array<string,mixed> $data
     */
    public function create_template( array $data ): int|false {
        global $wpdb;

        $result = $wpdb->insert( $this->tmpl_table, [
            'title'       => $data['title']       ?? '',
            'description' => $data['description'] ?? '',
            'code'        => $data['code']        ?? '',
            'language'    => $data['language']     ?? 'php',
            'location'    => $data['location']     ?? 'everywhere',
            'tags'        => $data['tags']         ?? '',
            'is_builtin'  => $data['is_builtin']   ?? 0,
            'created_by'  => get_current_user_id(),
        ], [ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' ] );

        if ( false === $result ) {
            return false;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Update an existing template.
     *
     * @param array<string,mixed> $data
     */
    public function update_template( int $id, array $data ): bool {
        global $wpdb;

        $fields = [];
        $format = [];

        $allowed = [ 'title', 'description', 'code', 'language', 'location', 'tags' ];
        foreach ( $allowed as $key ) {
            if ( array_key_exists( $key, $data ) ) {
                $fields[ $key ] = $data[ $key ];
                $format[]       = '%s';
            }
        }

        if ( empty( $fields ) ) {
            return false;
        }

        $result = $wpdb->update( $this->tmpl_table, $fields, [ 'id' => $id ], $format, [ '%d' ] );

        return false !== $result;
    }

    /**
     * Delete a template.
     */
    public function delete_template( int $id ): bool {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->tmpl_table} WHERE id = %d AND is_builtin = 0", $id ) );

        return $result !== false;
    }

    /**
     * Increment template usage count.
     */
    public function increment_template_usage( int $id ): void {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( $wpdb->prepare( "UPDATE {$this->tmpl_table} SET usage_count = usage_count + 1 WHERE id = %d", $id ) );
    }

    /* ───────────────────────────────────────────────
     *  CACHE HELPERS
     * ─────────────────────────────────────────────── */

    private function bust_cache(): void {
        wp_cache_delete( 'active_snippets', 'starter_snippets' );
        delete_transient( 'starter_snippets_dashboard_stats' );
    }
}
