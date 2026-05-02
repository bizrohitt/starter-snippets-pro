<?php
/**
 * Database schema definitions.
 *
 * @package StarterSnippets\Database
 */

namespace StarterSnippets\Database;

use StarterSnippets\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Schema {

    /**
     * Create or update all plugin tables using dbDelta.
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        $snippets_table    = Config::table( Config::TABLE_SNIPPETS );
        $conditions_table  = Config::table( Config::TABLE_CONDITIONS );
        $logs_table        = Config::table( Config::TABLE_LOGS );
        $revisions_table   = Config::table( Config::TABLE_REVISIONS );
        $templates_table   = Config::table( Config::TABLE_TEMPLATES );

        $sql = "
CREATE TABLE {$snippets_table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT,
    code LONGTEXT NOT NULL,
    language VARCHAR(10) NOT NULL DEFAULT 'php',
    location VARCHAR(20) NOT NULL DEFAULT 'everywhere',
    priority INT NOT NULL DEFAULT 10,
    status VARCHAR(10) NOT NULL DEFAULT 'inactive',
    tags VARCHAR(255) DEFAULT '',
    created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY status (status),
    KEY language (language)
) {$charset};

CREATE TABLE {$conditions_table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    snippet_id BIGINT UNSIGNED NOT NULL,
    condition_type VARCHAR(30) NOT NULL,
    condition_value VARCHAR(255) NOT NULL DEFAULT '',
    condition_operator VARCHAR(10) NOT NULL DEFAULT 'include',
    PRIMARY KEY  (id),
    KEY snippet_id (snippet_id)
) {$charset};

CREATE TABLE {$revisions_table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    snippet_id BIGINT UNSIGNED NOT NULL,
    code LONGTEXT NOT NULL,
    language VARCHAR(10) NOT NULL DEFAULT 'php',
    created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY snippet_id (snippet_id)
) {$charset};

CREATE TABLE {$logs_table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    snippet_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    event VARCHAR(30) NOT NULL DEFAULT 'info',
    message TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY snippet_id (snippet_id),
    KEY event (event)
) {$charset};

CREATE TABLE {$templates_table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT,
    code LONGTEXT NOT NULL,
    language VARCHAR(10) NOT NULL DEFAULT 'php',
    location VARCHAR(20) NOT NULL DEFAULT 'everywhere',
    tags VARCHAR(255) DEFAULT '',
    is_builtin TINYINT(1) NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usage_count INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY  (id),
    KEY language (language),
    KEY is_builtin (is_builtin)
) {$charset};
";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}
