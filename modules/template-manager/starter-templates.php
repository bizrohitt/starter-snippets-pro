<?php
/**
 * Starter Templates – pre-built code templates.
 *
 * @package StarterSnippets\Modules\TemplateManager
 */

namespace StarterSnippets\Modules\TemplateManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class StarterTemplates {

    public static function get_all(): array {
        return [
            [
                'title'       => 'Google Analytics 4 Tracking',
                'description'  => 'Add Google Analytics 4 tracking code to your site. Replace GA_MEASUREMENT_ID with your actual GA4 Measurement ID.',
                'code'        => "<!-- Google tag (gtag.js) -->
<script async src=\"https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID\"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'GA_MEASUREMENT_ID');
</script>",
                'language'    => 'html',
                'location'    => 'header',
                'tags'        => 'analytics, tracking, google',
            ],
            [
                'title'       => 'Custom WordPress Login Logo',
                'description'  => 'Replace the default WordPress login logo with your own custom logo. Upload your logo to your theme/images folder and update the logo URL.',
                'code'        => "function custom_login_logo() {
    echo '<style type=\"text/css\">
        #login h1 a, .login h1 a {
            background-image: url(' . get_stylesheet_directory_uri() . '/images/custom-logo.png');
            width: 320px;
            height: 80px;
            background-size: contain;
            background-repeat: no-repeat;
        }
    </style>';
}
add_action( 'login_enqueue_scripts', 'custom_login_logo' );

function custom_login_logo_url() {
    return home_url();
}
add_filter( 'login_headerurl', 'custom_login_logo_url' );

function custom_login_logo_url_title() {
    return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'custom_login_logo_url_title' );",
                'language'    => 'php',
                'location'    => 'everywhere',
                'tags'        => 'branding, login, customization',
            ],
            [
                'title'       => 'Disable WordPress REST API',
                'description' => 'Completely disable the WordPress REST API for enhanced security. Only allow authenticated requests for authorized users.',
                'code'        => "// Disable REST API for non-authenticated users
add_filter( 'rest_authentication_errors', function( \$result ) {
    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'rest_disabled', 'REST API is disabled', array( 'status' => 403 ) );
    }
    return \$result;
});

// Remove REST API links from head
remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );",
                'language'    => 'php',
                'location'    => 'everywhere',
                'tags'        => 'security, api, rest',
            ],
            [
                'title'       => 'Custom Admin Footer Text',
                'description' => 'Replace the default WordPress admin footer text with your custom text.',
                'code'        => "// Change admin footer text
function custom_admin_footer_text() {
    echo 'Built with <span style=\"color: #e74c3c;\">&hearts;</span> using WordPress | <a href=\"https://yoursite.com\">Your Site Name</a>';
}
add_filter( 'admin_footer_text', 'custom_admin_footer_text' );

// Change WordPress version in footer
function custom_update_footer( \$footer_text ) {
    return 'Version 6.0+';
}
add_filter( 'update_footer', 'custom_update_footer', 9999 );",
                'language'    => 'php',
                'location'    => 'admin',
                'tags'        => 'admin, footer, branding',
            ],
            [
                'title'       => 'Disable Gutenberg Block Editor',
                'description'  => 'Completely disable the Gutenberg block editor and restore the classic editor for all post types.',
                'code'        => "// Disable Gutenberg for all post types
add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );

