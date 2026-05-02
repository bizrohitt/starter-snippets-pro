<?php
/**
 * Dashboard page template.
 *
 * @var array $stats  Stats array with total, active, inactive.
 * @package StarterSnippets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$add_url = admin_url( 'admin.php?page=starter-snippets-add' );
$all_url = admin_url( 'admin.php?page=starter-snippets' );
?>
<div class="wrap starter-snippets-wrap">
    <h1>
        <?php esc_html_e( 'Starter Snippets', 'starter-snippets' ); ?>
        <a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action">
            <?php esc_html_e( 'Add New Snippet', 'starter-snippets' ); ?>
        </a>
    </h1>

    <div class="starter-snippets-cards">
        <div class="starter-snippets-card starter-snippets-card--total">
            <div class="starter-snippets-card__icon dashicons dashicons-editor-code"></div>
            <div class="starter-snippets-card__body">
                <span class="starter-snippets-card__number"><?php echo esc_html( $stats['total'] ); ?></span>
                <span class="starter-snippets-card__label"><?php esc_html_e( 'Total Snippets', 'starter-snippets' ); ?></span>
            </div>
        </div>

        <div class="starter-snippets-card starter-snippets-card--active">
            <div class="starter-snippets-card__icon dashicons dashicons-yes-alt"></div>
            <div class="starter-snippets-card__body">
                <span class="starter-snippets-card__number"><?php echo esc_html( $stats['active'] ); ?></span>
                <span class="starter-snippets-card__label"><?php esc_html_e( 'Active', 'starter-snippets' ); ?></span>
            </div>
        </div>

        <div class="starter-snippets-card starter-snippets-card--inactive">
            <div class="starter-snippets-card__icon dashicons dashicons-marker"></div>
            <div class="starter-snippets-card__body">
                <span class="starter-snippets-card__number"><?php echo esc_html( $stats['inactive'] ); ?></span>
                <span class="starter-snippets-card__label"><?php esc_html_e( 'Inactive', 'starter-snippets' ); ?></span>
            </div>
        </div>
    </div>

    <!-- All Snippets Table -->
    <h2><?php esc_html_e( 'All Snippets', 'starter-snippets' ); ?></h2>

    <?php
    // Build a simple table of snippets.
    $repo     = new \StarterSnippets\Database\Repository();
    $snippets = $repo->find_all( [ 'per_page' => 50 ] );
    ?>

    <?php if ( isset( $_GET['deleted'] ) ) : ?>
        <div class="notice notice-success"><p><?php esc_html_e( 'Snippet deleted.', 'starter-snippets' ); ?></p></div>
    <?php endif; ?>

    <?php if ( isset( $_GET['bulk'] ) ) : ?>
        <div class="notice notice-success"><p><?php esc_html_e( 'Bulk action completed.', 'starter-snippets' ); ?></p></div>
    <?php endif; ?>

    <form method="post">
        <?php \StarterSnippets\Security\NonceHandler::field(); ?>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action" id="starter-snippets-bulk-action">
                    <option value=""><?php esc_html_e( 'Bulk Actions', 'starter-snippets' ); ?></option>
                    <option value="activate"><?php esc_html_e( 'Activate', 'starter-snippets' ); ?></option>
                    <option value="deactivate"><?php esc_html_e( 'Deactivate', 'starter-snippets' ); ?></option>
                    <option value="delete"><?php esc_html_e( 'Delete', 'starter-snippets' ); ?></option>
                </select>
                <?php submit_button( __( 'Apply', 'starter-snippets' ), 'action', 'do_bulk', false ); ?>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped starter-snippets-table">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all"></td>
                    <th><?php esc_html_e( 'Title', 'starter-snippets' ); ?></th>
                    <th><?php esc_html_e( 'Language', 'starter-snippets' ); ?></th>
                    <th><?php esc_html_e( 'Location', 'starter-snippets' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'starter-snippets' ); ?></th>
                    <th><?php esc_html_e( 'Priority', 'starter-snippets' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'starter-snippets' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $snippets ) ) : ?>
                    <tr><td colspan="7"><?php esc_html_e( 'No snippets found. Create your first snippet!', 'starter-snippets' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $snippets as $s ) : ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" name="snippet_ids[]" value="<?php echo esc_attr( $s->id ); ?>">
                            </th>
                            <td>
                                <strong>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=starter-snippets-add&id=' . $s->id ) ); ?>">
                                        <?php echo esc_html( $s->title ); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=starter-snippets-add&id=' . $s->id ) ); ?>">
                                            <?php esc_html_e( 'Edit', 'starter-snippets' ); ?>
                                        </a> |
                                    </span>
                                    <span class="trash">
                                        <a href="<?php echo esc_url( wp_nonce_url(
                                            admin_url( 'admin.php?page=starter-snippets&action=delete&snippet_id=' . $s->id ),
                                            'starter_snippets_delete_' . $s->id
                                        ) ); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e( 'Delete this snippet?', 'starter-snippets' ); ?>');">
                                            <?php esc_html_e( 'Delete', 'starter-snippets' ); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="starter-snippets-badge starter-snippets-badge--<?php echo esc_attr( $s->language ); ?>">
                                    <?php echo esc_html( strtoupper( $s->language ) ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( ucfirst( $s->location ) ); ?></td>
                            <td>
                                <button type="button"
                                        class="starter-snippets-toggle button-link"
                                        data-snippet-id="<?php echo esc_attr( $s->id ); ?>"
                                        data-status="<?php echo esc_attr( $s->status ); ?>">
                                    <span class="starter-snippets-status starter-snippets-status--<?php echo esc_attr( $s->status ); ?>">
                                        <?php echo 'active' === $s->status
                                            ? esc_html__( 'Active', 'starter-snippets' )
                                            : esc_html__( 'Inactive', 'starter-snippets' ); ?>
                                    </span>
                                </button>
                            </td>
                            <td><?php echo esc_html( $s->priority ); ?></td>
                            <td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $s->created_at ) ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>
