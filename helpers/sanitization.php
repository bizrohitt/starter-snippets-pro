<?php
/**
 * Input sanitization helpers.
 *
 * @package StarterSnippets\Helpers
 */

namespace StarterSnippets\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Sanitization {

    /**
     * Sanitize a full snippet data array from form input.
     *
     * @param array<string,mixed> $raw  Raw $_POST data.
     * @return array<string,mixed>  Sanitized data.
     */
    public static function snippet( array $raw ): array {
        return [
            'title'       => sanitize_text_field( $raw['title'] ?? '' ),
            'description' => sanitize_textarea_field( $raw['description'] ?? '' ),
            'code'        => self::snippet_code( $raw['code'] ?? '' ),
            'language'    => self::enum( $raw['language'] ?? 'php', \StarterSnippets\Core\Config::LANGUAGES, 'php' ),
            'location'    => self::enum( $raw['location'] ?? 'everywhere', \StarterSnippets\Core\Config::LOCATIONS, 'everywhere' ),
            'priority'    => absint( $raw['priority'] ?? 10 ),
            'status'      => self::enum( $raw['status'] ?? 'inactive', [ 'active', 'inactive' ], 'inactive' ),
            'tags'        => sanitize_text_field( $raw['tags'] ?? '' ),
            'conditions'  => self::conditions( $raw['conditions'] ?? [] ),
        ];
    }

    /**
     * Sanitize snippet code.
     *
     * Code is intentionally NOT sanitized with wp_kses because it may contain any language.
     * We use wp_unslash to remove magic quotes only.
     */
    public static function snippet_code( string $code ): string {
        return wp_unslash( $code );
    }

    /**
     * Validate a value against an enum list.
     */
    public static function enum( string $value, array $allowed, string $default ): string {
        return in_array( $value, $allowed, true ) ? $value : $default;
    }

    /**
     * Sanitize an array of conditions.
     *
     * @param array<array<string,string>> $conditions
     * @return array<array<string,string>>
     */
    public static function conditions( array $conditions ): array {
        $clean = [];

        foreach ( $conditions as $cond ) {
            if ( empty( $cond['condition_type'] ) ) {
                continue;
            }

            $type = self::enum(
                $cond['condition_type'],
                \StarterSnippets\Core\Config::CONDITION_TYPES,
                ''
            );

            if ( '' === $type ) {
                continue;
            }

            $clean[] = [
                'condition_type'     => $type,
                'condition_value'    => sanitize_text_field( $cond['condition_value'] ?? '' ),
                'condition_operator' => self::enum( $cond['condition_operator'] ?? 'include', [ 'include', 'exclude' ], 'include' ),
            ];
        }

        return $clean;
    }
}
