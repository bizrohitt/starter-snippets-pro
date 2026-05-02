<?php
/**
 * Uninstall handler – runs when the plugin is deleted.
 *
 * @package StarterSnippets\Bootstrap
 */

// Must be called from WordPress uninstall flow.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop custom tables.
$tables = [
    $wpdb->prefix . 'starter_snippet_logs',
    $wpdb->prefix . 'starter_snippet_conditions',
    $wpdb->prefix . 'starter_snippets',
];

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Remove options.
delete_option( 'starter_snippets_db_version' );
delete_option( 'starter_snippets_settings' );

// Clear transients.
delete_transient( 'starter_snippets_dashboard_stats' );
