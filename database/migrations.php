<?php
/**
 * Database migration runner.
 *
 * @package StarterSnippets\Database
 */

namespace StarterSnippets\Database;

use StarterSnippets\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Migrations {

    /**
     * Compare installed DB version with current and run upgrades.
     */
    public static function maybe_upgrade(): void {
        $installed = get_option( Config::DB_VERSION_OPTION, '0' );

        if ( version_compare( $installed, STARTER_SNIPPETS_DB_VERSION, '>=' ) ) {
            return;
        }

        // Re-run schema (dbDelta is safe to run multiple times).
        Schema::create_tables();

        // Version-specific migrations can be added here.
        // if ( version_compare( $installed, '1.1.0', '<' ) ) {
        //     self::upgrade_to_1_1_0();
        // }

        update_option( Config::DB_VERSION_OPTION, STARTER_SNIPPETS_DB_VERSION );
    }
}