// Remove Gutenberg styles
add_action( 'wp_print_styles', function() {
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-block-style' );
}, 100 );",
                'language'    => 'php',
                'location'    => 'everywhere',
                'tags'        => 'gutenberg, editor, classic',
            ],
            [
                'title'       => 'Custom Excerpt Length',
                'description' => 'Change the default excerpt length from 55 words to a custom number. Set your desired length in the excerpt_length filter.',
                'code'        => "// Customize excerpt length
function custom_excerpt_length( \$length ) {
    return 30; // Change this number to your desired word count
}
add_filter( 'excerpt_length', 'custom_excerpt_length' );

// Customize excerpt more text
function custom_excerpt_more( \$more ) {
    return '... <a href=\"' . get_permalink() . '\">Read More</a>';
}
add_filter( 'excerpt_more', 'custom_excerpt_more' );",
                'language'    => 'php',
                'location'    => 'frontend',
                'tags'        => 'excerpt, content, customization',
            ],
            [
                'title'       => 'Disable XML-RPC',
                'description' => 'Disable XML-RPC for enhanced security. This prevents pingback vulnerabilities and brute force attacks.',
                'code'        => "// Disable XML-RPC
add_filter( 'xmlrpc_enabled', '__return_false' );

// Remove XML-RPC link from head
remove_action( 'wp_head', 'rsd_link' );

// Disable XML-RPC API
add_filter( 'xmlrpc_methods', function( \$methods ) {
    unset( \$methods['pingback.ping'] );
    unset( \$methods['pingback.extensions.getPingbacks'] );
    return \$methods;
});",
                'language'    => 'php',
                'location'    => 'everywhere',
                'tags'        => 'security, xmlrpc, hardening',
            ],
            [
                'title'       => 'Add Custom Body Classes',
                'description' => 'Add custom CSS classes to the body tag based on various conditions like page, user state, etc.',
                'code'        => "// Add custom body classes
function custom_body_classes( \$classes ) {
    global \$post;

    // Add post slug as class
    if ( isset( \$post ) ) {
        \$classes[] = 'post-' . \$post->post_name;
    }

    // Add user state class
    if ( is_user_logged_in() ) {
        \$classes[] = 'logged-in';
    } else {
        \$classes[] = 'logged-out';
    }

    // Add browser detection class
    global \$is_lynx, \$is_gecko, \$is_IE, \$is_edge, \$is_opera, \$is_safari, \$is_chrome, \$is_iphone;
    if ( \$is_lynx ) \$classes[] = 'browser-lynx';
    elseif ( \$is_gecko ) \$classes[] = 'browser-firefox';
    elseif ( \$is_IE ) \$classes[] = 'browser-ie';
    elseif ( \$is_edge ) \$classes[] = 'browser-edge';
    elseif ( \$is_opera ) \$classes[] = 'browser-opera';
    elseif ( \$is_safari ) \$classes[] = 'browser-safari';
    elseif ( \$is_chrome ) \$classes[] = 'browser-chrome';
    elseif ( \$is_iphone ) \$classes[] = 'browser-iphone';

    return \$classes;
}
add_filter( 'body_class', 'custom_body_classes' );",
                'language'    => 'php',
                'location'    => 'frontend',
                'tags'        => 'body, classes, customization',
            ],
            [
                'title'       => 'Remove Query Strings from Static Resources',
                'description' => 'Remove query strings from CSS and JS files to improve caching. This helps with CDN caching and performance scores.',
                'code'        => "// Remove query strings from static resources
function remove_script_version( \$src ) {
    if ( strpos( \$src, 'ver=' ) ) {
        \$src = remove_query_arg( 'ver', \$src );
    }
    return \$src;
}
add_filter( 'style_loader_src', 'remove_script_version', 9999 );
add_filter( 'script_loader_src', 'remove_script_version', 9999 );",
                'language'    => 'php',
                'location'    => 'frontend',
                'tags'        => 'performance, caching, optimization',
            ],
            [
                'title'       => 'Disable Comments Globally',
                'description' => 'Completely disable comments across your entire WordPress site and remove comment-related menu items.',
                'code'        => "// Disable comments completely
function disable_comments_status() {
    return false;
}
add_filter( 'comments_open', 'disable_comments_status', 20, 2 );
add_filter( 'pings_open', 'disable_comments_status', 20, 2 );

// Hide existing comments
function disable_comments_hide_existing( \$open ) {
    return false;
}
add_filter( 'comments_array', function( \$comments ) {
    return array();
}, 10, 2 );

// Remove comments page from admin menu
add_action( 'admin_menu', function() {
    remove_menu_page( 'edit-comments.php' );
});

// Remove comments from admin bar
add_action( 'admin_bar_menu', function( \$wp_admin_bar ) {
    \$wp_admin_bar->remove_node( 'comments' );
}, 999 );",
                'language'    => 'php',
                'location'    => 'everywhere',
                'tags'        => 'comments, disable, moderation',
            ],
            [
                'title'       => 'Custom jQuery Ready Script',
                'description'  => 'A starter template for adding custom JavaScript that runs when the DOM is ready. Useful for front-end enhancements.',
                'code'        => "(function($) {
    'use strict';

    $(document).ready(function() {
        // Your custom jQuery code here
        console.log('Document ready!');

        // Example: Add smooth scroll to anchor links
        $('a[href^=\"#\"]').on('click', function(e) {
            var target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 500);
            }
        });
    });

})(jQuery);",
                'language'    => 'js',
                'location'    => 'footer',
                'tags'        => 'javascript, jquery, frontend',
            ],
            [
                'title'       => 'Custom CSS Reset Styles',
                'description'  => 'Basic CSS reset and normalization styles for improving cross-browser consistency.',
                'code'        => "/* Box sizing reset */
*, *::before, *::after {
    box-sizing: border-box;
}

/* Remove default margins */
* {
    margin: 0;
    padding: 0;
}

/* Better scroll behavior */
html {
    scroll-behavior: smooth;
}

/* Prevent font size adjustments */
body {
    -webkit-text-size-adjust: none;
    text-size-adjust: none;
}

/* Remove list styles */
ul, ol {
    list-style: none;
}

/* Images responsive */
img {
    max-width: 100%;
    height: auto;
    display: block;
}

/* Form elements reset */
button, input, select, textarea {
    font-family: inherit;
    font-size: 100%;
}

/* Remove button styling */
button {
    background: none;
    border: none;
    cursor: pointer;
}

/* Links */
a {
    text-decoration: none;
}",
                'language'    => 'css',
                'location'    => 'header',
                'tags'        => 'css, reset, stylesheet',
            ],
            [
                'title'       => 'Disable Auto-Save and Revisions',
                'description' => 'Disable WordPress auto-save and post revisions to reduce database bloat. Useful for sites with performance concerns.',
                'code'        => "// Disable auto-save
function disable_autosave() {
    wp_deregister_script( 'autosave' );
}
add_action( 'wp_print_scripts', 'disable_autosave' );

// Disable post revisions
function disable_revisions() {
    \$post_types = get_post_types( '', 'names' );
    foreach ( \$post_types as \$post_type ) {
        remove_post_type_support( \$post_type, 'revisions' );
    }
}
add_action( 'init', 'disable_revisions', 999 );

// Alternatively, limit revisions to 3
function limit_post_revisions( \$num, \$post_id ) {
    return 3;
}
add_filter( 'wp_revisions_to_keep', 'limit_post_revisions', 10, 2 );",
                'language'    => 'php',
                'location'    => 'everywhere',
                'tags'        => 'performance, revisions, autosave',
            ],
            [
                'title'       => 'Add Open Graph Meta Tags',
                'description' => 'Add Open Graph meta tags to your site head for better social media sharing. Works with Facebook, LinkedIn, and more.',
                'code'        => "// Add Open Graph meta tags
function add_open_graph_tags() {
    if ( is_singular() ) {
        global \$post;
        \$thumbnail = get_the_post_thumbnail_url( \$post->ID, 'large' );
        \$site_name = get_bloginfo( 'name' );

        echo '<meta property=\"og:title\" content=\"' . esc_attr( get_the_title() ) . '\" />' . \"\\n\";
        echo '<meta property=\"og:description\" content=\"' . esc_attr( get_the_excerpt() ) . '\" />' . \"\\n\";
        echo '<meta property=\"og:url\" content=\"' . esc_url( get_permalink() ) . '\" />' . \"\\n\";
        echo '<meta property=\"og:type\" content=\"article\" />' . \"\\n\";
        echo '<meta property=\"og:site_name\" content=\"' . esc_attr( \$site_name ) . '\" />' . \"\\n\";

        if ( \$thumbnail ) {
            echo '<meta property=\"og:image\" content=\"' . esc_url( \$thumbnail ) . '\" />' . \"\\n\";
        }
    }
}
add_action( 'wp_head', 'add_open_graph_tags' );",
                'language'    => 'php',
                'location'    => 'header',
                'tags'        => 'seo, social, open graph, sharing',
            ],
            [
                'title'       => 'Custom Login Error Messages',
                'description'  => 'Display generic error messages on failed login attempts for improved security. Prevents username enumeration.',
                'code'        => "// Generic login error message
function custom_login_error_message() {
    return '<strong>ERROR</strong>: Invalid username or password.';
}
add_filter( 'login_errors', 'custom_login_error_message' );

// Remove login hints
function remove_login_hints( \$error ) {
    return '<strong>ERROR</strong>: Invalid credentials.';
}
add_filter( 'login_errors', 'remove_login_hints' );",
                'language'    => 'php',
                'location'    => 'everywhere',
                'tags'        => 'security, login, authentication',
            ],
            [
                'title'       => 'WooCommerce: Change Number of Products',
                'description' => 'Change the number of products displayed per row and per page in WooCommerce shop pages.',
                'code'        => "// Change products per page
function change_products_per_page() {
    return 12; // Change to your desired number
}
add_filter( 'loop_shop_per_page', 'change_products_per_page', 20 );

// Change products per row
function change_products_columns( \$columns ) {
    return 4; // Change to your desired number of columns
}
add_filter( 'loop_shop_columns', 'change_products_columns' );

// Change related products number
function change_related_products_count( \$args ) {
    \$args['posts_per_page'] = 4; // Change to your desired number
    return \$args;
}
add_filter( 'woocommerce_output_related_products_args', 'change_related_products_count', 20 );",
                'language'    => 'php',
                'location'    => 'frontend',
                'tags'        => 'woocommerce, products, shop',
            ],
        ];
    }

    public static function get_count(): int {
        return count( self::get_all() );
    }
}
