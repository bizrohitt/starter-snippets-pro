<?php
/**
 * Admin Dashboard – overview page.
 *
 * @package StarterSnippets\Admin
 */

namespace StarterSnippets\Admin;

use StarterSnippets\Core\Config;
use StarterSnippets\Database\Repository;
use StarterSnippets\Security\Permissions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Dashboard {

    public function __construct(
        private Repository $repo,
    ) {}

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
    }

    /**
     * Register the top-level admin menu and submenus.
     */
    public function add_menu(): void {
        add_menu_page(
            __( 'Starter Snippets', 'starter-snippets' ),
            __( 'Snippets', 'starter-snippets' ),
            Config::CAPABILITY,
            Config::MENU_SLUG,
            [ $this, 'render' ],
            'dashicons-editor-code',
            81
        );

        // Rename first submenu to "Dashboard".
        add_submenu_page(
            Config::MENU_SLUG,
            __( 'Dashboard', 'starter-snippets' ),
            __( 'Dashboard', 'starter-snippets' ),
            Config::CAPABILITY,
            Config::MENU_SLUG,
            [ $this, 'render' ]
        );
    }

    /**
     * Render the dashboard page.
     */
    public function render(): void {
        Permissions::check_or_die();

        $stats = get_transient( 'starter_snippets_dashboard_stats' );

        if ( false === $stats ) {
            $counts = $this->repo->count_by_status();
            $stats  = [
                'total'    => array_sum( $counts ),
                'active'   => $counts['active'] ?? 0,
                'inactive' => $counts['inactive'] ?? 0,
            ];
            set_transient( 'starter_snippets_dashboard_stats', $stats, 300 );
        }

        include STARTER_SNIPPETS_DIR . 'templates/admin/dashboard-page.php';
    }
}
