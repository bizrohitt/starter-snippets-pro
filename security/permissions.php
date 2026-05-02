<?php
/**
 * Permissions helper.
 *
 * @package StarterSnippets\Security
 */

namespace StarterSnippets\Security;

use StarterSnippets\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Permissions {

    /**
     * Check whether the current user can manage snippets.
     */
    public static function can_manage(): bool {
        return current_user_can( Config::CAPABILITY );
    }

    /**
     * Abort with 403 if the user lacks permission.
     */
    public static function check_or_die(): void {
        if ( ! self::can_manage() ) {
            wp_die(
                esc_html__( 'You do not have permission to manage snippets.', 'starter-snippets' ),
                403
            );
        }
    }
}
