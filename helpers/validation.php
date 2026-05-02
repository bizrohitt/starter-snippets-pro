<?php
/**
 * Validation helpers.
 *
 * @package StarterSnippets\Helpers
 */

namespace StarterSnippets\Helpers;

use StarterSnippets\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Validation {

    /**
     * Validate sanitized snippet data.
     *
     * @param array<string,mixed> $data  Sanitized snippet data.
     * @return array<string>  Array of error messages (empty = valid).
     */
    public static function snippet( array $data ): array {
        $errors = [];

        if ( empty( $data['title'] ) ) {
            $errors[] = __( 'Snippet title is required.', 'starter-snippets' );
        } elseif ( mb_strlen( $data['title'] ) > 255 ) {
            $errors[] = __( 'Snippet title must be 255 characters or fewer.', 'starter-snippets' );
        }

        if ( empty( $data['code'] ) ) {
            $errors[] = __( 'Snippet code cannot be empty.', 'starter-snippets' );
        }

        if ( ! in_array( $data['language'] ?? '', Config::LANGUAGES, true ) ) {
            $errors[] = __( 'Invalid snippet language.', 'starter-snippets' );
        }

        if ( ! in_array( $data['location'] ?? '', Config::LOCATIONS, true ) ) {
            $errors[] = __( 'Invalid execution location.', 'starter-snippets' );
        }

        if ( ! in_array( $data['status'] ?? '', [ 'active', 'inactive' ], true ) ) {
            $errors[] = __( 'Invalid snippet status.', 'starter-snippets' );
        }

        return $errors;
    }

    /**
     * Validate import data structure.
     *
     * @param mixed $data  Decoded JSON.
     * @return bool
     */
    public static function import_data( mixed $data ): bool {
        if ( ! is_array( $data ) ) {
            return false;
        }

        foreach ( $data as $item ) {
            if ( ! is_array( $item ) ) {
                return false;
            }
            if ( empty( $item['title'] ) || ! isset( $item['code'] ) ) {
                return false;
            }
        }

        return true;
    }
}
