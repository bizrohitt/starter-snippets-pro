<?php
/**
 * Plugin deactivation handler.
 *
 * @package StarterSnippets\Bootstrap
 */

namespace StarterSnippets\Bootstrap;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Deactivate {

    /**
     * Run on plugin deactivation.
     *
     * Clears caches and transients but preserves data.
     */
    public static function run(): void {
        // Clear snippet cache.
        wp_cache_delete( 'active_snippets', 'starter_snippets' );
        delete_transient( 'starter_snippets_dashboard_stats' );

        // Flush rewrite rules.
        flush_rewrite_rules();
    }
}
