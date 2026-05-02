<?php
/**
 * Starter Snippets PHPUnit bootstrap.
 *
 * @package StarterSnippets\Tests
 */

// Load the WordPress test suite.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "Could not find WP test suite at {$_tests_dir}. Set WP_TESTS_DIR env var.\n";
    exit( 1 );
}

// Load WP test functions.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Load the plugin being tested.
 */
tests_add_filter( 'muplugins_loaded', function () {
    require dirname( __DIR__ ) . '/starter-snippets.php';
} );

// Start the WP test suite.
require $_tests_dir . '/includes/bootstrap.php';
