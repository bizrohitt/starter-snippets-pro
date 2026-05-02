<?php
/**
 * PSR-4-style autoloader for the StarterSnippets namespace.
 *
 * @package StarterSnippets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

spl_autoload_register( function ( string $class ) {

    $prefix = 'StarterSnippets\\';

    if ( ! str_starts_with( $class, $prefix ) ) {
        return;
    }

    $relative = substr( $class, strlen( $prefix ) );

    // Convert namespace separators to directory separators and class name to filename.
    // StarterSnippets\Core\PluginLoader  →  core/plugin-loader.php
    $parts    = explode( '\\', $relative );
    $filename = array_pop( $parts );

    // Convert PascalCase to kebab-case for the file name.
    $filename = strtolower( preg_replace( '/(?<!^)[A-Z]/', '-$0', $filename ) );

    // Convert namespace parts to lowercase directory names.
    $subdir = '';
    if ( ! empty( $parts ) ) {
        $subdir = implode(
            DIRECTORY_SEPARATOR,
            array_map( function ( $part ) {
                return strtolower( preg_replace( '/(?<!^)[A-Z]/', '-$0', $part ) );
            }, $parts )
        ) . DIRECTORY_SEPARATOR;
    }

    $file = STARTER_SNIPPETS_DIR . $subdir . $filename . '.php';

    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );
