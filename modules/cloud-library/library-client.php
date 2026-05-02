<?php
/**
 * Library Client – handles communication with the template library API.
 *
 * @package StarterSnippets\Modules\CloudLibrary
 */

namespace StarterSnippets\Modules\CloudLibrary;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LibraryClient {

    private const API_BASE = 'https://api.starter-snippets.com/v1';

    /**
     * Fetch community snippets from the library.
     *
     * @param array<string,mixed> $args
     * @return array<string,mixed>
     */
    public function fetch_snippets( array $args = [] ): array {
        $defaults = [
            'category' => '',
            'language' => '',
            'search'   => '',
            'page'     => 1,
            'per_page' => 20,
        ];

        $args = wp_parse_args( $args, $defaults );

        $url = add_query_arg( $args, self::API_BASE . '/snippets' );

        $response = wp_remote_get( $url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
                'snippets' => [],
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( 200 !== $code ) {
            return [
                'success' => false,
                'message' => sprintf( 'API returned status code: %d', $code ),
                'snippets' => [],
            ];
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        return [
            'success'  => true,
            'snippets' => $data['snippets'] ?? [],
            'total'    => $data['total'] ?? 0,
        ];
    }

    /**
     * Fetch a single snippet by ID.
     *
     * @param int $snippet_id
     * @return array<string,mixed>
     */
    public function fetch_snippet( int $snippet_id ): array {
        $url = self::API_BASE . '/snippets/' . $snippet_id;

        $response = wp_remote_get( $url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
                'snippet' => null,
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( 200 !== $code ) {
            return [
                'success' => false,
                'message' => sprintf( 'API returned status code: %d', $code ),
                'snippet' => null,
            ];
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        return [
            'success' => true,
            'snippet' => $data['snippet'] ?? null,
        ];
    }

    /**
     * Submit a snippet to the community library.
     *
     * @param array<string,mixed> $snippet_data
     * @return array<string,mixed>
     */
    public function submit_snippet( array $snippet_data ): array {
        $url = self::API_BASE . '/snippets';

        $response = wp_remote_post( $url, [
            'timeout' => 30,
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode( $snippet_data ),
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( 201 !== $code ) {
            return [
                'success' => false,
                'message' => sprintf( 'API returned status code: %d', $code ),
            ];
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        return [
            'success'  => true,
            'message'  => __( 'Snippet shared successfully!', 'starter-snippets' ),
            'snippet'  => $data['snippet'] ?? null,
        ];
    }
}
