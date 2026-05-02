<?php
/**
 * Frontend Snippet Runner – executes active snippets on the front-end and admin.
 *
 * @package StarterSnippets\Frontend
 */

namespace StarterSnippets\Frontend;

use StarterSnippets\Core\Config;
use StarterSnippets\Database\Repository;
use StarterSnippets\Modules\ConditionEngine\ConditionEngine;
use StarterSnippets\Helpers\Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SnippetRunner {

    public function __construct(
        private Repository $repo,
        private ConditionEngine $engine,
        private Logger $logger,
    ) {}

    /**
     * Register hooks for snippet execution.
     */
    public function register(): void {
        // Early hook for PHP snippets that need to run at init.
        add_action( 'init', [ $this, 'run_php_snippets' ], 99 );

        // Head/footer hooks for JS/CSS/HTML.
        add_action( 'wp_head', [ $this, 'run_head_snippets' ], 99 );
        add_action( 'wp_footer', [ $this, 'run_footer_snippets' ], 99 );
        add_action( 'admin_head', [ $this, 'run_admin_head_snippets' ], 99 );

        // Shortcode registration.
        add_shortcode( 'starter_snippet', [ $this, 'run_shortcode' ] );
    }

    /**
     * Execute PHP snippets at 'init'.
     */
    public function run_php_snippets(): void {
        // Safe mode disables PHP execution.
        if ( Config::get_setting( 'safe_mode', false ) ) {
            return;
        }

        $snippets = $this->get_filtered_snippets( 'php' );

        foreach ( $snippets as $snippet ) {
            if ( ! $this->should_run_for_context( $snippet ) ) {
                continue;
            }

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->execute_php( $snippet );
        }
    }

    /**
     * Output header snippets (JS, CSS, HTML with location=header or everywhere/frontend).
     */
    public function run_head_snippets(): void {
        $this->output_frontend_snippets( 'header' );
    }

    /**
     * Output footer snippets.
     */
    public function run_footer_snippets(): void {
        $this->output_frontend_snippets( 'footer' );
    }

    /**
     * Output admin head snippets.
     */
    public function run_admin_head_snippets(): void {
        $snippets = $this->repo->get_active_snippets();

        foreach ( $snippets as $snippet ) {
            if ( ! in_array( $snippet->location, [ 'admin', 'everywhere' ], true ) ) {
                continue;
            }
            if ( 'php' === $snippet->language ) {
                continue; // PHP is handled at init.
            }

            $conditions = $this->repo->get_conditions( (int) $snippet->id );
            if ( ! $this->engine->should_run( $conditions ) ) {
                continue;
            }

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->get_snippet_output( $snippet );
        }
    }

    /**
     * Output frontend snippets for a given position (header or footer).
     */
    private function output_frontend_snippets( string $position ): void {
        $snippets = $this->repo->get_active_snippets();

        foreach ( $snippets as $snippet ) {
            if ( 'php' === $snippet->language ) {
                continue; // PHP handled at init.
            }

            // Check location matching.
            $runs_here = match ( $position ) {
                'header' => in_array( $snippet->location, [ 'header', 'everywhere', 'frontend' ], true ),
                'footer' => in_array( $snippet->location, [ 'footer', 'everywhere', 'frontend' ], true ),
                default  => false,
            };

            if ( ! $runs_here ) {
                continue;
            }

            // Admin-only snippets should not run on frontend.
            if ( 'admin' === $snippet->location ) {
                continue;
            }

            $conditions = $this->repo->get_conditions( (int) $snippet->id );
            if ( ! $this->engine->should_run( $conditions ) ) {
                continue;
            }

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->get_snippet_output( $snippet );
        }
    }

    /**
     * Get the HTML output of a non-PHP snippet (JS/CSS/HTML).
     */
    private function get_snippet_output( object $snippet ): string {
        $code = $snippet->code;
        $out  = '';

        switch ( $snippet->language ) {
            case 'js':
                $out .= "\n<!-- Starter Snippet: " . esc_html( $snippet->title ) . " -->\n";
                $out .= "<script>\n" . $code . "\n</script>\n";
                break;

            case 'css':
                $out .= "\n<!-- Starter Snippet: " . esc_html( $snippet->title ) . " -->\n";
                $out .= "<style>\n" . $code . "\n</style>\n";
                break;

            case 'html':
                $out .= "\n<!-- Starter Snippet: " . esc_html( $snippet->title ) . " -->\n";
                $out .= $code . "\n";
                break;
        }

        return $out;
    }

    /**
     * Safely execute a PHP snippet inside a try/catch with output buffering.
     */
    private function execute_php( object $snippet ): string {
        ob_start();

        try {
            // phpcs:ignore Squiz.PHP.Eval.Discouraged
            $result = eval( $snippet->code );

            if ( false === $result ) {
                throw new \ParseError( 'Snippet returned false (possible parse error).' );
            }
        } catch ( \Throwable $e ) {
            ob_end_clean();

            $this->logger->error(
                (int) $snippet->id,
                sprintf( 'Error in snippet "%s": %s', $snippet->title, $e->getMessage() )
            );

            // Auto-deactivate if setting allows.
            if ( 'deactivate' === Config::get_setting( 'error_handling', 'deactivate' ) ) {
                $this->repo->update( (int) $snippet->id, [ 'status' => 'inactive' ] );
                $this->logger->warning(
                    (int) $snippet->id,
                    sprintf( 'Snippet "%s" auto-deactivated due to error.', $snippet->title )
                );
            }

            return '';
        }

        return ob_get_clean() ?: '';
    }

    /**
     * Check if a snippet should run in the current context (admin vs frontend).
     */
    private function should_run_for_context( object $snippet ): bool {
        $is_admin = is_admin();

        $runs_here = match ( $snippet->location ) {
            'everywhere' => true,
            'frontend'   => ! $is_admin,
            'admin'      => $is_admin,
            'header'     => ! $is_admin, // PHP snippets in header/footer run on frontend.
            'footer'     => ! $is_admin,
            'shortcode'  => false,
            default      => true,
        };

        if ( ! $runs_here ) {
            return false;
        }

        $conditions = $this->repo->get_conditions( (int) $snippet->id );
        return $this->engine->should_run( $conditions );
    }

    /**
     * Get active snippets filtered by language.
     *
     * @return array<object>
     */
    private function get_filtered_snippets( string $language ): array {
        $all = $this->repo->get_active_snippets();

        return array_filter( $all, fn( $s ) => $s->language === $language );
    }

    /**
     * Execute a snippet via shortcode: [starter_snippet id="X"]
     *
     * @param array<string,string>|string $atts Shortcode attributes.
     */
    public function run_shortcode( $atts ): string {
        $atts = shortcode_atts( [ 'id' => 0 ], $atts );
        $id   = (int) $atts['id'];

        if ( ! $id ) {
            return '';
        }

        $all_active = $this->repo->get_active_snippets();
        $snippet    = null;

        foreach ( $all_active as $s ) {
            if ( (int) $s->id === $id && 'shortcode' === $s->location ) {
                $snippet = $s;
                break;
            }
        }

        if ( ! $snippet ) {
            return '';
        }

        $conditions = $this->repo->get_conditions( $id );
        if ( ! $this->engine->should_run( $conditions ) ) {
            return '';
        }

        if ( 'php' === $snippet->language ) {
            if ( Config::get_setting( 'safe_mode', false ) ) {
                return '';
            }
            return $this->execute_php( $snippet );
        }

        return $this->get_snippet_output( $snippet );
    }
}
