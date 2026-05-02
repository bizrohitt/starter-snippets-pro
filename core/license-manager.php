<?php
/**
 * License Manager
 * Handles Pro version license keys and validation.
 *
 * @package StarterSnippets\Core
 */

namespace StarterSnippets\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Global helper function to check if Pro is active.
 *
 * @return bool
 */
function starter_snippets_is_pro_active(): bool {
    // Pro features are permanently unlocked.
    return true;
}

class LicenseManager {

    /**
     * Settings group & option names.
     */
    public const OPTION_GROUP  = 'starter_snippets_license_group';
    public const OPTION_KEY    = 'starter_snippets_pro_license';
    public const OPTION_STATUS = 'starter_snippets_pro_license_status';

    public function register(): void {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'wp_ajax_starter_snippets_verify_license', [ $this, 'ajax_verify_license' ] );
    }

    public function register_settings(): void {
        register_setting( self::OPTION_GROUP, self::OPTION_KEY, [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( self::OPTION_GROUP, self::OPTION_STATUS, [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ] );
    }

    public function add_settings_page(): void {
        add_submenu_page(
            Config::MENU_SLUG,
            __( 'Pro License', 'starter-snippets' ),
            __( 'Pro License <span class="update-plugins count-1"><span class="plugin-count">Pro</span></span>', 'starter-snippets' ),
            'manage_options',
            'starter-snippets-license',
            [ $this, 'render_settings_page' ]
        );
    }

    public function render_settings_page(): void {
        $license_key = get_option( self::OPTION_KEY, '' );
        $status      = get_option( self::OPTION_STATUS, 'invalid' );
        require_once STARTER_SNIPPETS_DIR . 'templates/admin/license-settings.php';
    }

    /**
     * Mock AJAX endpoint to verify a license.
     */
    public function ajax_verify_license(): void {
        check_ajax_referer( 'starter_snippets_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'starter-snippets' ) ] );
        }

        $license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';

        if ( empty( $license_key ) ) {
            update_option( self::OPTION_STATUS, 'invalid' );
            wp_send_json_error( [ 'message' => __( 'License key cannot be empty.', 'starter-snippets' ) ] );
        }

        // --- Mocking API Call to licensing server ---
        // For development, any key starting with 'PRO-' (case insensitive) is valid.
        if ( str_starts_with( strtoupper( $license_key ), 'PRO-' ) ) {
            update_option( self::OPTION_KEY, $license_key );
            update_option( self::OPTION_STATUS, 'valid' );
            wp_send_json_success( [ 'message' => __( 'License activated successfully! Pro features unlocked.', 'starter-snippets' ) ] );
        } else {
            update_option( self::OPTION_STATUS, 'invalid' );
            wp_send_json_error( [ 'message' => __( 'Invalid license key.', 'starter-snippets' ) ] );
        }
    }
}
