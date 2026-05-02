<?php
/**
 * Logger – writes events to the snippet_logs table.
 *
 * @package StarterSnippets\Helpers
 */

namespace StarterSnippets\Helpers;

use StarterSnippets\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Logger {

    private string $table;

    public function __construct() {
        $this->table = Config::table( Config::TABLE_LOGS );
    }

    /**
     * Log an event related to a snippet.
     *
     * @param int    $snippet_id  Snippet ID (0 for global events).
     * @param string $event       Event type: activated | deactivated | error | executed | info | warning.
     * @param string $message     Human-readable message.
     */
    public function log( int $snippet_id, string $event, string $message ): void {
        if ( ! Config::get_setting( 'enable_logging', true ) ) {
            return;
        }

        global $wpdb;

        $wpdb->insert( $this->table, [
            'snippet_id' => $snippet_id,
            'event'      => sanitize_key( $event ),
            'message'    => sanitize_text_field( $message ),
        ], [ '%d', '%s', '%s' ] );
    }

    /**
     * Convenience methods.
     */
    public function info( int $snippet_id, string $message ): void {
        $this->log( $snippet_id, 'info', $message );
    }

    public function warning( int $snippet_id, string $message ): void {
        $this->log( $snippet_id, 'warning', $message );
    }

    public function error( int $snippet_id, string $message ): void {
        $this->log( $snippet_id, 'error', $message );
    }

    /**
     * Get recent log entries.
     *
     * @return array<object>
     */
    public function get_recent( int $limit = 50 ): array {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.*, s.title AS snippet_title FROM {$this->table} l LEFT JOIN " . Config::table( Config::TABLE_SNIPPETS ) . " s ON l.snippet_id = s.id ORDER BY l.created_at DESC LIMIT %d",
                $limit
            )
        );
    }

    /**
     * Clear old logs (keep last N entries).
     */
    public function prune( int $keep = 500 ): void {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table} WHERE id NOT IN ( SELECT id FROM ( SELECT id FROM {$this->table} ORDER BY created_at DESC LIMIT %d ) AS keep_rows )",
                $keep
            )
        );
    }
}
