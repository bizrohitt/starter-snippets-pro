<?php
/**
 * Centralised configuration.
 *
 * @package StarterSnippets\Core
 */

namespace StarterSnippets\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Config {

    /** Required capability for all admin actions. */
    public const CAPABILITY = 'manage_options';

    /** Admin menu slug. */
    public const MENU_SLUG = 'starter-snippets';

    /** REST API namespace. */
    public const REST_NAMESPACE = 'starter-snippets/v1';

    /** Option key storing the current DB schema version. */
    public const DB_VERSION_OPTION = 'starter_snippets_db_version';

    /** Option key for plugin settings. */
    public const SETTINGS_OPTION = 'starter_snippets_settings';

    /** Snippet table (without WP prefix). */
    public const TABLE_SNIPPETS = 'starter_snippets';

    /** Conditions table (without WP prefix). */
    public const TABLE_CONDITIONS = 'starter_snippet_conditions';

    /** Logs table (without WP prefix). */
    public const TABLE_LOGS = 'starter_snippet_logs';

    /** Revisions table (without WP prefix). */
    public const TABLE_REVISIONS = 'starter_snippet_revisions';

    /** Templates table (without WP prefix). */
    public const TABLE_TEMPLATES = 'starter_snippet_templates';

    /** Allowed snippet languages. */
    public const LANGUAGES = [ 'php', 'js', 'css', 'html' ];

    /** Allowed execution locations. */
    public const LOCATIONS = [ 'everywhere', 'frontend', 'admin', 'header', 'footer', 'shortcode' ];

    /** Allowed condition types. */
    public const CONDITION_TYPES = [
        'page_id',
        'post_id',
        'post_type',
        'user_role',
        'logged_in',
        'url_pattern',
        'device',
        'schedule',
        'country',
        'woo_cart_total',
        'woo_cart_product',
    ];

    /**
     * Return the full table name.
     */
    public static function table( string $short ): string {
        global $wpdb;
        return $wpdb->prefix . $short;
    }

    /**
     * Return default plugin settings.
     *
     * @return array<string,mixed>
     */
    public static function defaults(): array {
        return [
            'enable_logging' => true,
            'safe_mode'      => false,
            'error_handling'  => 'deactivate', // deactivate | log_only
        ];
    }

    /**
     * Get a single setting value.
     */
    public static function get_setting( string $key, mixed $default = null ): mixed {
        $settings = get_option( self::SETTINGS_OPTION, self::defaults() );
        return $settings[ $key ] ?? $default;
    }
}
