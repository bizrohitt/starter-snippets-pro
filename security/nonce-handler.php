<?php
/**
 * Nonce handler.
 *
 * @package StarterSnippets\Security
 */

namespace StarterSnippets\Security;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class NonceHandler {

    private const ACTION = 'starter_snippets_nonce';
    private const FIELD  = '_starter_snippets_nonce';

    /**
     * Output a hidden nonce field.
     */
    public static function field(): void {
        wp_nonce_field( self::ACTION, self::FIELD );
    }

    /**
     * Verify a nonce from $_POST or $_REQUEST.
     */
    public static function verify( ?string $nonce = null ): bool {
        if ( null === $nonce ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $nonce = $_POST[ self::FIELD ] ?? $_REQUEST[ self::FIELD ] ?? '';
        }
        return (bool) wp_verify_nonce( $nonce, self::ACTION );
    }

    /**
     * Verify or die.
     */
    public static function verify_or_die( ?string $nonce = null ): void {
        if ( ! self::verify( $nonce ) ) {
            wp_die(
                esc_html__( 'Security check failed. Please try again.', 'starter-snippets' ),
                403
            );
        }
    }

    /**
     * Return the nonce action string (for REST / JS).
     */
    public static function action(): string {
        return self::ACTION;
    }
}
