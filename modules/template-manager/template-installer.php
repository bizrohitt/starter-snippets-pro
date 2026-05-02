<?php
/**
 * Template Installer – handles built-in template installation.
 *
 * @package StarterSnippets\Modules\TemplateManager
 */

namespace StarterSnippets\Modules\TemplateManager;

use StarterSnippets\Database\Repository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class TemplateInstaller {

    private Repository $repository;

    public function __construct( Repository $repository ) {
        $this->repository = $repository;
    }

    /**
     * Install all built-in templates if they don't exist.
     */
    public function install_builtin_templates(): void {
        $installed_count = $this->repository->count_templates( [ 'is_builtin' => 1 ] );

        if ( $installed_count > 0 ) {
            return;
        }

        $templates = StarterTemplates::get_all();

        foreach ( $templates as $template ) {
            $this->repository->create_template( [
                'title'       => sanitize_text_field( $template['title'] ),
                'description' => sanitize_textarea_field( $template['description'] ),
                'code'        => $template['code'],
                'language'    => sanitize_key( $template['language'] ),
                'location'    => sanitize_key( $template['location'] ),
                'tags'        => sanitize_text_field( $template['tags'] ),
                'is_builtin'  => 1,
            ] );
        }
    }

    /**
     * Reinstall built-in templates (replaces existing).
     */
    public function reinstall_builtin_templates(): void {
        $this->remove_builtin_templates();
        $this->install_builtin_templates();
    }

    /**
     * Remove all built-in templates.
     */
    private function remove_builtin_templates(): void {
        global $wpdb;

        $table = $this->repository::TABLE_TEMPLATES ?? \StarterSnippets\Core\Config::TABLE_TEMPLATES;
        $table_name = $wpdb->prefix . $table;

        $wpdb->query( "DELETE FROM {$table_name} WHERE is_builtin = 1" );
    }
}
