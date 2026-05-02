<?php
/**
 * Settings Page.
 *
 * @package StarterSnippets\Admin
 */

namespace StarterSnippets\Admin;

use StarterSnippets\Core\Config;
use StarterSnippets\Security\Permissions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SettingsPage {

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'add_submenu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_submenu(): void {
        add_submenu_page(
            Config::MENU_SLUG,
            __( 'Settings', 'starter-snippets' ),
            __( 'Settings', 'starter-snippets' ),
            Config::CAPABILITY,
            Config::MENU_SLUG . '-settings',
            [ $this, 'render' ]
        );
    }

    /**
     * Register settings with the WP Settings API.
     */
    public function register_settings(): void {
        register_setting( 'starter_snippets_settings_group', Config::SETTINGS_OPTION, [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize_settings' ],
            'default'           => Config::defaults(),
        ] );

        add_settings_section(
            'starter_snippets_general',
            __( 'General Settings', 'starter-snippets' ),
            function () {
                echo '<p>' . esc_html__( 'Configure how Starter Snippets behaves.', 'starter-snippets' ) . '</p>';
            },
            Config::MENU_SLUG . '-settings'
        );

        // Enable Logging.
        add_settings_field(
            'enable_logging',
            __( 'Enable Logging', 'starter-snippets' ),
            [ $this, 'render_checkbox' ],
            Config::MENU_SLUG . '-settings',
            'starter_snippets_general',
            [
                'key'         => 'enable_logging',
                'description' => __( 'Log snippet activation, deactivation, and error events.', 'starter-snippets' ),
            ]
        );

        // Error Handling.
        add_settings_field(
            'error_handling',
            __( 'Error Handling', 'starter-snippets' ),
            [ $this, 'render_select' ],
            Config::MENU_SLUG . '-settings',
            'starter_snippets_general',
            [
                'key'     => 'error_handling',
                'options' => [
                    'deactivate' => __( 'Auto-deactivate faulty snippets', 'starter-snippets' ),
                    'log_only'   => __( 'Log errors only (do not deactivate)', 'starter-snippets' ),
                ],
            ]
        );

        // Safe Mode.
        add_settings_field(
            'safe_mode',
            __( 'Safe Mode', 'starter-snippets' ),
            [ $this, 'render_checkbox' ],
            Config::MENU_SLUG . '-settings',
            'starter_snippets_general',
            [
                'key'         => 'safe_mode',
                'description' => __( 'When enabled, no PHP snippets will execute. Useful for debugging.', 'starter-snippets' ),
            ]
        );
    }

    /**
     * Sanitize settings array.
     */
    public function sanitize_settings( mixed $input ): array {
        $defaults = Config::defaults();
        $clean    = [];

        $clean['enable_logging'] = ! empty( $input['enable_logging'] );
        $clean['safe_mode']      = ! empty( $input['safe_mode'] );
        $clean['error_handling']  = in_array( $input['error_handling'] ?? '', [ 'deactivate', 'log_only' ], true )
            ? $input['error_handling']
            : $defaults['error_handling'];

        return $clean;
    }

    /**
     * Render a checkbox field.
     */
    public function render_checkbox( array $args ): void {
        $settings = get_option( Config::SETTINGS_OPTION, Config::defaults() );
        $checked  = ! empty( $settings[ $args['key'] ] );
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr( Config::SETTINGS_OPTION . '[' . $args['key'] . ']' ); ?>"
                   value="1"
                   <?php checked( $checked ); ?>>
            <?php echo esc_html( $args['description'] ?? '' ); ?>
        </label>
        <?php
    }

    /**
     * Render a select field.
     */
    public function render_select( array $args ): void {
        $settings = get_option( Config::SETTINGS_OPTION, Config::defaults() );
        $value    = $settings[ $args['key'] ] ?? '';
        ?>
        <select name="<?php echo esc_attr( Config::SETTINGS_OPTION . '[' . $args['key'] . ']' ); ?>">
            <?php foreach ( $args['options'] as $val => $label ) : ?>
                <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $value, $val ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render the settings page.
     */
    public function render(): void {
        Permissions::check_or_die();
        ?>
        <div class="wrap starter-snippets-wrap">
            <h1><?php esc_html_e( 'Starter Snippets Settings', 'starter-snippets' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields( 'starter_snippets_settings_group' );
                    do_settings_sections( Config::MENU_SLUG . '-settings' );
                    submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
