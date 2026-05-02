<?php
/**
 * License Settings Page Template
 *
 * @package StarterSnippets\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Variables passed in: $license_key, $status
$is_pro = \StarterSnippets\Core\starter_snippets_is_pro_active();
?>

<div class="wrap starter-snippets-wrap">
    <h1><?php esc_html_e( 'Starter Snippets Pro License', 'starter-snippets' ); ?></h1>

    <div class="starter-snippets-notice notice-success notice">
        <p>
            <strong><?php esc_html_e( 'Pro features are active!', 'starter-snippets' ); ?></strong>
            <?php esc_html_e( 'You have full access to Advanced Conditions and more. No license key is required.', 'starter-snippets' ); ?>
        </p>
    </div>
</div>
